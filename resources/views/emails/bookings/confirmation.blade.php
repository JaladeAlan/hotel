@component('mail::message')
# Booking Confirmation

Hello {{ $user->name }},

Your booking has been successfully confirmed!

## Booking Details:
- **Room:** {{ $room->room_type }} (Room #{{ $room->room_number }})
- **Check-in Date:** {{ \Carbon\Carbon::parse($booking->check_in_date)->toFormattedDateString() }}
- **Check-out Date:** {{ \Carbon\Carbon::parse($booking->check_out_date)->toFormattedDateString() }}
- **Total Price:** ₦{{ number_format($booking->total_price, 2) }}

Thank you for choosing our hotel. We look forward to hosting you.

@component('mail::button', ['url' => url('/')])
Visit Our Website
@endcomponent

Thanks,  
{{ config('app.name') }}
@endcomponent
