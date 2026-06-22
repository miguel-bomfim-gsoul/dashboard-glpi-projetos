<?php

declare(strict_types=1);

date_default_timezone_set('America/Sao_Paulo');

const GLPI_PAGE_SIZE = 50;
const GLPI_USER_NAMES = [
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
  '839' => 'Suyane Ferreira'
  // Adicione novos IDs aqui no formato: '123' => 'Nome do Usuario',
];

define('GLPI_BASE_URL', getenv('GLPI_BASE_URL') ?: 'https://chamados.bm3group.com.br/apirest.php');
define('GLPI_APP_TOKEN', getenv('GLPI_APP_TOKEN') ?: 'b98jUvA2vu9rW43hspxOuYDwZPYFEtW78tKDtM69');
define('GLPI_USER_TOKEN', getenv('GLPI_USER_TOKEN') ?: 'qZXkqOrKgiw9Fcr7svsXsGqGXM4OucaL60d0i5gh');
define('GLPI_SSL_VERIFY', filter_var(getenv('GLPI_SSL_VERIFY') ?: 'false', FILTER_VALIDATE_BOOLEAN));
define('GLPI_PROJECT_FIELD', getenv('GLPI_PROJECT_FIELD') ?: '10500');
define('GLPI_PROJECT_TAG', getenv('GLPI_PROJECT_TAG') ?: '3');
define('GLPI_PROJECT_TAG_LABEL', getenv('GLPI_PROJECT_TAG_LABEL') ?: 'PROJETOS');

$errorMessage = null;
$projects = [];

try {
  $projects = fetchProjectTickets();
} catch (Throwable $exception) {
  $errorMessage = $exception->getMessage();
}

function glpiRequest(string $method, string $url, array $headers, ?array $query = null): array
{
  if ($query) {
    $url .= '?' . http_build_query($query);
  }

  $curl = curl_init($url);
  curl_setopt_array($curl, [
    CURLOPT_CUSTOMREQUEST => $method,
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => GLPI_SSL_VERIFY,
    CURLOPT_SSL_VERIFYHOST => GLPI_SSL_VERIFY ? 2 : 0,
  ]);

  $body = curl_exec($curl);
  $statusCode = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
  $error = curl_error($curl);
  curl_close($curl);

  if ($body === false || $error !== '') {
    throw new RuntimeException('Falha ao chamar API GLPI: ' . $error);
  }

  $decoded = json_decode($body, true);
  if ($statusCode >= 400) {
    throw new RuntimeException('API GLPI retornou HTTP ' . $statusCode . ': ' . $body);
  }

  return is_array($decoded) ? $decoded : [];
}

function initGlpiSession(): string
{
  $response = glpiRequest('GET', GLPI_BASE_URL . '/initSession', [
    'App-Token: ' . GLPI_APP_TOKEN,
    'Authorization: user_token ' . GLPI_USER_TOKEN,
  ]);

  $sessionToken = $response['session_token'] ?? null;
  if (!is_string($sessionToken) || $sessionToken === '') {
    throw new RuntimeException('A API GLPI nao retornou session_token.');
  }

  return $sessionToken;
}

function fetchProjectTickets(): array
{
  $sessionToken = initGlpiSession();
  $tickets = [];
  $start = 0;
  $totalCount = null;

  do {
    $response = glpiRequest('GET', GLPI_BASE_URL . '/search/Ticket', [
      'App-Token: ' . GLPI_APP_TOKEN,
      'Session-Token: ' . $sessionToken,
      'Content-Type: application/json',
    ], buildTicketQuery($start));

    $totalCount ??= (int) ($response['totalcount'] ?? 0);
    $rows = is_array($response['data'] ?? null) ? $response['data'] : [];

    foreach ($rows as $row) {
      if (!is_array($row)) {
        continue;
      }

      $projectTag = glpiValue($row[GLPI_PROJECT_FIELD] ?? null);
      if ($projectTag === '') {
        continue;
      }

      $tickets[] = normalizeProjectTicket($row, $projectTag);
    }

    $start += GLPI_PAGE_SIZE;
  } while ($start < $totalCount && count($rows) > 0);

  return $tickets;
}

