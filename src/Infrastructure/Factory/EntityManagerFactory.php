<?php

declare(strict_types=1);

namespace App\Infrastructure\Factory;

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\ORM\ORMSetup;

final class EntityManagerFactory
{
    public static function create(
        array $entityPaths,
        bool $devMode,
        string $driver,
        string $host,
        string $user,
        string $password,
        string $dbname,
    ): EntityManager {
        $config = ORMSetup::createAttributeMetadataConfiguration($entityPaths, $devMode);
        $config->setNamingStrategy(new UnderscoreNamingStrategy());

        $connection = DriverManager::getConnection([
            'driver' => $driver,
            'host' => $host,
            'user' => $user,
            'password' => $password,
            'dbname' => $dbname,
        ]);

        return new EntityManager($connection, $config);
    }
}
