<?php
declare(strict_types=1);
if (!extension_loaded('phalcon')) {
    throw new RuntimeException('Phalcon no está instalado. Utilice Docker según README.md.');
}
defined('BASE_PATH') || define('BASE_PATH', dirname(__DIR__));
$settings = require BASE_PATH . '/config/config.php';
date_default_timezone_set($settings['timezone']);
require BASE_PATH . '/config/loader.php';
$di = new Phalcon\Di\FactoryDefault();
$di->setShared('settings', fn() => $settings);
$di->setShared('db', function () use ($settings) {
    $db = $settings['database'];
    if ($db['driver'] === 'sqlite') {
        $connection = new Phalcon\Db\Adapter\Pdo\Sqlite(['dbname' => $db['dbname']]);
        $connection->execute('PRAGMA foreign_keys = ON');
        return $connection;
    }
    unset($db['driver']);
    return new Phalcon\Db\Adapter\Pdo\Mysql($db);
});
$di->setShared('security', fn() => new Phalcon\Encryption\Security());
$di->setShared('session', function () use ($settings) {
    if (PHP_SAPI !== 'cli') {
        ini_set('session.use_strict_mode', '1');
        session_name('cantera_session');
        session_set_cookie_params(['httponly' => true, 'secure' => ($settings['environment'] === 'production' || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')), 'samesite' => 'Lax', 'path' => '/']);
    }
    $session = new Phalcon\Session\Manager();
    $session->setAdapter(new Phalcon\Session\Adapter\Stream(['savePath' => BASE_PATH . '/var/session']));
    $session->start();
    return $session;
});
$di->setShared('acl', function () {
    $acl = new Phalcon\Acl\Adapter\Memory();
    $acl->setDefaultAction(Phalcon\Acl\Enum::DENY);
    $acl->addRole('admin');
    $acl->addRole('referee');
    $acl->addComponent('tournament', ['read', 'write']);
    $acl->allow('admin', 'tournament', ['read', 'write']);
    $acl->allow('referee', 'tournament', 'read');
    return $acl;
});
$di->setShared('dispatcher', function () {
    $dispatcher = new Phalcon\Mvc\Dispatcher();
    $dispatcher->setDefaultNamespace('App\\Controllers');
    return $dispatcher;
});
$di->setShared('view', function () use ($di) {
    $view = new Phalcon\Mvc\View();
    $view->setViewsDir(BASE_PATH . '/app/views/');
    $view->registerEngines(['.volt' => function ($view) use ($di) {
        $volt = new Phalcon\Mvc\View\Engine\Volt($view, $di);
        $volt->setOptions(['path' => BASE_PATH . '/var/cache/', 'always' => true]);
        return $volt;
    }]);
    return $view;
});
$di->setShared('router', fn() => require BASE_PATH . '/config/routes.php');
$di->setShared('tournament', fn() => new App\Services\Tournament($di));
return $di;
