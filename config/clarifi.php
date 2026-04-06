<?php

return [
    'default_currency' => env('CLARIFI_DEFAULT_CURRENCY', 'CLP'),
    'supported_currencies' => [
        'CLP' => 'Peso chileno (CLP)',
        'USD' => 'Dolar estadounidense (USD)',
        'EUR' => 'Euro (EUR)',
    ],
];