function buildTicketQuery(int $start): array
{
  $query = [
    'criteria' => [
      [
        'link' => 'AND',
        'field' => GLPI_PROJECT_FIELD,
        'searchtype' => 'equals',
        'value' => GLPI_PROJECT_TAG,
      ],
      [
        'link' => 'AND',
        'field' => 12,
        'searchtype' => 'equals',
        'value' => 'notold',
      ],
    ],
    'range' => $start . '-' . ($start + GLPI_PAGE_SIZE - 1),
    'expand_dropdowns' => true,
    'sort' => [19],
    'order' => ['DESC'],
  ];

  foreach ([1, 2, 3, 4, 5, 12, 15, 19, 80, 83, GLPI_PROJECT_FIELD] as $index => $field) {
    $query['forcedisplay'][$index] = $field;
  }

  return $query;
}

function normalizeProjectTicket(array $ticket, string $projectTag): array
{
  $statusId = (string) glpiValue($ticket['12'] ?? '');
  $ticketId = glpiValue($ticket['2'] ?? '');
  $title = glpiValue($ticket['1'] ?? '');
  $assignees = resolveTicketUsers($ticket['5'] ?? null);
  if ($assignees === []) {
    $assignees = ['Não atribuído'];
  }

  $assignee = implode(', ', $assignees);
  $entity = glpiValue($ticket['80'] ?? '') ?: 'Sem entidade';
  $updatedAt = glpiValue($ticket['19'] ?? '') ?: glpiValue($ticket['15'] ?? '');

  return [
    'departamento' => $entity,
    'projeto' => $title ?: GLPI_PROJECT_TAG_LABEL,
    'responsavel' => $assignee,
    'responsaveis' => $assignees,
    'prazo' => formatDateForDisplay($updatedAt),
    'status' => dashboardStatus($statusId),
    'progresso' => dashboardProgress($statusId),
    'observacao' => trim('#' . $ticketId . ' - Etiqueta: ' . ($projectTag ?: GLPI_PROJECT_TAG_LABEL)),
  ];
}

function resolveTicketUsers(mixed $value): array
{
  $label = glpiValue($value);
  if ($label === '') {
    return [];
  }

  $parts = preg_split('/\s*,\s*/', $label, -1, PREG_SPLIT_NO_EMPTY) ?: [];
  $names = [];

  foreach ($parts as $part) {
    $part = trim($part);
    if ($part === '') {
      continue;
    }

    if (ctype_digit($part)) {
      if (isset(GLPI_USER_NAMES[$part])) {
        $names[] = GLPI_USER_NAMES[$part];
      }
      continue;
    }

    $names[] = $part;
  }

  return array_values(array_unique(array_filter($names)));
}

function glpiValue(mixed $value): string
{
  if (is_array($value)) {
    if (isset($value['name'])) {
      return trim((string) $value['name']);
    }

    return trim(implode(', ', array_map(static fn($item): string => glpiValue($item), $value)));
  }

  return trim((string) $value);
}

function glpiId(mixed $value): string
{
  if (is_array($value)) {
    if (isset($value['id']) && ctype_digit((string) $value['id'])) {
      return (string) $value['id'];
    }

    foreach ($value as $item) {
      $id = glpiId($item);
      if ($id !== '') {
        return $id;
      }
    }
  }

  return ctype_digit((string) $value) ? (string) $value : '';
}

function dashboardStatus(string $statusId): string
{
  return match ($statusId) {
    '1', '2', '3' => 'Em execucao',
    '4' => 'Pendente',
    '5', '6' => 'Concluido',
    default => 'Pendente',
  };
}

function dashboardProgress(string $statusId): int
{
  return match ($statusId) {
    '1' => 10,
    '2' => 45,
    '3' => 65,
    '4' => 35,
    '5', '6' => 100,
    default => 0,
  };
}

function formatDateForDisplay(string $value): string
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

