<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\Product;
use App\Models\Warehouse;

class LowStockNotification extends Notification
{
    use Queueable;

    public $product;
    public $warehouse;
    public $remaining_qty;

    public function __construct(Product $product, Warehouse $warehouse, $remaining_qty)
    {
        $this->product = $product;
        $this->warehouse = $warehouse;
        $this->remaining_qty = $remaining_qty;
    }

    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }

    public function toArray($notifiable)
    {
        return [
            'title' => '📦 Low Stock Alert',
            'message' => "{$this->product->name} in {$this->warehouse->name} is down to {$this->remaining_qty} items.",
            'url' => url("/warehouses/{$this->warehouse->id}"), 
        ];
    }
}

