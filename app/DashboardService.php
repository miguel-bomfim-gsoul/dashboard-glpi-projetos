<?php

declare(strict_types=1);

function dashboard_projects(array $config): array
{
    $sessionToken = glpi_init_session($config);
    $rows = fetch_project_ticket_rows($config, $sessionToken);

    return array_map(
        static fn(array $row): array => map_ticket_to_project($row, $config),
        $rows
    );
}
