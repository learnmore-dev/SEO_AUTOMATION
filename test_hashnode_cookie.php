<?php
$cookies = [
    'hashnode-session' => 'eyJhbGciOiJkaXIiLCJlbmMiOiJBMjU2Q0JDLUhTNTEyIiwia2lkIjoiODBpR2h0XzIyN0dKLWxLejd4blZSVC13TllVRnZEZjV1eDVmSnVGeUVRd3VVWXBzYlhZQ3VYLWFjMFlhQnlsNm9qZ3o2dFBXUU5QVTVDak00dEdWZGcifQ..7LcLpwjOZM7eFypq4wMoXg.DKtje8PEKqFN4cRnWVPUzuVHGnCzsyrBgPWRVANq92yiHFgVewE4VDDzGv28v91tlsdHhuVmKE7xY3FYEHCOhNOe4Di-dMugnkajWMypsESck6bTLBKD2MD7PFGXUvq84Qe3ZY0YYb3SayiRmQXAYQvMeWDed6MBE8cZOPw4JPrEgnWq-PLg2Z7BV5NfhIv8arKuRLB6iYhSn5jos5GUa8JILI7anSV8TeTYiAbuhNS2Weem2bGrlekvkbxYESdrPsnZp8aCDz9pu3jNvO-c82sKqj5i44tEpFPpycOqlTQH7dxJd70ndKDNtU0xJWmiZ6gLXi042ndPjwqVAuJoBc7FM37wd70U8cohq9dJOf7syMcbMqsC71f_bSMVobbVfzVIzxeX8PvcA9lRaIVYwUqaoizR5k3Hauvx_ZDWUZqF07IPbJjjqHlMaEO1EUDFZSUC9HUjmkFAkdBeYFFNYcskHEqlY0PGcIBYDpMsYXTlS4QMyIGxEK7gc-VmqsQn0QBRru3X1aVUoNOzcSkZpTfX3ty43DrS93qUuqHG-0g2t65alTah1-Kp9ygM19K0Vl6MKLWcMeIO9usMom9HvQHi3v_Q5eEdUMIhib56nk1FumUefrnQYfXASqXq7Vvbq-080JVIv4muItpJ7SYrDuYC4yKzXXVN7w3zeYAEXEFKnbEGizAKK5majk-W-V9obRT5RSpXv8sj_0YNOAjwfjL1CP7ShAD7pNPbWDLcI6S2J58FmUPZt5uke0OvzLl1foV4O6fC4QliORUVQ6RiC_hkQEUIURaBByCa_RBt4CNJPCuhLBDEbLVtIDLnxLWzjEz7oGGTsaZymvNL-4lNUyEz_cy6hG6TfjcOdnIv53WSlBZ2z87vbS9BMTfLMlabN4OqyeYmU1gsZHhY_pRUzq138rFEl7enQD1cS73Vy4cTw9rDdqUrv8-DyBb6u8gsAZUiWos4ZarGMe0aXa-Dh4R1mx6hBwlygyJzxfCUUjzb2w9vCBU7Ju8iIARv4bnc5UL4pHi6sb2mrKE9-aCi4g.N4t3K3Jbtv0CI6p__rIPLDo2PvMt58LtBWr9CoEaDW8',
    '__Host-authjs.csrf-token' => '9de7f10a94c4674179d06414020eba8843216b75833ae36a27915ed81dbd59a1%7C87f7da0873c88d2bafaaf5d6858ce4c5a5f1a3147a006097a0747d75685c0a4d',
    '__Secure-authjs.callback-url' => 'https%3A%2F%2Fhashnode.com%2Fsettings%2Fdeveloper',
    'FPID' => 'FPID2.2.nBEu2xPWNxrJpsMeawrO5Vd%2BdOariCUwbuw2y1nDxgY%3D.1792657186',
    'FPLC' => '5K1TFfPb122Qln%2FDvz0IEe%2FvIgqWFqyjy1FRGjn%2BtZj97MbujvzMgwqzwsZkSm0hrBA6Ne4Gv%2Fo8Kft7Ux045ftSwdPVj8G2x4OHv2YdhPFsM4Alxf5HBSTdLy5AiQ%3D%3D',
    '_vcrcs' => '1.1792657185.3600.YWI1ODY5OTllYmRkNjI4YmI5MjRiMThiYmVmYmZkNmY=.1534b3d8e17b6b1ae731b1b1086887da'
];

$cookieStr = '';
foreach ($cookies as $k => $v) {
    $cookieStr .= "$k=$v; ";
}

// Check what GraphQL queries hashnode web UI uses
// Usually Hashnode web uses https://gql.hashnode.com or https://hashnode.com/api/graphql or api.hashnode.com
$tests = [
    'gql_with_cookie' => [
        'url' => 'https://gql.hashnode.com',
        'headers' => [
            'Content-Type: application/json',
            'Cookie: ' . $cookieStr,
            'Origin: https://hashnode.com',
            'Referer: https://hashnode.com/'
        ],
        'post' => json_encode(['query' => 'query { me { id username name publications(first: 5) { edges { node { id title } } } } }'])
    ],
    'main_graphql' => [
        'url' => 'https://hashnode.com/api/graphql',
        'headers' => [
            'Content-Type: application/json',
            'Cookie: ' . $cookieStr,
            'Origin: https://hashnode.com',
            'Referer: https://hashnode.com/'
        ],
        'post' => json_encode(['query' => 'query { me { id username name publications(first: 5) { edges { node { id title } } } } }'])
    ],
    'api_hashnode' => [
        'url' => 'https://api.hashnode.com',
        'headers' => [
            'Content-Type: application/json',
            'Cookie: ' . $cookieStr,
        ],
        'post' => json_encode(['query' => 'query { user(username: "dildar") { id username } }'])
    ],
    'session_check' => [
        'url' => 'https://hashnode.com/api/auth/session',
        'headers' => [
            'Cookie: ' . $cookieStr,
            'User-Agent: Mozilla/5.0'
        ],
        'post' => null
    ]
];

$out = [];
foreach ($tests as $key => $t) {
    $ch = curl_init($t['url']);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $t['headers'],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_FOLLOWLOCATION => false
    ]);
    if ($t['post']) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $t['post']);
    }
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $redirect = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
    curl_close($ch);
    $out[$key] = [
        'code' => $code,
        'redirect' => $redirect,
        'response' => substr($res, 0, 500)
    ];
}

header('Content-Type: application/json');
echo json_encode($out, JSON_PRETTY_PRINT);
