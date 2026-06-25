<?php

declare(strict_types=1);

require __DIR__ . '/Support.php';
require __DIR__ . '/Config.php';
require __DIR__ . '/GlpiClient.php';
require __DIR__ . '/UserRepository.php';
require __DIR__ . '/TicketRepository.php';
require __DIR__ . '/DashboardMapper.php';
require __DIR__ . '/DashboardService.php';

$config = load_config(dirname(__DIR__) . '/.env');
date_default_timezone_set($config['timezone']);

return $config;
