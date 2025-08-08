<?php
// ...
// 1. 設定ファイルを読み込む
$config = require __DIR__ . '/config.php';

// 2. ロガーを初期化
$logger = App\Logger::getInstance($config['logging']);
$logger?->info("Site generation started.");

// 3. 各コンポーネントに設定とロガーを注入してインスタンス化
$paApiHandler = new App\PaApiHandler($config, $logger);
$simulationEngine = new App\SimulationEngine($config['simulation']);
// ...

// 4. try-catchブロックで、エラーをログに記録する
// try { ... } catch (Exception $e) { $logger?->error($e->getMessage()); }

$logger?->info("Site generation finished.");