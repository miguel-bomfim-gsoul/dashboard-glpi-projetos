<?php

declare(strict_types=1);

$config = require __DIR__ . '/app/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

$ticketId = trim((string) ($_GET['id'] ?? ''));
if ($ticketId === '' || !ctype_digit($ticketId)) {
    http_response_code(400);
    echo json_encode(['error' => 'Chamado invalido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $sessionToken = glpi_init_session($config);
    $tasks = fetch_ticket_tasks($config, $sessionToken, $ticketId);

    echo json_encode([
        'ticketId' => $ticketId,
        'tasks' => map_ticket_tasks($tasks, $config),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode(['error' => $exception->getMessage()], JSON_UNESCAPED_UNICODE);
}
