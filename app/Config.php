<?php

declare(strict_types=1);

function load_config(string $envFile): array
{
    load_env_file($envFile);

    return [
        'timezone' => env_value('APP_TIMEZONE', 'America/Sao_Paulo'),
        'glpi' => [
            'base_url' => rtrim(env_value('GLPI_BASE_URL', 'https://chamados.bm3group.com.br/apirest.php'), '/'),
            'app_token' => env_value('GLPI_APP_TOKEN', ''),
            'user_token' => env_value('GLPI_USER_TOKEN', ''),
            'ssl_verify' => env_bool('GLPI_SSL_VERIFY', false),
            'page_size' => (int) env_value('GLPI_PAGE_SIZE', '50'),
            'project_field' => env_value('GLPI_PROJECT_FIELD', '10500'),
            'project_tag' => env_value('GLPI_PROJECT_TAG', '3'),
            'project_tag_label' => env_value('GLPI_PROJECT_TAG_LABEL', 'PROJETOS'),
        ],
        'users' => [
            '217' => 'Giovani Moras',
            '343' => 'Alexandre de Sá',
            '636' => 'Gabriel Wolker',
            '297' => 'Lucas Silveira',
            '374' => 'Augusto Gonçalves',
            '344' => 'Tiago Torquato',
            '587' => 'Matheus Monteiro',
            '656' => 'Miguel Bomfim',
            '382' => 'Patrick Schlemper',
            '405' => 'Daniel Floriano',
            '804' => 'Kauan Souza',
            '839' => 'Suyane Ferreira',
        ],
    ];
}

function load_env_file(string $path): void
{
    if (!is_file($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = array_map('trim', explode('=', $line, 2));
        if ($key !== '' && getenv($key) === false) {
            putenv($key . '=' . trim($value, "\"'"));
        }
    }
}

function env_value(string $key, string $default): string
{
    $value = getenv($key);
    return $value === false ? $default : $value;
}

function env_bool(string $key, bool $default): bool
{
    $value = getenv($key);
    return $value === false ? $default : filter_var($value, FILTER_VALIDATE_BOOLEAN);
}
