<?php
return [

    'canal' => env('LEMBRETES_CANAL', 'sms'),

    'sms' => [
        'driver' => env('LEMBRETES_SMS_DRIVER', 'log'),
        'sid' => env('TWILIO_SID'),
        'token' => env('TWILIO_TOKEN'),
        'from' => env('TWILIO_FROM'),
    ],

];
