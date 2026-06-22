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

    return $tickets;
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

    foreach ([1, 2, 3, 4, 5, 12, 15, 19, 80, 83, $projectField] as $index => $field) {
        $query['forcedisplay'][$index] = $field;
    }

    return $query;
}
