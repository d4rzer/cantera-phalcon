<?php
declare(strict_types=1);
// Isolated SQLite database. The application itself defaults to MySQL.
if (!extension_loaded('pdo_sqlite')) { fwrite(STDERR, "Pruebas: se necesita pdo_sqlite (incluido en Docker).\n"); exit(1); }
$file = sys_get_temp_dir() . '/cantera-test-' . bin2hex(random_bytes(8)) . '.sqlite';
putenv('DB_DRIVER=sqlite'); putenv('DB_NAME=' . $file);
require dirname(__DIR__) . '/scripts/migrate.php';
require dirname(__DIR__) . '/scripts/seed.php';
$service = $di->getShared('tournament');
$checks = 0;
function check(bool $condition, string $name): void { global $checks; if (!$condition) throw new RuntimeException('FAIL: ' . $name); $checks++; echo "PASS: {$name}\n"; }
function rejected(callable $fn, string $name): void { try { $fn(); } catch (App\Exceptions\HttpException $e) { check($e->status >= 400, $name); return; } throw new RuntimeException('FAIL: se aceptó ' . $name); }
try {
    $state = $service->state();
    check(count($state['teams']) === 4 && count($state['players']) === 20 && count($state['matchdays']) === 5, 'Datos de demostración completos');
    $referee = $service->report('referee', ['matchday_id' => 3]);
    check(count(array_filter($referee['rows'], fn($p) => $p['suspended'])) === 3, 'Tres suspensiones en jornada 3');
    check(count(array_filter($service->report('referee', ['matchday_id' => 4])['rows'], fn($p) => $p['suspended'])) === 0, 'Suspensión solo en la fecha indicada');
    $scores = $service->report('scorers', [])['rows'];
    check($scores[0]['total_goals'] === 10 && count(array_unique(array_column($scores, 'total_goals'))) >= 5, 'Totales y orden de goleadores');
    check(count($service->report('incidents', ['team_id' => 1, 'card_type' => 'red'])['rows']) === 1, 'Filtro de incidencias por equipo y tarjeta');
    check(count($service->report('incidents', ['team_id' => 1, 'player_id' => 4])['rows']) === 1, 'Incidencias de un jugador o todos los del equipo');
    check(count($service->report('incidents', ['date_from' => '2026-10-10', 'date_to' => '2026-10-10'])['rows']) === 5, 'Rango de fechas inclusivo');
    check($service->player(2)['history'][2]['suspended'] === true && $service->player(2)['total_goals'] === 9, 'Ficha completa e historial');
    rejected(fn() => $service->save('goals', ['player_id' => 2, 'matchday_id' => 3, 'goals' => 1]), 'Bloqueo de goles por suspensión');
    rejected(fn() => $service->save('goals', ['player_id' => 1, 'matchday_id' => 1, 'goals' => 2]), 'Un registro de goles por jugador y jornada');
    rejected(fn() => $service->save('incidents', ['player_id' => 1, 'description' => 'Prueba', 'card_type' => 'red', 'incident_date' => '2026-10-10']), 'Roja requiere suspensión');
    rejected(fn() => $service->save('incidents', ['player_id' => 1, 'description' => 'Prueba', 'card_type' => 'red', 'incident_date' => '2026-10-10', 'suspension_date' => '2026-10-03']), 'Suspensión anterior a incidencia');
    rejected(fn() => $service->save('incidents', ['player_id' => 1, 'description' => 'Prueba', 'card_type' => 'red', 'incident_date' => '2026-10-03', 'suspension_date' => '2026-10-10']), 'Suspensión incompatible con goles existentes');
    rejected(fn() => $service->save('teams', ['name' => 'LEONES DE PETAPA', 'active' => 1]), 'Nombre de equipo único sin distinción de mayúsculas');
    rejected(fn() => $service->delete('teams', 1), 'Protección de equipo con jugadores');
    rejected(fn() => $service->delete('players', 1), 'Protección del historial del jugador');
    rejected(fn() => $service->delete('matchdays', 3), 'Protección de jornada con suspensiones');
    rejected(fn() => $service->save('matchdays', ['number' => 3, 'game_date' => '2026-11-07'], 3), 'Cambio de fecha con suspensiones exige ajuste previo');
    rejected(fn() => $service->save('matchdays', ['number' => 6, 'game_date' => '2026-02-30']), 'Fecha inexistente');
    rejected(fn() => $service->save('players', ['first_name'=>'Juan1', 'last_name'=>'Pérez','birth_date'=>'2015-04-02','team_id'=>1]), 'Nombre con caracteres inválidos');
    rejected(fn() => $service->save('players', ['first_name'=>'Juan', 'last_name'=>'Pérez','birth_date'=>'2000-04-02','team_id'=>1]), 'Edad fuera de la categoría');
    $p = $state['players'][0]; $p['team_id'] = 2;
    rejected(fn() => $service->save('players', $p, 1), 'El historial conserva el equipo del torneo');
    rejected(fn() => $service->report('incidents', ['date_from'=>'2026-11-01','date_to'=>'2026-10-01']), 'Rango de fechas invertido');
    $newTeam = $service->save('teams', ['name'=>'Equipo de prueba','active'=>1]);
    $newPlayer = $service->save('players', ['first_name'=>'Óscar','last_name'=>'Álvarez','birth_date'=>'2015-04-02','team_id'=>$newTeam['id'],'shirt_number'=>10,'position'=>'Delantero']);
    check($newPlayer['photo_path'] === 'images/player-placeholder.svg', 'Nuevo jugador y fotografía de respaldo');
    $newDay = $service->save('matchdays', ['number'=>6,'game_date'=>'2026-11-07']);
    $goal = $service->save('goals', ['player_id'=>$newPlayer['id'],'matchday_id'=>$newDay['id'],'goals'=>0]);
    check((int)$goal['goals'] === 0, 'Cero goles es un valor válido');
    $goal = $service->save('goals', ['player_id'=>$newPlayer['id'],'matchday_id'=>$newDay['id'],'goals'=>4], (int)$goal['id']);
    check($service->player((int)$newPlayer['id'])['total_goals'] === 4, 'Edición de goles actualiza los reportes');
    $yellow = $service->save('incidents', ['player_id'=>$newPlayer['id'],'description'=>'Prueba de amarilla','card_type'=>'yellow','incident_date'=>'2026-11-07','suspension_date'=>'2026-11-08']);
    check($yellow['suspension_date'] === null, 'Amarilla no genera suspensión');
    $service->save('teams', ['name'=>'Equipo actualizado','active'=>0], (int)$newTeam['id']);
    rejected(fn()=>$service->save('players',['first_name'=>'Nuevo','last_name'=>'Jugador','birth_date'=>'2015-04-02','team_id'=>$newTeam['id']]), 'Equipo inactivo no recibe nuevos jugadores');
    $service->delete('goals', (int)$goal['id']); $service->delete('incidents', (int)$yellow['id']);
    $service->delete('players', (int)$newPlayer['id']); $service->delete('teams', (int)$newTeam['id']); $service->delete('matchdays', (int)$newDay['id']);
    check(count($service->state()['players']) === 20 && count($service->state()['matchdays']) === 5, 'CRUD completo y eliminación de registros sin asociaciones');
    $acl = $di->getShared('acl');
    check($acl->isAllowed('admin','tournament','write') && $acl->isAllowed('referee','tournament','read') && !$acl->isAllowed('referee','tournament','write'), 'ACL de administrador y árbitro');
    echo "\n{$checks} verificaciones correctas.\n";
} finally { if (is_file($file)) unlink($file); }
