<?php

namespace App\Mail;

use App\Models\Order; // 1. Quan trọng: Phải import Model Order
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderSuccess extends Mailable
{
    use Queueable, SerializesModels;

    // 2. Khai báo biến public để lưu đơn hàng
    public $order;

    /**
     * Create a new message instance.
     */
    // 3. QUAN TRỌNG NHẤT: Constructor phải nhận tham số Order
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            // Tiêu đề email
            subject: 'Xác nhận đơn hàng #' . $this->order->id . ' - PDA Fashion',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        // Trỏ đúng về file view
        return new Content(
            view: 'emails.order_success',
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}