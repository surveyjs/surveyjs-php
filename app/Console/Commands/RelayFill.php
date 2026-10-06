<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Relay\FillRelay;
use App\Relay\RoomKey;
use Closure;
use Illuminate\Console\Command;
use Throwable;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;
use Workerman\Worker;

/**
 * Starts the I.8 relay (app/Relay/FillRelay.php) as its own long-running process, because
 * PHP-FPM and `php artisan serve` can't hold WebSocket connections. Runs on Windows and Linux.
 */
class RelayFill extends Command
{
    protected $signature = 'relay:fill';

    protected $description = 'Run the I.8 "Fill one form together" WebSocket relay (RELAY_PORT, default 8081)';

    public function handle(): int
    {
        $port = config('surveyjs.relay.fill_port');
        $relay = new FillRelay;

        TcpConnection::$defaultMaxPackageSize = 20 * 1024 * 1024;   // a large answer (a signature, a long text) fits in one frame
        $worker = new Worker("websocket://0.0.0.0:{$port}");
        $worker->name = 'surveyjs-fill-relay';
        $worker->onWebSocketConnected = fn (TcpConnection $c, Request $request) => $this->guard($c, function () use ($relay, $c, $request) {
            $relay->onConnect($c, $request);
            $this->snapshot($relay, $c);
        });
        $worker->onMessage = fn (TcpConnection $c, string $data) => $this->guard($c, function () use ($relay, $c, $data) {
            $relay->onMessage($c, $data);
            if (str_contains($data, '"value"')) {
                $this->snapshot($relay, $c);
            }
        });
        $worker->onClose = fn (TcpConnection $c) => $this->guard($c, function () use ($relay, $c) {
            $relay->onClose($c);
            $this->snapshot($relay, $c);
        });
        $worker->onError = fn (TcpConnection $c, int $code, string $message) => $relay->onError($c, $code, $message);

        Worker::$pidFile = storage_path('framework/relay-fill.pid');
        Worker::$logFile = storage_path('logs/relay-fill.log');
        Worker::$command = 'start';   // Workerman reads its command from argv on Linux and macOS
        $this->components->info("I.8 fill relay listening on ws://0.0.0.0:{$port}/ws/rooms/{recordId}");
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

    /** Demo only: the room's latest values for the "What the server stored" panel. */
    private function snapshot(FillRelay $relay, TcpConnection $c): void
    {
        $room = $relay->roomOf($c);
        if (config('surveyjs.demo_mode') && $room) {
            RoomKey::writeSnapshot($c->context->roomKey, 'fill', [
                'participants' => count($room->clients),
                'values' => $room->snapshot(),
            ]);
        }
    }
}
