<?php
// Set 10s timeout so it doesn't hang
$ch = curl_init('https://gql.hashnode.com');
$query = json_encode([
    'query' => 'query { user(username: "learnmore2027") { id username name publications(first: 10) { edges { node { id title url } } } } }'
]);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $query,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'User-Agent: Mozilla/5.0'
    ],
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_TIMEOUT => 10
]);
$resp = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$redirect = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
curl_close($ch);

echo json_encode(['code' => $code, 'redirect' => $redirect, 'resp' => $resp]);
