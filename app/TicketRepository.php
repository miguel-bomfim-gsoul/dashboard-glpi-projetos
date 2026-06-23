<?php

declare(strict_types=1);

function fetch_project_ticket_rows(array $config, string $sessionToken): array
{
    $tickets = [];
    $start = 0;
    $totalCount = null;
    $pageSize = max(1, (int) $config['glpi']['page_size']);

    do {
        $response = glpi_request(
            $config,
            'GET',
            '/search/Ticket',
            build_project_ticket_query($config, $start, $pageSize),
            $sessionToken
        );

        $totalCount ??= (int) ($response['totalcount'] ?? 0);
        $rows = is_array($response['data'] ?? null) ? $response['data'] : [];

        foreach ($rows as $row) {
            if (is_array($row) && value_to_string($row[$config['glpi']['project_field']] ?? null) !== '') {
                $tickets[] = $row;
            }
        }

        $start += $pageSize;
    } while ($start < $totalCount && count($rows) > 0);

    return enrich_ticket_categories($tickets, $config, $sessionToken);
}

function enrich_ticket_categories(array $tickets, array $config, string $sessionToken): array
{
    $categoryField = $config['glpi']['category_field'];
    $categoryIds = [];

    foreach ($tickets as $ticket) {
        $rawCategory = value_to_string($ticket[$categoryField] ?? '');
        if (ctype_digit($rawCategory)) {
            $categoryIds[$rawCategory] = true;
        }
    }

    if ($categoryIds === []) {
        return $tickets;
    }

    $categoryNames = [];
    foreach (array_keys($categoryIds) as $categoryId) {
        try {
            $category = glpi_request($config, 'GET', '/ITILCategory/' . $categoryId, null, $sessionToken);
            $categoryNames[$categoryId] = itil_category_name($category);
        } catch (Throwable $error) {
            $categoryNames[$categoryId] = '';
        }
    }

    foreach ($tickets as $index => $ticket) {
        $rawCategory = value_to_string($ticket[$categoryField] ?? '');
        if (isset($categoryNames[$rawCategory]) && $categoryNames[$rawCategory] !== '') {
            $tickets[$index]['_category_name'] = $categoryNames[$rawCategory];
        } else {
            $tickets[$index]['_category_name'] = $rawCategory;
        }
    }

    return $tickets;
}

function itil_category_name(array $category): string
{
    foreach (['name', 'completename'] as $field) {
        $value = value_to_string($category[$field] ?? '');
        if ($value !== '') {
            return $value;
        }
    }

    return '';
}

function fetch_ticket_tasks(array $config, string $sessionToken, string $ticketId): array
{
    if ($ticketId === '' || !ctype_digit($ticketId)) {
        return [];
    }

    $tasks = glpi_request($config, 'GET', '/Ticket/' . $ticketId . '/TicketTask', null, $sessionToken);
    return normalize_ticket_tasks($tasks);
}

function normalize_ticket_tasks(array $response): array
{
    $rows = is_array($response['data'] ?? null) ? $response['data'] : $response;
    $tasks = [];

    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }

        $content = ticket_task_content($row);
        if ($content === '') {
            continue;
        }

        $tasks[] = [
            'id' => value_to_string($row['id'] ?? ''),
            'conteudo' => $content,
            'autor' => ticket_task_user($row),
            'criadoEm' => format_date_br(value_to_string($row['date'] ?? ($row['date_creation'] ?? ''))),
            'atualizadoEm' => format_date_br(value_to_string($row['date_mod'] ?? '')),
            'status' => ticket_task_status(value_to_string($row['state'] ?? ($row['status'] ?? ''))),
        ];
    }

    return $tasks;
}

function ticket_task_content(array $task): string
{
    $raw = value_to_string($task['content'] ?? ($task['name'] ?? ''));
    $decoded = html_entity_decode($raw, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = trim(html_entity_decode(strip_tags($decoded), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    return preg_replace('/\s+/', ' ', $text) ?? $text;
}

function ticket_task_user(array $task): string
{
    foreach (['users_id', 'users_id_tech', 'users_id_editor'] as $field) {
        $value = value_to_string($task[$field] ?? '');
        if ($value !== '' && $value !== '0') {
            return $value;
        }
    }

    return 'Sem responsavel';
}

function ticket_task_status(string $state): string
{
    switch ($state) {
        case '1':
            return 'A fazer';
        case '2':
            return 'Feita';
        case '3':
            return 'Informacao';
        default:
            return $state !== '' ? $state : 'Sem status';
    }
}

function build_project_ticket_query(array $config, int $start, int $pageSize): array
{
    $projectField = $config['glpi']['project_field'];

    $query = [
        'criteria' => [
            [
                'link' => 'AND',
                'field' => $projectField,
                'searchtype' => 'equals',
                'value' => $config['glpi']['project_tag'],
            ],
            [
                'link' => 'AND',
                'field' => 12,
                'searchtype' => 'equals',
                'value' => 'notold',
            ],
        ],
        'range' => $start . '-' . ($start + $pageSize - 1),
        'expand_dropdowns' => true,
        'sort' => [19],
        'order' => ['DESC'],
    ];

    $displayFields = [
        1,
        2,
        3,
        4,
        5,
        $config['glpi']['category_field'],
        12,
        $config['glpi']['open_date_field'],
        $config['glpi']['solution_time_field'],
        19,
        80,
        $config['glpi']['location_field'],
        $projectField,
    ];

    foreach (array_values(array_unique($displayFields)) as $index => $field) {
        $query['forcedisplay'][$index] = $field;
    }

    return $query;
}