function e(string $value): string
{
  return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$projectsJson = json_encode($projects, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
if ($projectsJson === false) {
  $projectsJson = '[]';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard de Projetos em Execucao - BM3 Group</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --petrol-900: #00242C;
      --petrol-800: #003B49;
      --petrol-700: #0A5566;
      --teal-500: #1C8C99;
      --bg: #F2F5F6;
      --card: #FFFFFF;
      --ink: #16282C;
      --ink-soft: #516268;
      --border: #E1E7E8;
      --radius-lg: 18px;
      --radius-md: 12px;
      --radius-sm: 8px;
      --shadow-sm: 0 1px 2px rgba(0, 36, 44, 0.06), 0 1px 1px rgba(0, 36, 44, 0.04);
      --shadow-md: 0 8px 24px rgba(0, 36, 44, 0.08);
      --status-exec-bg: #E3F2F4;
      --status-exec-fg: #0A5566;
      --status-exec-dot: #1C8C99;
      --status-pend-bg: #FFF3DF;
      --status-pend-fg: #8A5800;
      --status-pend-dot: #E08E0B;
      --status-espera-bg: #ECEEEE;
      --status-espera-fg: #5C6566;
      --status-espera-dot: #9AA3A4;
      --status-concl-bg: #E5F6EC;
      --status-concl-fg: #1F7A43;
      --status-concl-dot: #2E9E5B;
      --status-atraso-bg: #FDEAEA;
      --status-atraso-fg: #B23030;
      --status-atraso-dot: #D64545;
    }

    * {
      box-sizing: border-box;
    }

    html,
    body {
      margin: 0;
      padding: 0;
    }

    body {
      font-family: 'Inter', system-ui, -apple-system, sans-serif;
      background: var(--bg);
      color: var(--ink);
      -webkit-font-smoothing: antialiased;
    }

    h1,
    h2,
    h3,
    .brand-title {
      font-family: 'Sora', system-ui, sans-serif;
    }

    header.topbar {
      background: linear-gradient(120deg, var(--petrol-900) 0%, var(--petrol-800) 55%, var(--petrol-700) 100%);
      color: #fff;
      padding: 28px clamp(16px, 4vw, 48px);
      box-shadow: var(--shadow-md);
      position: relative;
      overflow: hidden;
    }

    header.topbar::after {
      content: "";
      position: absolute;
      right: -80px;
      top: -80px;
      width: 260px;
      height: 260px;
      background: radial-gradient(circle, rgba(255, 255, 255, 0.06), transparent 70%);
      pointer-events: none;
    }

    .topbar-inner {
      max-width: 1400px;
      margin: 0 auto;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 24px;
      flex-wrap: wrap;
      position: relative;
      z-index: 1;
    }

    .brand-block {
      display: flex;
      align-items: center;
      gap: 20px;
    }

    .brand-mark {
      height: 64px;
      width: 64px;
      border: 1px solid rgba(255, 255, 255, 0.32);
      border-radius: 14px;
      display: grid;
      place-items: center;
      font-family: 'Sora', sans-serif;
      font-weight: 800;
      letter-spacing: -0.08em;
      background: rgba(255, 255, 255, 0.08);
    }

    .brand-divider {
      width: 1px;
      height: 48px;
      background: rgba(255, 255, 255, 0.25);
    }

    .brand-text h1 {
      margin: 0;
      font-size: clamp(20px, 2.4vw, 28px);
      font-weight: 700;
      letter-spacing: -0.01em;
    }

    .brand-text p {
      margin: 4px 0 0;
      font-size: 14px;
      color: rgba(255, 255, 255, 0.72);
      font-weight: 500;
    }

    .update-block {
      display: flex;
      flex-direction: column;
      align-items: flex-end;
      gap: 6px;
      color: rgba(255, 255, 255, 0.85);
      font-size: 13px;
    }

    .update-block label {
      text-transform: uppercase;
      letter-spacing: .06em;
      font-size: 11px;
      color: rgba(255, 255, 255, 0.55);
      font-weight: 600;
    }

    .update-pill {
      background: rgba(255, 255, 255, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.25);
      color: #fff;
      padding: 8px 12px;
      border-radius: var(--radius-sm);
      font-weight: 700;
    }

    main {
      max-width: 1400px;
      margin: 0 auto;
      padding: 28px clamp(16px, 4vw, 48px) 64px;
    }

    .alert {
      background: #fff6e5;
      border: 1px solid #f1c36b;
      color: #7a5100;
      border-radius: var(--radius-md);
      padding: 14px 16px;
      margin-bottom: 20px;
      font-weight: 600;
    }

    .summary-grid {
      display: grid;
      grid-template-columns: repeat(5, 1fr);
      gap: 16px;
      margin-bottom: 28px;
    }

    @media (max-width:1100px) {
      .summary-grid {
        grid-template-columns: repeat(3, 1fr);
      }
    }

    @media (max-width:640px) {
      .summary-grid {
        grid-template-columns: repeat(2, 1fr);
      }

      .topbar-inner {
        flex-direction: column;
        align-items: flex-start;
      }

      .update-block {
        align-items: flex-start;
      }
    }

    .summary-card {
      background: var(--card);
      border-radius: var(--radius-md);
      padding: 18px 18px 16px;
      box-shadow: var(--shadow-sm);
      border: 1px solid var(--border);
      border-top: 4px solid var(--accent, var(--petrol-700));
    }

    .summary-card .label {
      font-size: 12px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: .05em;
      color: var(--ink-soft);
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .summary-card .value {
      font-family: 'Sora', sans-serif;
      font-size: 32px;
      font-weight: 700;
      margin-top: 8px;
      color: var(--petrol-900);
      line-height: 1;
    }

    .summary-card .dot {
      width: 9px;
      height: 9px;
      border-radius: 50%;
      flex-shrink: 0;
    }

    .filters {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: var(--radius-md);
      padding: 16px 18px;
      display: flex;
      gap: 14px;
      flex-wrap: wrap;
      align-items: center;
      margin-bottom: 26px;
      box-shadow: var(--shadow-sm);
    }

    .filter-field {
      display: flex;
      flex-direction: column;
      gap: 6px;
      min-width: 180px;
    }

    .filter-field.search {
      flex: 1;
      min-width: 260px;
    }

    .filter-field label {
      font-size: 11px;
      text-transform: uppercase;
      letter-spacing: .05em;
      font-weight: 700;
      color: var(--ink-soft);
    }

    select,
    input {
      height: 40px;
      border: 1px solid var(--border);
      border-radius: var(--radius-sm);
      padding: 0 12px;
      background: #fff;
      color: var(--ink);
      font: inherit;
      outline: none;
    }

    select:focus,
    input:focus {
      border-color: var(--teal-500);
      box-shadow: 0 0 0 3px rgba(28, 140, 153, 0.12);
    }

    .filter-reset {
      height: 40px;
      align-self: end;
      border: none;
      background: var(--petrol-800);
      color: #fff;
      border-radius: var(--radius-sm);
      padding: 0 16px;
      font-weight: 700;
      cursor: pointer;
    }

    .results-count {
      margin: 0 0 18px;
      color: var(--ink-soft);
      font-size: 14px;
    }

    .cards-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 18px;
      margin-bottom: 34px;
    }

    @media (max-width:1100px) {
      .cards-grid {
        grid-template-columns: repeat(2, 1fr);
      }
    }

    @media (max-width:720px) {
      .cards-grid {
        grid-template-columns: 1fr;
      }
    }

    .project-card {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      padding: 20px;
      box-shadow: var(--shadow-sm);
      display: flex;
      flex-direction: column;
      gap: 14px;
    }

    .dept {
      font-size: 11px;
      text-transform: uppercase;
      letter-spacing: .08em;
      font-weight: 800;
      color: var(--petrol-700);
    }

    .card-row {
      display: flex;
      justify-content: space-between;
      gap: 12px;
      align-items: flex-start;
    }

    .pname {
      font-family: 'Sora', sans-serif;
      font-weight: 800;
      font-size: 18px;
      line-height: 1.25;
      color: var(--petrol-900);
    }

    .badge {
      display: inline-flex;
      align-items: center;
      gap: 7px;
      border-radius: 999px;
      padding: 7px 10px;
      font-size: 12px;
      font-weight: 800;
      white-space: nowrap;
    }

    .badge .dot {
      width: 8px;
      height: 8px;
      border-radius: 50%;
    }

    .badge.exec {
      background: var(--status-exec-bg);
      color: var(--status-exec-fg);
    }

    .badge.exec .dot {
      background: var(--status-exec-dot);
    }

    .badge.pend {
      background: var(--status-pend-bg);
      color: var(--status-pend-fg);
    }

    .badge.pend .dot {
      background: var(--status-pend-dot);
    }

    .badge.espera {
      background: var(--status-espera-bg);
      color: var(--status-espera-fg);
    }

    .badge.espera .dot {
      background: var(--status-espera-dot);
    }

    .badge.concl {
      background: var(--status-concl-bg);
      color: var(--status-concl-fg);
    }

    .badge.concl .dot {
      background: var(--status-concl-dot);
    }

    .badge.atraso {
      background: var(--status-atraso-bg);
      color: var(--status-atraso-fg);
    }

    .badge.atraso .dot {
      background: var(--status-atraso-dot);
    }

    .meta-line {
      display: flex;
      align-items: center;
      gap: 8px;
      color: var(--ink-soft);
      font-size: 13px;
    }

    .progress-wrap {
      display: flex;
      flex-direction: column;
      gap: 8px;
    }

    .progress-label {
      display: flex;
      justify-content: space-between;
      font-size: 12px;
      font-weight: 700;
      color: var(--ink-soft);
    }

    .progress-track {
      height: 9px;
      background: #ECF0F1;
      border-radius: 999px;
      overflow: hidden;
    }

    .progress-fill {
      height: 100%;
      border-radius: 999px;
      transition: width .3s ease;
    }

    .note {
      font-size: 13px;
      line-height: 1.45;
      color: var(--ink-soft);
      background: #F8FAFA;
      border-radius: var(--radius-sm);
      padding: 10px 12px;
    }

    .empty-state {
      background: var(--card);
      border: 1px dashed var(--border);
      border-radius: var(--radius-md);
      padding: 30px;
      text-align: center;
      color: var(--ink-soft);
    }

    .section-title {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin: 8px 0 14px;
    }

    .section-title h2 {
      margin: 0;
      font-size: 20px;
      color: var(--petrol-900);
    }

    .table-wrap {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: var(--radius-md);
      box-shadow: var(--shadow-sm);
      overflow: hidden;
    }

    .table-wrap-scroll {
      overflow-x: auto;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      min-width: 860px;
    }

    thead th {
      text-align: left;
      font-size: 11px;
      text-transform: uppercase;
      letter-spacing: .06em;
      color: var(--ink-soft);
      background: #F8FAFA;
      padding: 14px 16px;
      border-bottom: 1px solid var(--border);
    }

    tbody td {
      padding: 15px 16px;
      border-bottom: 1px solid var(--border);
      font-size: 14px;
      vertical-align: middle;
    }

    tbody tr:last-child td {
      border-bottom: none;
    }

    tbody tr:hover {
      background: #FAFCFC;
    }

    .table-progress {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .table-progress .progress-track {
      width: 90px;
    }

    .table-progress span {
      font-size: 12px;
      font-weight: 600;
      color: var(--ink-soft);
      min-width: 34px;
    }

    footer {
      text-align: center;
      padding: 24px;
      font-size: 12px;
      color: var(--ink-soft);
    }
  </style>
</head>

<body>
  <header class="topbar">
    <div class="topbar-inner">
      <div class="brand-block">
        <div class="brand-mark">BM3</div>
        <div class="brand-divider"></div>
        <div class="brand-text">
          <h1>Dashboard de Projetos em Execucao</h1>
          <p>Chamados GLPI com tag/campo de projeto preenchido</p>
        </div>
      </div>
      <div class="update-block">
        <label>Atualizado em</label>
        <div class="update-pill"><?= e(date('d/m/Y H:i')) ?></div>
      </div>
    </div>
  </header>
  <main>
    <?php if ($errorMessage): ?>
      <div class="alert">Nao foi possivel carregar os chamados do GLPI: <?= e($errorMessage) ?></div>
    <?php endif; ?>

    <section class="summary-grid" id="summaryGrid"></section>

    <section class="filters">
      <div class="filter-field">
        <label for="filtroDepartamento">Departamento</label>
        <select id="filtroDepartamento"></select>
      </div>
      <div class="filter-field">
        <label for="filtroStatus">Status</label>
        <select id="filtroStatus"></select>
      </div>
      <div class="filter-field">
        <label for="filtroResponsavel">Responsavel</label>
        <select id="filtroResponsavel"></select>
      </div>
      <div class="filter-field search">
        <label for="filtroBusca">Buscar por projeto ou responsavel</label>
        <input type="text" id="filtroBusca" placeholder="Ex.: WMS, BI, nome do tecnico...">
      </div>
      <button class="filter-reset" id="resetFiltros">Limpar filtros</button>
    </section>

    <p class="results-count" id="resultsCount"></p>
    <section class="cards-grid" id="cardsGrid"></section>

    <div class="section-title">
      <h2>Visao geral em tabela</h2>
    </div>
    <div class="table-wrap">
      <div class="table-wrap-scroll">
        <table>
          <thead>
            <tr>
              <th>Departamento</th>
              <th>Projeto</th>
              <th>Responsavel</th>
              <th>Status</th>
              <th>Progresso</th>
              <th>Ultima atualizacao</th>
              <th>Chamado</th>
            </tr>
          </thead>
          <tbody id="tableBody"></tbody>
        </table>
      </div>
    </div>
  </main>
  <footer>BM3 Group - Dashboard interno de acompanhamento de projetos</footer>
  <script>
    const projetos = <?= $projectsJson ?>;
    const STATUS_CONFIG = {
      "Em execucao": {
        badge: "exec",
        bar: "var(--status-exec-dot)"
      },
      "Pendente": {
        badge: "pend",
        bar: "var(--status-pend-dot)"
      },
      "Em espera": {
        badge: "espera",
        bar: "var(--status-espera-dot)"
      },
      "Concluido": {
        badge: "concl",
        bar: "var(--status-concl-dot)"
      },
      "Atrasado": {
        badge: "atraso",
        bar: "var(--status-atraso-dot)"
      }
    };
    const ICONS = {
      user: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>',
      calendar: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>'
    };
    const summaryGrid = document.getElementById('summaryGrid');
    const cardsGrid = document.getElementById('cardsGrid');
    const tableBody = document.getElementById('tableBody');
    const filtroDepto = document.getElementById('filtroDepartamento');
    const filtroStatus = document.getElementById('filtroStatus');
    const filtroResponsavel = document.getElementById('filtroResponsavel');
    const filtroBusca = document.getElementById('filtroBusca');
    const resetFiltros = document.getElementById('resetFiltros');
    const resultsCount = document.getElementById('resultsCount');

    function badgeClass(status) {
      return (STATUS_CONFIG[status] && STATUS_CONFIG[status].badge) || "espera";
    }

    function barColor(status) {
      return (STATUS_CONFIG[status] && STATUS_CONFIG[status].bar) || "var(--status-espera-dot)";
    }

    function escapeHtml(str) {
      return String(str ?? '').replace(/[&<>"']/g, c => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;'
      } [c]));
    }

    function renderSummary(lista) {
      const counts = {
        "Em execucao": 0,
        "Pendente": 0,
        "Em espera": 0,
        "Concluido": 0,
        "Atrasado": 0
      };
      lista.forEach(p => {
        if (counts[p.status] !== undefined) counts[p.status]++;
      });
      const cards = [{
          label: "Total de projetos",
          value: lista.length,
          accent: "var(--petrol-700)",
          dot: null
        },
        {
          label: "Em execucao",
          value: counts["Em execucao"],
          accent: "var(--status-exec-dot)",
          dot: "var(--status-exec-dot)"
        },
        {
          label: "Pendentes",
          value: counts["Pendente"],
          accent: "var(--status-pend-dot)",
          dot: "var(--status-pend-dot)"
        },
        {
          label: "Em espera",
          value: counts["Em espera"],
          accent: "var(--status-espera-dot)",
          dot: "var(--status-espera-dot)"
        },
        {
          label: "Concluidos",
          value: counts["Concluido"],
          accent: "var(--status-concl-dot)",
          dot: "var(--status-concl-dot)"
        }
      ];
      summaryGrid.innerHTML = cards.map(c => `
    <div class="summary-card" style="--accent:${c.accent}">
      <div class="label">${c.dot ? `<span class="dot" style="background:${c.dot}"></span>` : ''}${c.label}</div>
      <div class="value">${c.value}</div>
    </div>
  `).join('');
    }

    function popularFiltros() {
      const departamentos = [...new Set(projetos.map(p => p.departamento))].sort();
      const statusList = [...new Set(projetos.map(p => p.status))];
      const responsaveis = [...new Set(projetos.flatMap(p => Array.isArray(p.responsaveis) ? p.responsaveis : [p.responsavel]).filter(Boolean))].sort();
      filtroDepto.innerHTML = '<option value="todos">Todos os departamentos</option>' +
        departamentos.map(d => `<option value="${escapeHtml(d)}">${escapeHtml(d)}</option>`).join('');
      filtroStatus.innerHTML = '<option value="todos">Todos os status</option>' +
        statusList.map(s => `<option value="${escapeHtml(s)}">${escapeHtml(s)}</option>`).join('');
      filtroResponsavel.innerHTML = '<option value="todos">Todos os responsaveis</option>' +
        responsaveis.map(r => `<option value="${escapeHtml(r)}">${escapeHtml(r)}</option>`).join('');
    }

    function renderCards(lista) {
      if (lista.length === 0) {
        cardsGrid.innerHTML = `<div class="empty-state" style="grid-column:1/-1;">Nenhum chamado com tag de projeto encontrado.</div>`;
        return;
      }
      cardsGrid.innerHTML = lista.map(p => `
    <div class="project-card">
      <div class="dept">${escapeHtml(p.departamento)}</div>
      <div class="card-row">
        <div class="pname">${escapeHtml(p.projeto)}</div>
        <span class="badge ${badgeClass(p.status)}"><span class="dot"></span>${escapeHtml(p.status)}</span>
      </div>
      <div class="meta-line">${ICONS.user}${escapeHtml(p.responsavel)}</div>
      <div class="meta-line">${ICONS.calendar}Atualizado: ${escapeHtml(p.prazo)}</div>
      <div class="progress-wrap">
        <div class="progress-label"><span>Progresso estimado</span><span>${Number(p.progresso) || 0}%</span></div>
        <div class="progress-track"><div class="progress-fill" style="width:${Number(p.progresso) || 0}%; background:${barColor(p.status)}"></div></div>
      </div>
      <div class="note">${escapeHtml(p.observacao)}</div>
    </div>
  `).join('');
    }

    function renderTable(lista) {
      if (lista.length === 0) {
        tableBody.innerHTML = `<tr><td colspan="7" style="text-align:center;color:var(--ink-soft);padding:30px;">Nenhum chamado encontrado.</td></tr>`;
        return;
      }
      tableBody.innerHTML = lista.map(p => `
    <tr>
      <td>${escapeHtml(p.departamento)}</td>
      <td><strong>${escapeHtml(p.projeto)}</strong></td>
      <td>${escapeHtml(p.responsavel)}</td>
      <td><span class="badge ${badgeClass(p.status)}"><span class="dot"></span>${escapeHtml(p.status)}</span></td>
      <td><div class="table-progress"><div class="progress-track"><div class="progress-fill" style="width:${Number(p.progresso) || 0}%; background:${barColor(p.status)}"></div></div><span>${Number(p.progresso) || 0}%</span></div></td>
      <td>${escapeHtml(p.prazo)}</td>
      <td style="max-width:320px;">${escapeHtml(p.observacao)}</td>
    </tr>
  `).join('');
    }

    function aplicarFiltros() {
      const depto = filtroDepto.value;
      const status = filtroStatus.value;
      const responsavel = filtroResponsavel.value;
      const busca = filtroBusca.value.trim().toLowerCase();
      const filtrados = projetos.filter(p => {
        const okDepto = depto === 'todos' || p.departamento === depto;
        const okStatus = status === 'todos' || p.status === status;
        const nomesResponsaveis = Array.isArray(p.responsaveis) ? p.responsaveis : [p.responsavel];
        const okResponsavel = responsavel === 'todos' || nomesResponsaveis.includes(responsavel);
        const okBusca = !busca ||
          String(p.projeto).toLowerCase().includes(busca) ||
          String(p.responsavel).toLowerCase().includes(busca) ||
          String(p.observacao).toLowerCase().includes(busca);
        return okDepto && okStatus && okResponsavel && okBusca;
      });
      renderCards(filtrados);
      renderTable(filtrados);
      resultsCount.innerHTML = `Exibindo <strong>${filtrados.length}</strong> de <strong>${projetos.length}</strong> chamado(s) com projeto`;
    }
    filtroDepto.addEventListener('change', aplicarFiltros);
    filtroStatus.addEventListener('change', aplicarFiltros);
    filtroResponsavel.addEventListener('change', aplicarFiltros);
    filtroBusca.addEventListener('input', aplicarFiltros);
    resetFiltros.addEventListener('click', () => {
      filtroDepto.value = 'todos';
      filtroStatus.value = 'todos';
      filtroResponsavel.value = 'todos';
      filtroBusca.value = '';
      aplicarFiltros();
    });
    popularFiltros();
    renderSummary(projetos);
    renderCards(projetos);
    renderTable(projetos);
    resultsCount.innerHTML = `Exibindo <strong>${projetos.length}</strong> de <strong>${projetos.length}</strong> chamado(s) com projeto`;
  </script>
</body>

</html>