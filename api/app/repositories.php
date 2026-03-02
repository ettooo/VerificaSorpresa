<?php

declare(strict_types=1);

use App\Domain\User\UserRepository;
use App\Infrastructure\Persistence\User\InMemoryUserRepository;
use DI\ContainerBuilder;

return function (ContainerBuilder $containerBuilder) {
    // Here we map our UserRepository interface to its in memory implementation
    $containerBuilder->addDefinitions([
        UserRepository::class => \DI\autowire(InMemoryUserRepository::class),
        \App\Domain\Piece\PieceRepository::class => \DI\autowire(\App\Infrastructure\Persistence\Piece\PDOPieceRepository::class),
        \App\Domain\Supplier\SupplierRepository::class => \DI\autowire(\App\Infrastructure\Persistence\Supplier\PDOSupplierRepository::class),
    ]);
};
