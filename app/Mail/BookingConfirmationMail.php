<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BookingConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $booking;

    /**
     * Create a new message instance.
     */
    public function __construct(Booking $booking)
    {
        $this->booking = $booking;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Your Booking Confirmation')
                    ->markdown('emails.bookings.confirmation')
                    ->with([
                        'booking' => $this->booking,
                        'user' => $this->booking->user,
                        'room' => $this->booking->room,
                    ]);
    }
}
