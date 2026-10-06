<?php

declare(strict_types=1);

namespace App\Relay;

use App\Http\Middleware\DemoUser;
use Illuminate\Support\Facades\DB;
use stdClass;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;
use Workerman\Timer;

/**
 * I.8 Fill one form together: the relay behind CollaborationPlugin from survey-core/collaboration.
 * Wire protocol: https://github.com/surveyjs/collaborative-form-filling (PROTOCOL.md).
 * Started by `php artisan relay:fill` (app/Console/Commands/RelayFill.php).
 */
final class FillRelay
{
    // #region sjs:I.8.server
    // The relay, run as its own process: php artisan relay:fill (PHP-FPM can't hold connections).
    // No SurveyJS here: the definition and the answers are opaque JSON.
    private array $rooms = [];   // room key => Room { seed, values, clients }

    public function onConnect(TcpConnection $c, Request $request): void
    {
        $user = $this->user($request);                                       // may this user open this record?
        $recordId = $this->recordId($request);
        $roomKey = $recordId === null ? null : RoomKey::fromHandshake($request, $recordId);
        if (! $user || $roomKey === null) {
            $c->close();

            return;
        }
        $room = $this->rooms[$roomKey] ??= new Room(...$this->seed($recordId));   // the definition + the saved answers
        $room->cancelDrop();                                                  // back within 2 s: keep the room
        $c->context->roomKey = $roomKey;
        $c->context->id = bin2hex(random_bytes(8));
        $c->context->name = self::name($request->get('name'), $user->name);
        $c->context->slot = $room->freeSlot();
        $c->context->state = null;
        $room->clients[$c->id] = $c;
        $this->send($c, ['type' => 'init', 'clientId' => $c->context->id, 'name' => $c->context->name,
            'colorIndex' => Room::colorIndex($c->context->slot), 'seed' => $room->seed,
            'values' => $room->snapshot(), 'peers' => $room->roster($c)]);          // init first
    }

    public function onMessage(TcpConnection $from, string $raw): void
    {
        $msg = json_decode($raw);                                                // objects stay objects: {} is relayed as {}
        $room = $this->rooms[$from->context->roomKey ?? ''] ?? null;
        if (! $room || ! $msg instanceof stdClass) {
            return;
        }
        if (($msg->type ?? '') === 'value' && is_string($msg->key ?? null)) {   // store, then fan out
            $room->values[$msg->key] = $msg->value ?? null;
            $room->broadcast($from, ['type' => 'value', 'from' => $from->context->id, 'key' => $msg->key, 'value' => $msg->value ?? null]);
        } elseif (($msg->type ?? '') === 'presence' && isset($msg->state) && strlen($raw) <= 4096) {   // stamp identity, relay
            $retain = ($msg->retain ?? true) !== false;
            if ($retain) {
                $from->context->state = $msg->state;
            }
            $room->broadcast($from, ['type' => 'peer', 'retain' => $retain, 'peer' => Room::peer($from, $msg->state)], droppable: ! $retain);
        }                                                                        // unknown types: ignore
    }

    public function onClose(TcpConnection $c): void
    {
        $room = $this->rooms[$c->context->roomKey ?? ''] ?? null;
        if (! $room) {
            return;
        }
        unset($room->clients[$c->id]);
        $room->broadcast($c, ['type' => 'peer-left', 'clientId' => $c->context->id]);
        if (count($room->clients) === 0) {                                      // drop the room 2 s after the last person leaves
            $room->dropTimer = Timer::add(2, fn () => count($room->clients) === 0 && $this->drop($c->context->roomKey), [], false);
        }
    }
    // #endregion

    public function onError(TcpConnection $c, int $code, string $message): void
    {
        $c->close();
    }

    /** Demo only: the room a connection is in, for the stored panel's snapshot. */
    public function roomOf(TcpConnection $c): ?Room
    {
        return $this->rooms[$c->context->roomKey ?? ''] ?? null;
    }

    private function drop(string $roomKey): void
    {
        unset($this->rooms[$roomKey]);
    }

    /**
     * Demo only: the demo_user cookie stands in for your real session check here
     * (the same session or token your Laravel app authenticates with).
     */
    private function user(Request $request): ?object
    {
        $email = DemoUser::USERS[$request->cookie('demo_user')] ?? null;

        return $email ? DB::table('users')->where('email', $email)->first(['name', 'plan', 'is_editor']) : null;
    }

    /** The record id from /ws/rooms/{recordId}, the same ids the reference relay accepts. */
    private function recordId(Request $request): ?string
    {
        return preg_match('~^/ws/rooms/([A-Za-z0-9_-]{1,64})$~', $request->path(), $m) ? $m[1] : null;
    }

    /** The job sheet definition, and the saved answers when the record exists (otherwise a blank sheet). */
    private function seed(string $recordId): array
    {
        $definition = json_decode((string) DB::table('forms')->where('key', 'job-sheet')->value('json')) ?? new stdClass;
        $data = DB::table('responses')->where('form_id', 'job-sheet')->where('id', $recordId)->value('data');

        return [$definition, $data === null ? [] : (array) json_decode($data)];
    }

    /** The display name from ?name=, trimmed to 32 characters. */
    private static function name(mixed $name, string $fallback): string
    {
        $name = is_string($name) ? mb_substr(trim($name), 0, 32) : '';

        return $name !== '' ? $name : $fallback;
    }

    private function send(TcpConnection $c, array $message): void
    {
        $c->send(json_encode($message, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION));
    }
}
