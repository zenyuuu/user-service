<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Illuminate\Support\Facades\Log;

class PublishUserEventJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private string $eventType,
        private array  $payload
    ) {}

    public function handle(): void
    {
        try {
            $connection = new AMQPStreamConnection(
                host:     env('RABBITMQ_HOST', 'rabbitmq'),
                port:     env('RABBITMQ_PORT', 5672),
                user:     env('RABBITMQ_USER', 'admin'),
                password: env('RABBITMQ_PASSWORD', 'admin123'),
                vhost:    env('RABBITMQ_VHOST', '/'),
            );

            $channel = $connection->channel();

            // Declare exchange (topic type agar bisa routing fleksibel)
            $channel->exchange_declare(
                exchange:     'user_events',
                type:         'topic',
                passive:      false,
                durable:      true,
                auto_delete:  false,
            );

            $body = json_encode([
                'event'     => $this->eventType,
                'payload'   => $this->payload,
                'timestamp' => now()->toISOString(),
            ]);

            $message = new AMQPMessage($body, [
                'content_type'  => 'application/json',
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
            ]);

            $channel->basic_publish(
                msg:         $message,
                exchange:    'user_events',
                routing_key: $this->eventType,   // contoh: "user.registered"
            );

            Log::info("Event published: {$this->eventType}", $this->payload);

            $channel->close();
            $connection->close();

        } catch (\Exception $e) {
            Log::error("Gagal publish event ke RabbitMQ: " . $e->getMessage());
            throw $e;
        }
    }
}
