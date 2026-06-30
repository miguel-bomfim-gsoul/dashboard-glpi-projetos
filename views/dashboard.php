<?php

declare(strict_types=1);

$projectsJson = json_encode($projects ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
if ($projectsJson === false) {
  $projectsJson = '[]';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GLPI - Dashboard Projetos</title>
  <link rel="icon" type="image/png" href="/dashboard/public/assets/bm3group.png">
  <link rel="apple-touch-icon" href="/dashboard/public/assets/bm3group.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
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
      --shadow-sm: 0 1px 2px rgba(0, 36, 44, .06), 0 1px 1px rgba(0, 36, 44, .04);
      --shadow-md: 0 8px 24px rgba(0, 36, 44, .08);
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
      --priority-one: #00EF4B;
      --priority-other: #E4F83B;
      --priority-other-fg: #4D5A00;
      --priority-none: #A8B2B5;
      --priority-none-bg: #ECEEEE;
      --priority-none-fg: #5C6566;
    }

    * {
      box-sizing: border-box
    }

    html,
    body {
      margin: 0;
      padding: 0
    }

    body {
      font-family: Inter, system-ui, -apple-system, sans-serif;
      background: var(--bg);
      color: var(--ink)
    }

    h1,
    h2,
    .pname,
    .summary-card .value {
      font-family: Sora, system-ui, sans-serif
    }

    .topbar {
      background: linear-gradient(120deg, var(--petrol-900), var(--petrol-800) 55%, var(--petrol-700));
      color: #fff;
      padding: 28px clamp(16px, 4vw, 48px);
      box-shadow: var(--shadow-md)
    }

    .topbar-inner {
      max-width: 1400px;
      margin: 0 auto;
      display: flex;
      justify-content: space-between;
      gap: 24px;
      align-items: center;
      flex-wrap: wrap
    }

    .brand-block {
      display: flex;
      align-items: center;
      gap: 20px
    }

    .brand-mark {
      height: 64px;
      width: 86px;
      display: flex;
      align-items: center
    }

    .brand-mark img {
      display: block;
      width: 100%;
      height: 100%;
      object-fit: contain
    }

    .brand-divider {
      width: 1px;
      height: 48px;
      background: rgba(255, 255, 255, .25)
    }

    .brand-text h1 {
      margin: 0;
      font-size: clamp(20px, 2.4vw, 28px)
    }

    .brand-text p {
      margin: 4px 0 0;
      color: rgba(255, 255, 255, .72);
      font-weight: 600
    }

    .update-block {
      text-align: right
    }

    .update-block label {
      display: block;
      text-transform: uppercase;
      font-size: 11px;
      color: rgba(255, 255, 255, .58);
      font-weight: 800;
      letter-spacing: .06em;
      margin-bottom: 6px
    }

    .update-pill {
      background: rgba(255, 255, 255, .1);
      border: 1px solid rgba(255, 255, 255, .25);
      padding: 8px 12px;
      font-weight: 800
    }

    main {
      max-width: 1400px;
      margin: 0 auto;
      padding: 28px 0;
    }

    .alert {
      background: #fff6e5;
      border: 1px solid #f1c36b;
      color: #7a5100;
      border-radius: var(--radius-md);
      padding: 14px 16px;
      margin-bottom: 20px;
      font-weight: 700
    }

    .summary-grid {
      display: grid;
      grid-template-columns: repeat(5, 1fr);
      gap: 16px;
      margin-bottom: 28px
    }

    .summary-card {
      background: var(--card);
      border: 1px solid var(--border);
      border-top: 4px solid var(--accent, var(--petrol-700));
      padding: 18px;
      box-shadow: var(--shadow-sm);
      cursor: pointer;
      text-align: left;
      transition: background .16s ease, border-color .16s ease, box-shadow .16s ease, transform .16s ease;
      width: 100%;
      appearance: none;
      font: inherit
    }

    .summary-card:hover {
      transform: translateY(-1px);
      box-shadow: var(--shadow-md)
    }

    .summary-card.active {
      background: color-mix(in srgb, var(--accent, var(--petrol-700)) 12%, #fff);
      border-color: var(--accent, var(--petrol-700));
      box-shadow: 0 0 0 3px color-mix(in srgb, var(--accent, var(--petrol-700)) 18%, transparent), var(--shadow-sm)
    }

    .summary-card.disabled {
      cursor: not-allowed;
      opacity: .55;
      transform: none;
      box-shadow: var(--shadow-sm)
    }

    .summary-card .label {
      font-size: 12px;
      font-weight: 800;
      text-transform: uppercase;
      color: var(--ink-soft);
      display: flex;
      align-items: center;
      gap: 7px;
      letter-spacing: .05em
    }

    .summary-card .dot {
      width: 9px;
      height: 9px;
      border-radius: 50%
    }

    .summary-card .value {
      font-size: 32px;
      font-weight: 800;
      margin-top: 8px;
      color: var(--petrol-900)
    }

    .filters {
      background: #fff;
      border: 1px solid var(--border);
      border-radius: var(--radius-md);
      padding: 16px 18px;
      display: flex;
      gap: 14px;
      flex-wrap: wrap;
      align-items: end;
      margin-bottom: 26px;
      box-shadow: var(--shadow-sm)
    }

    .filter-field {
      display: flex;
      flex-direction: column;
      gap: 6px;
      min-width: 180px
    }

    .filter-field.search {
      flex: 1;
      min-width: 260px
    }

    .filter-field label {
      font-size: 11px;
      text-transform: uppercase;
      font-weight: 800;
      color: var(--ink-soft);
      letter-spacing: .05em
    }

    select,
    input {
      height: 40px;
      border: 1px solid var(--border);
      border-radius: var(--radius-sm);
      padding: 0 12px;
      background: #fff;
      color: var(--ink);
      font: inherit
    }

    select:focus,
    input:focus {
      outline: none;
      border-color: var(--teal-500);
      box-shadow: 0 0 0 3px rgba(28, 140, 153, .12)
    }

    .filter-reset {
      height: 40px;
      border: 0;
      background: var(--petrol-800);
      color: #fff;
      border-radius: var(--radius-sm);
      padding: 0 16px;
      font-weight: 800;
      cursor: pointer
    }

    .results-count {
      color: var(--ink-soft);
      font-size: 14px
    }

    .cards-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 18px;
      margin: 18px 0 34px
    }

    .project-card {
      background: #fff;
      border: 1px solid var(--border);
      padding: 20px;
      box-shadow: var(--shadow-sm);
      display: flex;
      flex-direction: column;
      gap: 14px
    }

    .project-card.priority-1 {
      border-color: var(--priority-one);
      box-shadow: inset 0 0 0 1px var(--priority-one), var(--shadow-sm)
    }

    .project-card.priority-other {
      border-color: var(--priority-other);
      box-shadow: inset 0 0 0 1px var(--priority-other), var(--shadow-sm)
    }

    .project-card.priority-none {
      border-color: var(--priority-none);
      box-shadow: inset 0 0 0 1px var(--priority-none), var(--shadow-sm)
    }

    .project-card.overdue {
      border-color: var(--status-atraso-dot);
      box-shadow: inset 0 0 0 1px var(--status-atraso-dot), var(--shadow-sm)
    }

    .dept {
      font-size: 11px;
      text-transform: uppercase;
      letter-spacing: .08em;
      font-weight: 800;
      color: var(--petrol-700)
    }

    .card-row {
      display: flex;
      justify-content: space-between;
      gap: 12px
    }

    .pname {
      font-weight: 800;
      font-size: 18px;
      line-height: 1.25;
      color: var(--petrol-900)
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
      max-height: 35px;
    }

    .badge .dot {
      width: 8px;
      height: 8px;
      border-radius: 50%
    }

    .badge.exec {
      background: var(--status-exec-bg);
      color: var(--status-exec-fg)
    }

    .badge.exec .dot {
      background: var(--status-exec-dot)
    }

    .badge.pend {
      background: var(--status-pend-bg);
      color: var(--status-pend-fg)
    }

    .badge.pend .dot {
      background: var(--status-pend-dot)
    }

    .badge.espera {
      background: var(--status-espera-bg);
      color: var(--status-espera-fg)
    }

    .badge.espera .dot {
      background: var(--status-espera-dot)
    }

    .badge.concl {
      background: var(--status-concl-bg);
      color: var(--status-concl-fg)
    }

    .badge.concl .dot {
      background: var(--status-concl-dot)
    }

    .meta-line {
      display: flex;
      align-items: center;
      gap: 8px;
      color: var(--ink-soft);
      font-size: 13px
    }

    .progress-label {
      display: flex;
      justify-content: space-between;
      font-size: 12px;
      font-weight: 800;
      color: var(--ink-soft);
      margin-bottom: 8px
    }

    .progress-track {
      height: 9px;
      background: #ECF0F1;
      border-radius: 999px;
      overflow: hidden
    }

    .progress-fill {
      height: 100%;
      border-radius: 999px
    }

    .note {
      font-size: 13px;
      color: var(--ink-soft);
      background: #F8FAFA;
      border-radius: var(--radius-sm);
      padding: 10px 12px
    }

    .priority-chip {
      align-self: flex-start;
      border-radius: var(--radius-sm);
      padding: 10px 12px;
      font-size: 12px;
      font-weight: 800
    }

    .priority-chip.priority-1 {
      background: var(--priority-one);
      color: var(--petrol-900)
    }

    .priority-chip.priority-other {
      background: var(--priority-other);
      color: var(--priority-other-fg)
    }

    .priority-chip.priority-none {
      background: var(--priority-none-bg);
      color: var(--priority-none-fg)
    }

    .task-toggle {
      border: 1px solid var(--border);
      background: #fff;
      color: var(--petrol-800);
      border-radius: var(--radius-sm);
      padding: 8px 10px;
      font: inherit;
      font-size: 12px;
      font-weight: 800;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 7px;
      align-self: flex-start
    }

    .task-toggle:hover,
    .task-toggle[aria-expanded="true"] {
      border-color: var(--teal-500);
      background: #EEF8F9
    }

    .task-empty {
      border-radius: var(--radius-sm);
      background: var(--priority-none-bg);
      color: var(--priority-none-fg);
      padding: 8px 10px;
      font-size: 12px;
      font-weight: 800;
      align-self: flex-start;
      display: inline-flex
    }

    .task-caret {
      font-size: 11px;
      line-height: 1
    }

    .task-panel {
      display: none;
      border: 1px solid var(--border);
      background: #FBFCFC;
      border-radius: var(--radius-sm);
      padding: 10px;
      margin-top: -4px
    }

    .task-panel.open {
      display: block
    }

    .task-list {
      display: grid;
      gap: 10px
    }

    .task-item {
      --task-accent: var(--teal-500);
      --task-bg: #fff;
      --task-soft: #EEF8F9;
      border: 1px solid var(--border);
      border-left: 4px solid var(--task-accent);
      border-radius: var(--radius-sm);
      background: #fff;
      padding: 11px 12px;
      box-shadow: var(--shadow-sm);
      display: grid;
      grid-template-columns: auto 1fr;
      gap: 10px;
      align-items: start
    }

    .task-item.done {
      --task-accent: #2E9E5B;
      --task-soft: #E5F6EC
    }

    .task-item.todo {
      --task-accent: #8A5800;
      --task-soft: #FFF3DF
    }

    .task-icon {
      width: 26px;
      height: 26px;
      border-radius: 999px;
      background: var(--task-soft);
      color: var(--task-accent);
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-weight: 900;
      line-height: 1;
      flex: 0 0 auto
    }

    .task-title {
      font-size: 13px;
      color: var(--ink);
      font-weight: 700;
      line-height: 1.35
    }

    .task-status {
      border-radius: 999px;
      background: var(--task-soft);
      color: var(--task-accent);
      padding: 3px 8px;
      font-size: 11px;
      font-weight: 800
    }

    .task-meta {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      margin-top: 8px;
      color: var(--ink-soft);
      font-size: 11px;
      font-weight: 700;
      align-items: center
    }

    .table-task-toggle {
      white-space: nowrap
    }

    .task-row td {
      padding: 0 16px 16px;
      background: #FBFCFC
    }

    .task-row .task-panel {
      display: block;
      margin-top: 0
    }

    .priority-dot {
      display: inline-block;
      width: 9px;
      height: 9px;
      border-radius: 50%;
      margin-right: 8px;
      vertical-align: middle
    }

    .priority-dot.priority-1 {
      background: var(--priority-one)
    }

    .priority-dot.priority-other {
      background: var(--priority-other)
    }

    .priority-dot.priority-none {
      background: var(--priority-none)
    }

    .empty-state {
      background: #fff;
      border: 1px dashed var(--border);
      border-radius: var(--radius-md);
      padding: 30px;
      text-align: center;
      color: var(--ink-soft)
    }

    .section-title h2 {
      font-size: 20px
    }

    .table-wrap {
      background: #fff;
      border: 1px solid var(--border);
      border-radius: var(--radius-md);
      box-shadow: var(--shadow-sm);
      overflow: hidden
    }

    .table-wrap-scroll {
      overflow-x: auto
    }

    table {
      width: 100%;
      border-collapse: collapse;
      min-width: 860px
    }

    th {
      font-size: 11px;
      text-align: left;
      text-transform: uppercase;
      letter-spacing: .06em;
      color: var(--ink-soft);
      background: #F8FAFA;
      padding: 14px 16px;
      border-bottom: 1px solid var(--border)
    }

    td {
      padding: 15px 16px;
      border-bottom: 1px solid var(--border);
      font-size: 14px
    }

    .table-progress {
      display: flex;
      align-items: center;
      gap: 10px
    }

    .table-progress .progress-track {
      width: 90px
    }

    footer {
      text-align: center;
      padding: 24px;
      font-size: 12px;
      color: var(--ink-soft)
    }

    @media(max-width:1100px) {
      .summary-grid {
        grid-template-columns: repeat(3, 1fr)
      }

      .cards-grid {
        grid-template-columns: repeat(2, 1fr)
      }
    }

    @media(max-width:720px) {
      .cards-grid {
        grid-template-columns: 1fr
      }

      .summary-grid {
        grid-template-columns: repeat(2, 1fr)
      }

      .topbar-inner {
        align-items: flex-start
      }

      .update-block {
        text-align: left
      }
    }
  </style>
</head>

<body>
  <header class="topbar">
    <div class="topbar-inner">
      <div class="brand-block">
        <div class="brand-mark"><img src="/dashboard/public/assets/bm3group.png" alt="BM3 Group Logo"></div>
        <div class="brand-divider"></div>
        <div class="brand-text">
          <h1>Dashboard de Projetos em Execução</h1>
          <p>Chamados GLPI com tag/campo de projeto preenchido</p>
        </div>
      </div>
      <div class="update-block">
        <label>Atualizado em</label>
        <div class="update-pill"><?= e((string) $updatedAt) ?></div>
      </div>
    </div>
  </header>
  <main>
    <?php if (!empty($errorMessage)): ?>
      <div class="alert">Nao foi possivel carregar os chamados do GLPI: <?= e((string) $errorMessage) ?></div>
    <?php endif; ?>

    <section class="summary-grid" id="summaryGrid"></section>
    <section class="filters">
      <div class="filter-field"><label for="filtroDepartamento">Departamento</label><select id="filtroDepartamento"></select></div>
      <div class="filter-field"><label for="filtroStatus">Status</label><select id="filtroStatus"></select></div>
      <div class="filter-field"><label for="filtroResponsavel">Responsável</label><select id="filtroResponsavel"></select></div>
      <div class="filter-field"><label for="filtroPrioridade">Prioridade</label><select id="filtroPrioridade"></select></div>
      <div class="filter-field search"><label for="filtroBusca">Buscar por projeto ou responsável</label><input id="filtroBusca" type="text" placeholder="Ex.: WMS, BI, nome do tecnico..."></div>
      <button class="filter-reset" id="resetFiltros">Limpar filtros</button>
    </section>
    <p class="results-count" id="resultsCount"></p>
    <section class="cards-grid" id="cardsGrid"></section>
    <div class="section-title">
      <h2>Visão geral em tabela</h2>
    </div>
    <div class="table-wrap">
      <div class="table-wrap-scroll">
        <table>
          <thead>
            <tr>
              <th>Departamento</th>
              <th>Projeto</th>
              <th>Responsável</th>
              <th>Status</th>
              <th>Prazo</th>
              <th>Abertura / solução</th>
              <th>Chamado</th>
              <th>Tarefas</th>
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
      "Em execução": {
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
      "Concluído": {
        badge: "concl",
        bar: "var(--status-concl-dot)"
      },
      "Atrasado": {
        badge: "atraso",
        bar: "var(--status-atraso-dot)"
      }
    };
    const ICONS = {
      user: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>',
      calendar: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>'
    };
    const summaryGrid = document.getElementById('summaryGrid');
    const cardsGrid = document.getElementById('cardsGrid');
    const tableBody = document.getElementById('tableBody');
    const filtroDepto = document.getElementById('filtroDepartamento');
    const filtroStatus = document.getElementById('filtroStatus');
    const filtroResponsavel = document.getElementById('filtroResponsavel');
    const filtroPrioridade = document.getElementById('filtroPrioridade');
    const filtroBusca = document.getElementById('filtroBusca');
    const resetFiltros = document.getElementById('resetFiltros');
    const resultsCount = document.getElementById('resultsCount');
    const expandedTableTasks = new Set();
    const taskCache = {};
    const filtros = {
      departamento: 'todos',
      status: 'Em execução',
      responsavel: 'todos',
      prioridade: 'todos',
      busca: ''
    };
    const escapeHtml = str => String(str ?? '').replace(/[&<>"']/g, c => ({
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#39;'
    } [c]));
    const badgeClass = status => (STATUS_CONFIG[status] && STATUS_CONFIG[status].badge) || "espera";
    const barColor = status => (STATUS_CONFIG[status] && STATUS_CONFIG[status].bar) || "var(--status-espera-dot)";
    const progressLabel = project => project.progressoLabel || `${Number(project.progresso) || 0}%`;
    const progressWidth = project => {
      const value = Number(project.progresso) || 0;
      return value > 0 && value < 1 ? 1 : value;
    };

    function responsibleNames(project) {
      return Array.isArray(project.responsaveis) ? project.responsaveis : [project.responsavel];
    }

    function priorityOrder(project) {
      const order = Number(project.prioridadeOrdem);
      return Number.isFinite(order) && order > 0 ? order : Number.MAX_SAFE_INTEGER;
    }

    function projectKey(project) {
      return String(project.chamadoId || project.observacao || project.projeto);
    }

    function projectTasks(project) {
      const cache = taskCache[projectKey(project)];
      return cache && Array.isArray(cache.tasks) ? cache.tasks : [];
    }

    function taskState(project) {
      return taskCache[projectKey(project)] || {
        status: 'idle',
        tasks: []
      };
    }

    function taskStatusClass(task) {
      return String(task.status).toLowerCase() === 'feita' ? 'done' : 'todo';
    }

    function taskStatusIcon(task) {
      return String(task.status).toLowerCase() === 'feita' ? '✓' : '!';
    }

    function renderTaskItems(project) {
      const state = taskState(project);
      if (state.status === 'loading') {
        return '<div class="empty-state" style="padding:14px;">Carregando tarefas...</div>';
      }

      if (state.status === 'error') {
        return `<div class="empty-state" style="padding:14px;">${escapeHtml(state.message || 'Nao foi possivel carregar as tarefas.')}</div>`;
      }

      const tasks = state.tasks;
      if (tasks.length === 0) {
        return '<div class="empty-state" style="padding:14px;">Nenhuma tarefa encontrada neste chamado.</div>';
      }

      return `<div class="task-list">${tasks.map(task => `<div class="task-item ${taskStatusClass(task)}"><span class="task-icon">${taskStatusIcon(task)}</span><div><div class="task-title">${escapeHtml(task.conteudo)}</div><div class="task-meta"><span>${escapeHtml(task.autor)}</span><span class="task-status">${escapeHtml(task.status)}</span><span>Prazo: ${escapeHtml(task.criadoEm || 'A definir')}</span></div></div></div>`).join('')}</div>`;
    }

    function renderTaskToggle(project, extraClass = '') {
      const key = projectKey(project);
      const state = taskState(project);
      const count = state.tasks.length;
      const open = expandedTableTasks.has(key);
      let label = 'Tarefas';

      if (state.status === 'loading') {
        label = 'Carregando...';
      } else if (state.status === 'loaded') {
        label = count === 0 ? 'Sem tarefas' : (count === 1 ? '1 tarefa' : `${count} tarefas`);
      } else if (state.status === 'error') {
        label = 'Erro ao carregar';
      }

      return `<button type="button" class="task-toggle ${extraClass}" data-task-key="${escapeHtml(key)}" aria-expanded="${open ? 'true' : 'false'}"><span class="task-caret">${open ? '▲' : '▼'}</span>${label}</button>`;
    }

    async function loadTasks(project) {
      const key = projectKey(project);
      const state = taskState(project);
      if (state.status === 'loading' || state.status === 'loaded') return;

      taskCache[key] = {
        status: 'loading',
        tasks: []
      };
      aplicarFiltros();

      try {
        const response = await fetch(`tasks.php?id=${encodeURIComponent(project.chamadoId)}`, {
          headers: {
            'Accept': 'application/json'
          }
        });
        const payload = await response.json();

        if (!response.ok) {
          throw new Error(payload.error || 'Nao foi possivel carregar as tarefas.');
        }

        taskCache[key] = {
          status: 'loaded',
          tasks: Array.isArray(payload.tasks) ? payload.tasks : []
        };
      } catch (error) {
        taskCache[key] = {
          status: 'error',
          tasks: [],
          message: error.message || 'Nao foi possivel carregar as tarefas.'
        };
      }

      aplicarFiltros();
    }

    function matchesSearch(project) {
      if (!filtros.busca) return true;
      return String(project.projeto).toLowerCase().includes(filtros.busca) ||
        String(project.responsavel).toLowerCase().includes(filtros.busca) ||
        String(project.observacao).toLowerCase().includes(filtros.busca);
    }

    function filteredProjects(overrides = {}) {
      const active = {
        ...filtros,
        ...overrides
      };
      return projetos.filter(project => {
        const nomes = responsibleNames(project);
        const okBusca = !active.busca ||
          String(project.projeto).toLowerCase().includes(active.busca) ||
          String(project.responsavel).toLowerCase().includes(active.busca) ||
          String(project.observacao).toLowerCase().includes(active.busca);

        return (active.departamento === 'todos' || project.departamento === active.departamento) &&
          (active.status === 'todos' || project.status === active.status) &&
          (active.responsavel === 'todos' || nomes.includes(active.responsavel)) &&
          (active.prioridade === 'todos' || project.prioridadeFiltro === active.prioridade) &&
          okBusca;
      }).sort((a, b) => priorityOrder(a) - priorityOrder(b) ||
        String(a.departamento).localeCompare(String(b.departamento), 'pt-BR') ||
        String(a.projeto).localeCompare(String(b.projeto), 'pt-BR'));
    }

    function renderSummary(lista) {
      const counts = {
        "Em execução": 0,
        "Pendente": 0,
        "Em espera": 0,
        "Concluído": 0,
        "Atrasado": 0
      };
      lista.forEach(p => {
        if (counts[p.status] !== undefined) counts[p.status]++;
      });
      const cards = [
        ["Total de projetos", lista.length, "var(--petrol-700)", null, "todos"],
        ["Em execução", counts["Em execução"], "var(--status-exec-dot)", "var(--status-exec-dot)", "Em execução"],
        ["Pendentes", counts["Pendente"], "var(--status-pend-dot)", "var(--status-pend-dot)", "Pendente"],
        ["Em espera", counts["Em espera"], "var(--status-espera-dot)", "var(--status-espera-dot)", "Em espera"],
        ["Concluídos", counts["Concluído"], "var(--status-concl-dot)", "var(--status-concl-dot)", "Concluído"]
      ];
      summaryGrid.innerHTML = cards.map(([label, value, accent, dot, status]) => {
        const active = filtros.status === status || (status === 'todos' && filtros.status === 'todos');
        const disabled = value === 0 && status !== 'todos';
        return `<button type="button" class="summary-card ${active ? 'active' : ''} ${disabled ? 'disabled' : ''}" style="--accent:${accent}" data-status="${escapeHtml(status)}" ${disabled ? 'disabled' : ''}><div class="label">${dot ? `<span class="dot" style="background:${dot}"></span>` : ''}${label}</div><div class="value">${value}</div></button>`;
      }).join('');
    }

    function renderSelect(select, options, selected, allLabel) {
      const values = ['todos', ...options];
      select.innerHTML = values.map(value => {
        const label = value === 'todos' ? allLabel : value;
        return `<option value="${escapeHtml(value)}" ${value === selected ? 'selected' : ''}>${escapeHtml(label)}</option>`;
      }).join('');
    }

    function updateFilterOptions() {
      const departamentos = [...new Set(filteredProjects({
        departamento: 'todos'
      }).map(p => p.departamento))].sort();
      const statusList = [...new Set(filteredProjects({
        status: 'todos'
      }).map(p => p.status))].sort();
      const responsaveis = [...new Set(filteredProjects({
        responsavel: 'todos'
      }).flatMap(responsibleNames).filter(Boolean))].sort();
      const prioridades = [...new Set(filteredProjects({
        prioridade: 'todos'
      }).map(p => p.prioridadeFiltro).filter(Boolean))].sort();

      if (filtros.departamento !== 'todos' && !departamentos.includes(filtros.departamento)) filtros.departamento = 'todos';
      if (filtros.status !== 'todos' && !statusList.includes(filtros.status)) filtros.status = 'todos';
      if (filtros.responsavel !== 'todos' && !responsaveis.includes(filtros.responsavel)) filtros.responsavel = 'todos';
      if (filtros.prioridade !== 'todos' && !prioridades.includes(filtros.prioridade)) filtros.prioridade = 'todos';

      renderSelect(filtroDepto, departamentos, filtros.departamento, 'Todas as departamentos');
      renderSelect(filtroStatus, statusList, filtros.status, 'Todos os status');
      renderSelect(filtroResponsavel, responsaveis, filtros.responsavel, 'Todos os responsaveis');
      renderSelect(filtroPrioridade, prioridades, filtros.prioridade, 'Todas as prioridades');
    }

    function renderCards(lista) {
      if (lista.length === 0) {
        cardsGrid.innerHTML = '<div class="empty-state" style="grid-column:1/-1;">Nenhum chamado com tag de projeto encontrado.</div>';
        return;
      }
      cardsGrid.innerHTML = lista.map(p => {
        const overdueClass = p.prazoVencido ? 'overdue' : '';
        return `<div class="project-card ${escapeHtml(p.prioridadeClasse)} ${overdueClass}"><div class="dept">${escapeHtml(p.departamento)}</div><div class="card-row"><div class="pname">${escapeHtml(p.projeto)}</div><span class="badge ${badgeClass(p.status)}"><span class="dot"></span>${escapeHtml(p.status)}</span></div><div class="meta-line">${ICONS.user}${escapeHtml(p.responsavel)}</div><div class="meta-line">${ICONS.calendar}${escapeHtml(p.periodo_sla)}</div><div><div class="progress-label"><span>Prazo de entrega</span><span>${escapeHtml(progressLabel(p))}</span></div><div class="progress-track"><div class="progress-fill" style="width:${progressWidth(p)}%;background:${barColor(p.status)}"></div></div></div><div class="note">${escapeHtml(p.observacao)}</div><span class="priority-chip ${escapeHtml(p.prioridadeClasse)}">${escapeHtml(p.prioridade)}</span></div>`;
      }).join('');
    }

    function renderTable(lista) {
      if (lista.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="8" style="text-align:center;color:var(--ink-soft);padding:30px;">Nenhum chamado encontrado.</td></tr>';
        return;
      }
      tableBody.innerHTML = lista.map(p => {
        const key = projectKey(p);
        const open = expandedTableTasks.has(key);
        const row = `<tr><td><span class="priority-dot ${escapeHtml(p.prioridadeClasse)}"></span>${escapeHtml(p.departamento)}</td><td><strong>${escapeHtml(p.projeto)}</strong></td><td>${escapeHtml(p.responsavel)}</td><td><span class="badge ${badgeClass(p.status)}"><span class="dot"></span>${escapeHtml(p.status)}</span></td><td><div class="table-progress"><div class="progress-track"><div class="progress-fill" style="width:${progressWidth(p)}%;background:${barColor(p.status)}"></div></div><span>${escapeHtml(progressLabel(p))}</span></div></td><td>${escapeHtml(p.periodo_sla)}</td><td>${escapeHtml(p.observacao)}</td><td>${renderTaskToggle(p, 'table-task-toggle')}</td></tr>`;
        const tasks = open ? `<tr class="task-row"><td colspan="8"><div class="task-panel">${renderTaskItems(p)}</div></td></tr>` : '';
        return row + tasks;
      }).join('');
    }

    function aplicarFiltros() {
      updateFilterOptions();
      const filtrados = filteredProjects();
      renderSummary(filteredProjects({
        status: 'todos'
      }));
      renderCards(filtrados);
      renderTable(filtrados);
      resultsCount.innerHTML = `Exibindo <strong>${filtrados.length}</strong> de <strong>${projetos.length}</strong> chamado(s) com projeto`;
    }

    filtroDepto.addEventListener('change', () => {
      filtros.departamento = filtroDepto.value;
      aplicarFiltros();
    });
    filtroStatus.addEventListener('change', () => {
      filtros.status = filtroStatus.value;
      aplicarFiltros();
    });
    filtroResponsavel.addEventListener('change', () => {
      filtros.responsavel = filtroResponsavel.value;
      aplicarFiltros();
    });
    filtroPrioridade.addEventListener('change', () => {
      filtros.prioridade = filtroPrioridade.value;
      aplicarFiltros();
    });
    filtroBusca.addEventListener('input', () => {
      filtros.busca = filtroBusca.value.trim().toLowerCase();
      aplicarFiltros();
    });
    summaryGrid.addEventListener('click', event => {
      const card = event.target.closest('.summary-card');
      if (!card || card.disabled) return;
      filtros.status = card.dataset.status || 'todos';
      aplicarFiltros();
    });
    document.addEventListener('click', event => {
      const button = event.target.closest('.task-toggle');
      if (!button) return;

      const key = button.dataset.taskKey;
      if (!key) return;

      if (expandedTableTasks.has(key)) {
        expandedTableTasks.delete(key);
      } else {
        expandedTableTasks.add(key);
      }

      aplicarFiltros();
      if (expandedTableTasks.has(key)) {
        const project = projetos.find(item => projectKey(item) === key);
        if (project) {
          loadTasks(project);
        }
      }
    });
    resetFiltros.addEventListener('click', () => {
      filtros.departamento = 'todos';
      filtros.status = 'todos';
      filtros.responsavel = 'todos';
      filtros.prioridade = 'todos';
      filtros.busca = '';
      filtroBusca.value = '';
      aplicarFiltros();
    });
    aplicarFiltros();
  </script>
</body>

</html>
