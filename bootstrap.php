<?php

declare(strict_types=1);

use Doctrine\DBAL\Types\Type;
use Dotenv\Dotenv;
use Ramsey\Uuid\Doctrine\UuidBinaryType;

require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();
$dotenv->required(['PACKING_API_URL', 'PACKING_API_USERNAME', 'PACKING_API_KEY', 'API_KEY'])->notEmpty();

if (!Type::hasType('uuid_binary')) {
    Type::addType('uuid_binary', UuidBinaryType::class);
}

$containerFactory = require __DIR__ . '/config/container.php';
$container = $containerFactory();
$container->compile();

return $container;
