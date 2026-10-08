<?php

declare(strict_types=1);

namespace App\Relay;

use App\Support\Sandbox;
use Illuminate\Support\Facades\File;
use Workerman\Protocols\Http\Request;

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
     * The room key for a WebSocket handshake. In demo mode the demo_sid cookie picks the sandbox
     * (the same format check as the DemoSandbox middleware) and the connection switches to its
     * database, so the room is seeded from that sandbox. Null when the cookie is missing or invalid.
     */
    public static function fromHandshake(Request $request, string $id): ?string
    {
        if (! config('surveyjs.demo_mode')) {
            return $id;
        }

        $sandboxId = $request->cookie('demo_sid');
        if (! Sandbox::isValidId($sandboxId)) {
            return null;
        }
        Sandbox::activate($sandboxId);

        return self::for($sandboxId, $id);
    }

    /** Demo only: write a room's state for the "What the server stored" panel. */
    public static function writeSnapshot(string $roomKey, string $relay, array $snapshot): void
    {
        $path = self::snapshotPath($roomKey, $relay);
        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode($snapshot + ['updatedAt' => now('UTC')->toIso8601ZuluString()], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION));
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
