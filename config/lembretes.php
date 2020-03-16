<?php
return [

    'canal' => env('LEMBRETES_CANAL', 'sms'),

    'sms' => [
        'sid' => env('TWILIO_SID'),
        'token' => env('TWILIO_TOKEN'),
        'from' => env('TWILIO_FROM'),
    ],

];
