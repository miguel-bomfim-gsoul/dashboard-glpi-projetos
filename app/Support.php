<?php

declare(strict_types=1);

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function render_view(string $view, array $data = []): void
{
    extract($data, EXTR_SKIP);
    require dirname(__DIR__) . '/views/' . $view . '.php';
}

function value_to_string(mixed $value): string
{
    if (is_array($value)) {
        if (isset($value['name'])) {
            return trim((string) $value['name']);
        }

        return trim(implode(', ', array_map(static fn(mixed $item): string => value_to_string($item), $value)));
    }

    return trim((string) $value);
}

function format_date_br(string $value): string
{
    if ($value === '') {
        return 'A definir';
    }

    try {
        return (new DateTimeImmutable($value))->format('d/m/Y');
    } catch (Throwable) {
        return $value;
    }
}
