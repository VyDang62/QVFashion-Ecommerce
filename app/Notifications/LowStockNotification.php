<?php

namespace App\Notifications;

use App\Models\ProductVariant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LowStockNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $variant;

    /**
     * Create a new notification instance.
     */
    public function __construct(ProductVariant $variant)
    {
        $this->variant = $variant;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        // $channels = $notifiable->getEnabledChannels('low_stock');
        // if(empty($channels)){
        //     return ['database'];
        // }
        // return $channels;
        return ['database', 'broadcast'];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage{
        return new BroadcastMessage([
            'id' => $this->id,
            'data' => $this->toArray($notifiable),
            'created_at' => now()->toISOString(),
        ]);
    }
    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->line('The introduction to the notification.')
            ->action('Notification Action', url('/'))
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $productName = $this->variant->product->product_name ?? 'Sản phẩm không xác định';
        return [
            'type' => 'low_stock',
            'label' => 'Sắp hết hàng',
            'product_id' => $this->variant->product_id,
            'variant_id' => $this->variant->id,
            'sku' => $this->variant->sku,
            'product_name' => $productName,
            'current_stock' => $this->variant->stock_quantity,
            'threshold' => $this->variant->low_stock_threshold,
            'message' => "Sản phẩm {$productName} (SKU: {$this->variant->sku}) sắp hết hàng!",
        ];
    }
}
