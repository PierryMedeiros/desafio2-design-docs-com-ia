<?php

return [

    'antecedencia_horas' => 24,

    'canais' => ['whatsapp', 'sms'],

    'whatsapp' => [
        'driver' => env('LEMBRETES_WHATSAPP_DRIVER', 'log'),
        'versao_api' => env('WHATSAPP_API_VERSION', 'v18.0'),
        'token' => env('WHATSAPP_TOKEN'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'template' => env('WHATSAPP_TEMPLATE', 'lembrete_consulta'),
    ],

    'sms' => [
        'driver' => env('LEMBRETES_SMS_DRIVER', 'log'),
        'sid' => env('TWILIO_SID'),
        'token' => env('TWILIO_TOKEN'),
        'from' => env('TWILIO_FROM'),
    ],

];
