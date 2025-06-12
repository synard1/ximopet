<?php

$url = 'http://demo51.local/api/menu?location=sidebar';
$token = '16|jm2c50xGr5bGoPCEJh8uC6bjOUab15pgxP4PDmy5b0c8c8e4';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
    'Accept: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status Code: " . $httpCode . "\n\n";
echo "Response Data:\n";
echo json_encode(json_decode($response), JSON_PRETTY_PRINT);
