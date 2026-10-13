<?php
// Set NEW_PASSWORD in the environment; avoid passwords in command history.
$di = require dirname(__DIR__) . '/config/bootstrap.php';
$email = $argv[1] ?? '';
$password = getenv('NEW_PASSWORD') ?: '';
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 12) { fwrite(STDERR, "Uso: NEW_PASSWORD (12+ caracteres) y correo como argumento.\n"); exit(1); }
$user = App\Models\User::findFirst(['email = :e:', 'bind' => ['e' => $email]]);
if (!$user) { fwrite(STDERR, "Cuenta no encontrada.\n"); exit(1); }
$user->password_hash = $di->getShared('security')->hash($password);
if (!$user->save()) exit(1);
echo "Contraseña actualizada.\n";
