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
        83,
        $projectField,
    ];

    foreach (array_values(array_unique($displayFields)) as $index => $field) {
        $query['forcedisplay'][$index] = $field;
    }

    return $query;
}
