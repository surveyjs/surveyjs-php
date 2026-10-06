<?php

declare(strict_types=1);

namespace App\Relay;

/**
 * Demo only: in demo mode a room belongs to one visitor sandbox, so its key is "<demo_sid>:<id>".
 * Outside demo mode the key is the plain record or form id, as on the page.
 */
final class RoomKey
{
    public static function for(?string $sandboxId, string $id): string
    {
        return $sandboxId === null ? $id : $sandboxId.':'.$id;
    }

    /**
     * Where the relay writes a room snapshot for the "What the server stored" panel.
     * Room keys contain ":" (invalid on Windows) and ids from the URL, so the file is named by a hash.
     */
    public static function snapshotPath(string $roomKey, string $relay = 'fill'): string
    {
        $name = hash('sha256', $roomKey).'.json';

        return storage_path('app/relay/'.($relay === 'fill' ? $name : $relay.'-'.$name));
    }
}
