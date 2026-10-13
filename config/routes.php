<?php
$router = new Phalcon\Mvc\Router(false);
$router->removeExtraSlashes(true);
$router->addGet('/', ['controller' => 'index', 'action' => 'index']);
$router->addGet('/api/health', ['controller' => 'index', 'action' => 'health']);
$router->addGet('/api/session', ['controller' => 'auth', 'action' => 'session']);
$router->addPost('/api/login', ['controller' => 'auth', 'action' => 'login']);
$router->addPost('/api/logout', ['controller' => 'auth', 'action' => 'logout']);
$router->addGet('/api/state', ['controller' => 'records', 'action' => 'state']);
$entities = '(teams|players|matchdays|goals|incidents)';
$router->addGet('/api/{entity:' . $entities . '}', ['controller' => 'records', 'action' => 'list']);
$router->addPost('/api/{entity:' . $entities . '}', ['controller' => 'records', 'action' => 'save']);
$router->addPost('/api/{entity:' . $entities . '}/{id:[0-9]+}', ['controller' => 'records', 'action' => 'save']);
$router->addDelete('/api/{entity:' . $entities . '}/{id:[0-9]+}', ['controller' => 'records', 'action' => 'delete']);
$router->addGet('/api/reports/{kind:(referee|incidents|scorers)}', ['controller' => 'reports', 'action' => 'report']);
$router->addGet('/api/player/{id:[0-9]+}', ['controller' => 'reports', 'action' => 'player']);
$router->notFound(['controller' => 'index', 'action' => 'notFound']);
return $router;
