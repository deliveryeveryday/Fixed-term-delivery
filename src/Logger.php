<?php
namespace App;
use Monolog\Handler\StreamHandler;
use Monolog\Logger as MonologLogger;
class Logger {
    private static ?MonologLogger $instance = null;
    public static function getInstance(array $config): ?MonologLogger {
        if (self::$instance === null && $config['enabled']) {
            self::$instance = new MonologLogger('app');
            self::$instance->pushHandler(new StreamHandler($config['file'], $config['level']));
        }
        return self::$instance;
    }
}