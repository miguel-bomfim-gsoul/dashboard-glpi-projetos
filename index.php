<?php

declare(strict_types=1);

$config = require __DIR__ . '/app/bootstrap.php';

$projects = [];
$errorMessage = null;

try {
    $projects = dashboard_projects($config);
} catch (Throwable $exception) {
    $errorMessage = $exception->getMessage();
}

render_view('dashboard', [
    'projects' => $projects,
    'errorMessage' => $errorMessage,
    'updatedAt' => date('d/m/Y H:i'),
]);
