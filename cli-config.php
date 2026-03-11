<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Console\ConsoleRunner;
use Dotenv\Dotenv;

require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$containerFactory = require __DIR__ . '/config/container.php';
$container = $containerFactory();

return ConsoleRunner::createHelperSet($container->get(EntityManagerInterface::class));
