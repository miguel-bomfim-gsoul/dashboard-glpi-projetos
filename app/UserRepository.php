<?php

declare(strict_types=1);

function config_with_glpi_group_users(array $config, string $sessionToken): array
{
    $config['users'] = fetch_active_group_users($config, $sessionToken);
    return $config;
}

function fetch_active_group_users(array $config, string $sessionToken): array
{
    $groupId = value_to_string($config['glpi']['users_group_id'] ?? '');
    if ($groupId === '' || !ctype_digit($groupId)) {
        return [];
    }

    $users = [];
    foreach (fetch_group_user_rows($config, $sessionToken, $groupId) as $row) {
        $user = normalize_glpi_user($row, $config, $sessionToken);
        if ($user === null) {
            continue;
        }

        $users[$user['id']] = $user['name'];
    }

    natcasesort($users);
    return $users;
}

function fetch_group_user_rows(array $config, string $sessionToken, string $groupId): array
{
    foreach (['/Group/' . $groupId . '/User', '/Group/' . $groupId . '/Group_User'] as $path) {
        try {
            $rows = glpi_rows(glpi_request($config, 'GET', $path, null, $sessionToken));
            if ($rows !== []) {
                return $rows;
            }
        } catch (Throwable $error) {
            continue;
        }
    }

    return [];
}

function normalize_glpi_user(array $row, array $config, string $sessionToken)
{
    if (isset($row['users_id'])) {
        try {
            $row = glpi_request($config, 'GET', '/User/' . value_to_string($row['users_id']), null, $sessionToken);
        } catch (Throwable $error) {
            return null;
        }
    }

    if (!glpi_user_is_active($row)) {
        return null;
    }

    $id = value_to_string($row['users_id'] ?? ($row['id'] ?? ''));
    if ($id === '') {
        return null;
    }

    $name = glpi_user_display_name($row);
    if ($name === '') {
        return null;
    }

    return [
        'id' => $id,
        'name' => $name,
    ];
}

function glpi_rows(array $response): array
{
    $rows = is_array($response['data'] ?? null) ? $response['data'] : $response;
    return array_values(array_filter($rows, 'is_array'));
}

function glpi_user_is_active(array $user): bool
{
    $deleted = value_to_string($user['is_deleted'] ?? '0');
    if ($deleted !== '' && $deleted !== '0') {
        return false;
    }

    $active = value_to_string($user['is_active'] ?? '1');
    return $active === '' || $active === '1' || strtolower($active) === 'true';
}

function glpi_user_display_name(array $user): string
{
    $firstName = value_to_string($user['firstname'] ?? '');
    $lastName = value_to_string($user['realname'] ?? '');
    $fullName = trim($firstName . ' ' . $lastName);

    if ($fullName !== '') {
        return $fullName;
    }

    foreach (['name', 'completename'] as $field) {
        $value = value_to_string($user[$field] ?? '');
        if ($value !== '') {
            return $value;
        }
    }

    return '';
}
