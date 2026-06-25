<?php

declare(strict_types=1);

function dashboard_projects(array $config): array
{
    $sessionToken = glpi_init_session($config);
    $config = config_with_glpi_group_users($config, $sessionToken);
    $rows = fetch_project_ticket_rows($config, $sessionToken);

    return array_map(
        static function (array $row) use ($config): array {
            return map_ticket_to_project($row, $config);
        },
        $rows
    );
}
