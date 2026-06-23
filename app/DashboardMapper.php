<?php

declare(strict_types=1);

function map_ticket_to_project(array $ticket, array $config): array
{
    $projectTag = value_to_string($ticket[$config['glpi']['project_field']] ?? null);
    $statusId = value_to_string($ticket['12'] ?? '');
    $ticketId = value_to_string($ticket['2'] ?? '');
    $title = value_to_string($ticket['1'] ?? '');
    $category = value_to_string($ticket['_category_name'] ?? ($ticket[$config['glpi']['category_field']] ?? ''));
    $openedAt = value_to_string($ticket[$config['glpi']['open_date_field']] ?? '');
    $solutionTime = value_to_string($ticket[$config['glpi']['solution_time_field']] ?? '');
    $priority = ticket_priority_from_category($category);
    $progress = dashboard_progress($statusId, $openedAt, $solutionTime);
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
        'periodo_sla' => format_sla_period($openedAt, $solutionTime),
        'status' => dashboard_status($statusId),
        'progresso' => $progress,
        'progressoLabel' => dashboard_progress_label($progress, $openedAt, $solutionTime),
        'prioridade' => $priority['label'],
        'prioridadeFiltro' => $priority['filter'],
        'prioridadeClasse' => $priority['class'],
        'prioridadeOrdem' => $priority['order'],
        'observacao' => trim('#' . $ticketId . ' - Etiqueta: ' . ($projectTag ?: $config['glpi']['project_tag_label'])),
    ];
}

function ticket_priority_from_category(string $category): array
{
    $category = trim($category);

    if (!preg_match('/^\d+$/', $category)) {
        return [
            'label' => 'Sem prioridade',
            'filter' => 'Sem prioridade',
            'class' => 'priority-none',
            'order' => 999999,
        ];
    }

    $number = (int) $category;

    if ($number === 0) {
        return [
            'label' => 'Sem prioridade',
            'filter' => 'Sem prioridade',
            'class' => 'priority-none',
            'order' => 999999,
        ];
    }

    if ($number === 1) {
        return [
            'label' => 'Prioridade alta',
            'filter' => 'Alta',
            'class' => 'priority-1',
            'order' => $number,
        ];
    }

    return [
        'label' => 'Prioridade baixa',
        'filter' => 'Baixa',
        'class' => 'priority-other',
        'order' => $number,
    ];
}

function format_sla_period(string $openedAt, string $solutionTime): string
{
    $start = format_date_br($openedAt);
    $end = format_date_br($solutionTime);

    if ($start === 'A definir' && $end === 'A definir') {
        return 'SLA nao definido';
    }

    return $start . ' - ' . $end;
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
            return 'Em execução';
        case '4':
            return 'Pendente';
        case '5':
        case '6':
            return 'Concluído';
        default:
            return 'Pendente';
    }
}

function dashboard_progress(string $statusId, string $openedAt, string $solutionTime): float
{
    if ($statusId === '5' || $statusId === '6') {
        return 100.0;
    }

    $openedAtTimestamp = glpi_date_timestamp($openedAt);
    $solutionTimeTimestamp = glpi_date_timestamp($solutionTime);

    if ($openedAtTimestamp === null || $solutionTimeTimestamp === null || $solutionTimeTimestamp <= $openedAtTimestamp) {
        return 0.0;
    }

    $elapsed = time() - $openedAtTimestamp;
    $total = $solutionTimeTimestamp - $openedAtTimestamp;

    if ($elapsed <= 0) {
        return 0.0;
    }

    return max(0.0, min(100.0, round(($elapsed / $total) * 100, 2)));
}

function dashboard_progress_label(float $progress, string $openedAt, string $solutionTime): string
{
    if ($progress <= 0) {
        return '0%';
    }

    if ($progress < 1) {
        return number_format($progress, 2, ',', '') . '%';
    }

    if ($progress < 10) {
        return number_format($progress, 1, ',', '') . '%';
    }

    return number_format($progress, 0, ',', '') . '%';
}

function glpi_date_timestamp(string $value)
{
    if ($value === '') {
        return null;
    }

    $formats = [
        'Y-m-d H:i:s',
        'Y-m-d H:i',
        'Y-m-d',
        'd-m-Y H:i:s',
        'd-m-Y H:i',
        'd-m-Y',
        'd/m/Y H:i:s',
        'd/m/Y H:i',
        'd/m/Y',
    ];

    foreach ($formats as $format) {
        $date = DateTimeImmutable::createFromFormat($format, $value);
        if ($date instanceof DateTimeImmutable) {
            return $date->getTimestamp();
        }
    }

    try {
        return (new DateTimeImmutable($value))->getTimestamp();
    } catch (Throwable $error) {
        return null;
    }
}
