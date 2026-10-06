<?php

declare(strict_types=1);

namespace App\Relay;

use Workerman\Connection\TcpConnection;
use Workerman\Timer;

/**
 * One III.5 room: the saved definition it started from, every Creator record since (in arrival
 * order) and the people in it. Each connection keeps its id, name, colour and presence in $connection->context.
 */
final class EditRoom
{
    /** Colour slots, skipping 5 (yellow is unreadable with white text); 0 is gray and never assigned. */
    public const SLOTS = [1, 2, 3, 4, 6, 7, 8, 9];

    /** The colour of each slot: the same values as survey-core's --sjs2-color-utility-user-bg-color-N. */
    public const PALETTE = ['#808080', '#1570EF', '#CA4FFB', '#19B35C', '#19B394', '#F9C50B', '#F99130', '#F1529C', '#02ADEB', '#4E6198'];

    /** @var list<mixed> append-only: never deduplicated, replayed in this order */
    public array $log = [];

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

    public function __construct(public readonly object $seed) {}

    public function freeSlot(): int
    {
        $taken = [];
        foreach ($this->clients as $client) {
            $taken[] = $client->context->slot;
        }
        foreach (self::SLOTS as $slot) {
            if (! in_array($slot, $taken, true)) {
                return $slot;
            }
        }

        return self::SLOTS[count($this->clients) % count(self::SLOTS)];
    }

    /** Everyone else who has shared their presence: { clientId, name, colorIndex, color, state }. */
    public function roster(TcpConnection $except): array
    {
        $peers = [];
        foreach ($this->clients as $client) {
            if ($client !== $except && $client->context->state !== null) {
                $peers[] = self::peer($client);
            }
        }

        return $peers;
    }

    /** Creator's plugin paints rings and cursors from `color`, so each peer carries it next to colorIndex. */
    public static function peer(TcpConnection $client): array
    {
        return [
            'clientId' => $client->context->id,
            'name' => $client->context->name,
            'colorIndex' => $client->context->slot,
            'color' => self::PALETTE[$client->context->slot],
            'state' => $client->context->state,
        ];
    }

    /** Send to everyone in the room except the sender. */
    public function broadcast(TcpConnection $from, array $message): void
    {
        $json = json_encode($message, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);
        foreach ($this->clients as $client) {
            if ($client !== $from) {
                $client->send($json);
            }
        }
    }
}
