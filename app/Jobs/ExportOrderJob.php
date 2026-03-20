<?php

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class ExportOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public readonly Order $order,
    ) {}

    public function handle(): void
    {
        $url = config('services.export.url', 'https://httpbin.org/post');

        Http::post($url, [
            'order_id' => $this->order->id,
            'status' => $this->order->status,
            'total_amount' => $this->order->total_amount,
            'customer_id' => $this->order->customer_id,
        ]);
    }
}
