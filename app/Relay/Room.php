<?php

declare(strict_types=1);

namespace App\Relay;

use Workerman\Connection\TcpConnection;
use Workerman\Timer;

/**
 * One I.8 room: the definition it hands out, the latest value of every answer and the people in it.
 * Each connection keeps its own id, name, colour and presence in $connection->context.
 */
final class Room
{
    /** @var array<string, mixed> question key => latest value; last write wins */
    public array $values = [];

    /** @var array<int, TcpConnection> connection id => connection */
    public array $clients = [];

    public ?int $dropTimer = null;

    /** Someone joined an emptied room before it was dropped: keep it. */
    public function cancelDrop(): void
    {
        if ($this->dropTimer !== null) {
            Timer::del($this->dropTimer);
            $this->dropTimer = null;
        }
    }

    public function __construct(public readonly object $seed, array $values = [])
    {
        $this->values = $values;
    }

    /** The lowest colour slot nobody in the room holds, shown as 1..9 (0 means "unknown"). */
    public function freeSlot(): int
    {
        $taken = [];
        foreach ($this->clients as $client) {
            $taken[$client->context->slot] = true;
        }
        for ($slot = 1; isset($taken[$slot]); $slot++);

        return $slot;
    }

    public static function colorIndex(int $slot): int
    {
        return 1 + (($slot - 1) % 9);
    }

    /** Everyone else who has shared a retained presence: { clientId, name, colorIndex, state }. */
    public function roster(TcpConnection $except): array
    {
        $peers = [];
        foreach ($this->clients as $client) {
            if ($client !== $except && $client->context->state !== null) {
                $peers[] = self::peer($client, $client->context->state);
            }
        }

        return $peers;
    }

    public static function peer(TcpConnection $client, mixed $state): array
    {
        return [
            'clientId' => $client->context->id,
            'name' => $client->context->name,
            'colorIndex' => self::colorIndex($client->context->slot),
            'state' => $state,
        ];
    }

    /** The answers as a JSON object, even when empty or keyed like a list. */
    public function snapshot(): array|object
    {
        return $this->values === [] || array_is_list($this->values) ? (object) $this->values : $this->values;
    }

    /** Send to everyone in the room except the sender: nobody gets their own echo. */
    public function broadcast(TcpConnection $from, array $message, bool $droppable = false): void
    {
        $json = json_encode($message, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);
        foreach ($this->clients as $client) {
            // A non-retained presence update (a cursor move) may be dropped for a peer that is falling behind
            if ($client !== $from && ! ($droppable && $client->getSendBufferQueueSize() > 64 * 1024)) {
                $client->send($json);
            }
        }
    }
}
