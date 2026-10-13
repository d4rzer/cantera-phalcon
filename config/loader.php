<?php
$loader = new Phalcon\Autoload\Loader();
$loader->setNamespaces(['App' => BASE_PATH . '/app/']);
$loader->register();
