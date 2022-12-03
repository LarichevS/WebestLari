<?php

const TOKEN = '5660869677:AAEgGN4W3FgaBkJovJwBrBmcZPBa6mmWTGA';
$method = 'setWebhook';

$url = 'https://api.telegram.org/bot' . TOKEN . '/' . $method;
$options = [
    'url' => 'https://students2022.store/index.php',
    ];
    
    $response = file_get_contents($url . '?' . http_build_query($options));
    
    var_dump($response);