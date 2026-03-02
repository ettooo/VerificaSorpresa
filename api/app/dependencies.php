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
            // ensure tables exist and have auth columns
            $pdo->exec("CREATE TABLE IF NOT EXISTS Fornitori (
                fid TEXT PRIMARY KEY,
                fnome TEXT NOT NULL,
                indirizzo TEXT NOT NULL
            )");
            $cols = $pdo->query("PRAGMA table_info(Fornitori)")->fetchAll(\PDO::FETCH_ASSOC);
            $names = array_column($cols, 'name');
            if (!in_array('username', $names)) {
                $pdo->exec("ALTER TABLE Fornitori ADD COLUMN username TEXT UNIQUE");
            }
            if (!in_array('password', $names)) {
                $pdo->exec("ALTER TABLE Fornitori ADD COLUMN password TEXT");
            }

            return $pdo;
        },
    ]);

};