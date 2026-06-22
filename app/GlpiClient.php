<?php

declare(strict_types=1);

function glpi_request(array $config, string $method, string $path, $query = null, $sessionToken = null): array
{
    $glpi = $config['glpi'];
    $url = $glpi['base_url'] . $path;
    if (is_array($query) && $query !== []) {
        $url .= '?' . http_build_query($query);
    }

    $headers = ['App-Token: ' . $glpi['app_token']];
    if ($sessionToken !== null) {
        $headers[] = 'Session-Token: ' . $sessionToken;
        $headers[] = 'Content-Type: application/json';
    } else {
        $headers[] = 'Authorization: user_token ' . $glpi['user_token'];
    }

    $curl = curl_init($url);
    curl_setopt_array($curl, [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => $glpi['ssl_verify'],
        CURLOPT_SSL_VERIFYHOST => $glpi['ssl_verify'] ? 2 : 0,
    ]);

    $body = curl_exec($curl);
    $statusCode = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    $error = curl_error($curl);
    curl_close($curl);

    if ($body === false || $error !== '') {
        throw new RuntimeException('Falha ao chamar API GLPI: ' . $error);
    }

    if ($statusCode >= 400) {
        throw new RuntimeException('API GLPI retornou HTTP ' . $statusCode . ': ' . $body);
    }

    $decoded = json_decode($body, true);
    return is_array($decoded) ? $decoded : [];
}

function glpi_init_session(array $config): string
{
    $response = glpi_request($config, 'GET', '/initSession');
    $sessionToken = $response['session_token'] ?? null;
    if (!is_string($sessionToken) || $sessionToken === '') {
        throw new RuntimeException('A API GLPI nao retornou session_token.');
    }

    return $sessionToken;
}
