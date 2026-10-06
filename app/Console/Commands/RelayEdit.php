<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Relay\EditRelay;
use App\Relay\RoomKey;
use Closure;
use Illuminate\Console\Command;
use Throwable;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;
use Workerman\Worker;

/**
 * Starts the III.5 relay (app/Relay/EditRelay.php) as its own long-running process, because
 * PHP-FPM and `php artisan serve` can't hold WebSocket connections. Runs on Windows and Linux.
 */
class RelayEdit extends Command
{
    protected $signature = 'relay:edit';

    protected $description = 'Run the III.5 "Edit one form together" WebSocket relay (EDIT_RELAY_PORT, default 8082)';

    public function handle(): int
    {
        $port = config('surveyjs.relay.edit_port');
        $relay = new EditRelay;

        TcpConnection::$defaultMaxPackageSize = 5 * 1024 * 1024;   // the reference relay's limit for one Creator record
        $worker = new Worker("websocket://0.0.0.0:{$port}");
        $worker->name = 'surveyjs-edit-relay';
        $worker->onWebSocketConnected = fn (TcpConnection $c, Request $request) => $this->guard($c, function () use ($relay, $c, $request) {
            $relay->onConnect($c, $request);
            $this->snapshot($relay, $c);
        });
        $worker->onMessage = fn (TcpConnection $c, string $data) => $this->guard($c, function () use ($relay, $c, $data) {
            $relay->onMessage($c, $data);
            if (str_contains($data, '"append"')) {
                $this->snapshot($relay, $c);
            }
        });
        $worker->onClose = fn (TcpConnection $c) => $this->guard($c, function () use ($relay, $c) {
            $relay->onClose($c);
            $this->snapshot($relay, $c);
        });
        $worker->onError = fn (TcpConnection $c, int $code, string $message) => $relay->onError($c, $code, $message);

        Worker::$pidFile = storage_path('framework/relay-edit.pid');
        Worker::$logFile = storage_path('logs/relay-edit.log');
        Worker::$command = 'start';   // Workerman reads its command from argv on Linux and macOS
        $this->components->info("III.5 edit relay listening on ws://0.0.0.0:{$port}/ws/forms/{formId}");
        Worker::runAll();

        return self::SUCCESS;
    }

    /** An exception in a Workerman callback would stop the whole worker: close only that connection. */
    private function guard(TcpConnection $c, Closure $callback): void
    {
        try {
            $callback();
        } catch (Throwable $e) {
            report($e);
            $c->close();
        }
    }

    /** Demo only: the room's log length and participants for the "What the server stored" panel. */
    private function snapshot(EditRelay $relay, TcpConnection $c): void
    {
        $room = $relay->roomOf($c);
        if (config('surveyjs.demo_mode') && $room) {
            RoomKey::writeSnapshot($c->context->roomKey, 'edit', [
                'participants' => count($room->clients),
                'seed title' => $room->seed->title ?? null,
                'records in the log' => count($room->log),
            ]);
        }
    }
}
