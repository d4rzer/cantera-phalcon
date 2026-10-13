<?php
declare(strict_types=1);
$di = require dirname(__DIR__) . '/config/bootstrap.php';
$db = $di->getShared('db');
$driver = $di->getShared('settings')['database']['driver'];
$db->execute('CREATE TABLE IF NOT EXISTS schema_migrations (version VARCHAR(30) PRIMARY KEY, applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP)');
if ($db->fetchOne('SELECT version FROM schema_migrations WHERE version = ?', \Phalcon\Db\Enum::FETCH_ASSOC, ['001_initial'])) { echo "Esquema actualizado.\n"; exit(0); }
foreach (explode(';', file_get_contents(BASE_PATH . '/database/schema-' . $driver . '.sql')) as $sql) {
    $sql = trim($sql);
    if (!$sql) continue;
    if (preg_match('/^CREATE INDEX (\w+) ON (\w+)/', $sql, $match) && isset($db->describeIndexes($match[2])[$match[1]])) continue;
    $db->execute($sql);
}
$db->execute('CREATE TABLE IF NOT EXISTS tournament_lock (id INTEGER PRIMARY KEY)');
if (!$db->fetchOne('SELECT id FROM tournament_lock WHERE id = 1')) $db->insertAsDict('tournament_lock', ['id' => 1]);
$db->insertAsDict('schema_migrations', ['version' => '001_initial']);
echo "Migración 001 aplicada.\n";
