<?php
require('./vendor/autoload.php');
$base = \Base::instance();
$base->config('./app/Configs/config.ini');

$base->set('DB', new \DB\SQL(
    $base->get('db.dsn'),
    $base->get('db.username'),
    $base->get('db.password'),
    [PDO::ATTR_STRINGIFY_FETCHES => false]
));

$base->run();