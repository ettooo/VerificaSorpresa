<?php

use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;

return function (ContainerBuilder $containerBuilder) {

    $containerBuilder->addDefinitions([
        \PDO::class => function (ContainerInterface $c) {

            $dbPath = __DIR__ . '/../database.sqlite';

            $pdo = new \PDO("sqlite:" . $dbPath);

            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);

            return $pdo;
        },
    ]);

};