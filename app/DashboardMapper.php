<?php

declare(strict_types=1);

function map_ticket_to_project(array $ticket, array $config): array
{
    $projectTag = value_to_string($ticket[$config['glpi']['project_field']] ?? null);
    $statusId = value_to_string($ticket['12'] ?? '');
    $ticketId = value_to_string($ticket['2'] ?? '');
    $title = value_to_string($ticket['1'] ?? '');
    $openedAt = value_to_string($ticket[$config['glpi']['open_date_field']] ?? '');
    $solutionTime = value_to_string($ticket[$config['glpi']['solution_time_field']] ?? '');
    $assignees = resolve_ticket_users($ticket['5'] ?? null, $config['users']);
    if ($assignees === []) {
        $assignees = ['Não atribuído'];
    }

    return [
        'departamento' => value_to_string($ticket['80'] ?? '') ?: 'Sem entidade',
        'projeto' => $title ?: $config['glpi']['project_tag_label'],
        'responsavel' => implode(', ', $assignees),
        'responsaveis' => $assignees,
        'prazo' => format_date_br(value_to_string($ticket['19'] ?? '') ?: value_to_string($ticket['15'] ?? '')),
        'status' => dashboard_status($statusId),
        'progresso' => dashboard_progress($statusId, $openedAt, $solutionTime),
        'observacao' => trim('#' . $ticketId . ' - Etiqueta: ' . ($projectTag ?: $config['glpi']['project_tag_label'])),
    ];
}

function resolve_ticket_users($value, array $userNames): array
{
    $label = value_to_string($value);
    if ($label === '') {
        return [];
    }

    $names = [];
    foreach (preg_split('/\s*,\s*/', $label, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $part) {
        $part = trim($part);
        if ($part === '') {
            continue;
        }

        if (ctype_digit($part)) {
            if (isset($userNames[$part])) {
                $names[] = $userNames[$part];
            }
            continue;
        }

        $names[] = $part;
    }

    return array_values(array_unique(array_filter($names)));
}

function dashboard_status(string $statusId): string
{
    switch ($statusId) {
        case '1':
        case '2':
        case '3':
            return 'Em execucao';
        case '4':
            return 'Pendente';
        case '5':
        case '6':
            return 'Concluido';
        default:
            return 'Pendente';
    }
}

function dashboard_progress(string $statusId, string $openedAt, string $solutionTime): int
{
    if ($statusId === '5' || $statusId === '6') {
        return 100;
    }

    $openedAtTimestamp = glpi_date_timestamp($openedAt);
    $solutionTimeTimestamp = glpi_date_timestamp($solutionTime);

    if ($openedAtTimestamp === null || $solutionTimeTimestamp === null || $solutionTimeTimestamp <= $openedAtTimestamp) {
        return 0;
    }

    $elapsed = time() - $openedAtTimestamp;
    $total = $solutionTimeTimestamp - $openedAtTimestamp;

    if ($elapsed <= 0) {
        return 0;
    }

    return max(0, min(100, (int) round(($elapsed / $total) * 100)));
}

function glpi_date_timestamp(string $value)
{
    if ($value === '') {
        return null;
    }

    try {
        return (new DateTimeImmutable($value))->getTimestamp();
    } catch (Throwable $error) {
        return null;
    }
}
