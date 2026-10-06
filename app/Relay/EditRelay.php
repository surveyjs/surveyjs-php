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
 * III.5 Edit one form together: the relay behind CollaborationPlugin from survey-creator-core/collaboration.
 * Wire protocol: https://github.com/surveyjs/collaborative-form-editing (PROTOCOL.md).
 * Started by `php artisan relay:edit` (app/Console/Commands/RelayEdit.php).
 */
final class EditRelay
{
    // #region sjs:III.5.server
    // The same kind of relay as I.8, keeping an append-only log instead of a value map:
    // Creator records must be replayed in order. Run as its own process: php artisan relay:edit
    private array $rooms = [];   // room key => EditRoom { seed, log, clients }

    public function onConnect(TcpConnection $c, Request $request): void
    {
        $user = $this->editor($request);                                     // may this user edit this form?
        $formId = $this->formId($request);
        $roomKey = $formId === null ? null : RoomKey::fromHandshake($request, $formId);
        if (! $user || $roomKey === null) {
            $c->close();

            return;
        }
        $room = $this->rooms[$roomKey] ??= new EditRoom($this->savedDefinition($formId));   // seed = saved definition (III.1)
        $room->cancelDrop();                                                  // back within 2 s: keep the room
        $c->context->roomKey = $roomKey;
        $c->context->id = bin2hex(random_bytes(8));
        $c->context->name = self::name($request->get('name'), $user->name);
        $c->context->slot = $room->freeSlot();
        $c->context->state = null;
        $room->clients[$c->id] = $c;
        $this->send($c, ['type' => 'init', 'clientId' => $c->context->id, 'colorIndex' => $c->context->slot,
            'color' => EditRoom::PALETTE[$c->context->slot], 'seed' => $room->seed, 'log' => $room->log]);   // init first
        if ($peers = $room->roster($c)) {
            $this->send($c, ['type' => 'presence-sync', 'peers' => $peers]);
        }
    }

    public function onMessage(TcpConnection $from, string $raw): void
    {
        $msg = json_decode($raw);                                                // records stay opaque: {} is relayed as {}
        $room = $this->rooms[$from->context->roomKey ?? ''] ?? null;
        if (! $room || ! $msg instanceof stdClass) {
            return;
        }
        if (($msg->type ?? '') === 'append' && isset($msg->payload)) {          // append, then fan out
            $room->log[] = $msg->payload;
            $room->broadcast($from, ['type' => 'record', 'from' => $from->context->id, 'payload' => $msg->payload]);
        } elseif (($msg->type ?? '') === 'presence' && isset($msg->state) && strlen($raw) <= 4096) {
            $from->context->state = $msg->state;
            $room->broadcast($from, ['type' => 'presence', 'peer' => EditRoom::peer($from)]);
        }                                                                        // unknown types: ignore
    }

    public function onClose(TcpConnection $c): void
    {
        $room = $this->rooms[$c->context->roomKey ?? ''] ?? null;
        if (! $room) {
            return;
        }
        unset($room->clients[$c->id]);
        $room->broadcast($c, ['type' => 'presence-leave', 'clientId' => $c->context->id]);
        if (count($room->clients) === 0) {                                      // the log lives until the room empties
            $room->dropTimer = Timer::add(2, fn () => count($room->clients) === 0 && $this->drop($c->context->roomKey), [], false);
        }
    }
    // #endregion

    public function onError(TcpConnection $c, int $code, string $message): void
    {
        $c->close();
    }

    /** Demo only: the room a connection is in, for the stored panel's snapshot. */
    public function roomOf(TcpConnection $c): ?EditRoom
    {
        return $this->rooms[$c->context->roomKey ?? ''] ?? null;
    }

    private function drop(string $roomKey): void
    {
        unset($this->rooms[$roomKey]);
    }

    /**
     * Demo only: the demo_user cookie stands in for your real session check here. Like the
     * III.1 save, editing is for editors (Gate "edit-forms" in AppServiceProvider).
     */
    private function editor(Request $request): ?object
    {
        $email = DemoUser::USERS[$request->cookie('demo_user')] ?? null;
        $user = $email ? DB::table('users')->where('email', $email)->first(['name', 'is_editor']) : null;

        return $user && $user->is_editor ? $user : null;
    }

    /** The form id from /ws/forms/{formId}. */
    private function formId(Request $request): ?string
    {
        return preg_match('~^/ws/forms/([A-Za-z0-9_-]{1,64})$~', $request->path(), $m) ? $m[1] : null;
    }

    /** The definition saved through III.1, or an empty one for a new form. */
    private function savedDefinition(string $formId): object
    {
        return json_decode((string) DB::table('forms')->where('key', $formId)->value('json')) ?? new stdClass;
    }

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
