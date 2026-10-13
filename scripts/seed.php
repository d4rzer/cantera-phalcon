<?php
declare(strict_types=1);
$di = require dirname(__DIR__) . '/config/bootstrap.php';
$db = $di->getShared('db');
if ((int)$db->fetchOne('SELECT COUNT(*) AS total FROM teams', \Phalcon\Db\Enum::FETCH_ASSOC)['total']) { echo "La base ya contiene datos. No se sobrescribió información.\n"; exit(0); }
$seed = json_decode(file_get_contents(BASE_PATH . '/database/seed.json'), true, 512, JSON_THROW_ON_ERROR);
$db->begin();
try {
    foreach (['teams', 'players', 'matchdays', 'goals', 'incidents'] as $table) foreach ($seed[$table] as $row) $db->insertAsDict($table, $row);
    $security = $di->getShared('security');
    foreach ([['Administrador', 'admin@cantera.local', 'admin', getenv('SEED_ADMIN_PASSWORD') ?: 'Cantera2026!'], ['Árbitro', 'arbitro@cantera.local', 'referee', getenv('SEED_REFEREE_PASSWORD') ?: 'Arbitro2026!']] as [$name,$email,$role,$password]) {
        $db->insertAsDict('users', ['name'=>$name, 'email'=>$email, 'role'=>$role, 'password_hash'=>$security->hash($password)]);
    }
    $db->commit();
    echo "Datos de demostración y cuentas creados.\n";
} catch (Throwable $e) { $db->rollback(); throw $e; }
