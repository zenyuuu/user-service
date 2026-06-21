<?php
$curl = curl_init('http://localhost/api/auth/register');
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode([
    'name' => 'Test', 
    'email' => 'test@ex.com', 
    'password' => 'password123', 
    'password_confirmation' => 'password123'
]));
curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($curl);
echo $response;
