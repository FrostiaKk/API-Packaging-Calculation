<?php

declare(strict_types=1);

use App\Application\Handler\BoxHandler;
use App\Application\Handler\HealthHandler;
use App\Application\Handler\PackingHandler;
use App\Application\Http\ResponseFactory;
use App\Application\Kernel;
use App\Application\Middleware\ApiKeyAuthMiddleware;
use App\Application\Validation\RequestBodyParser;
use App\Domain\Port\BoxRepositoryInterface;
use App\Domain\Port\HealthCheckInterface;
use App\Domain\Port\PackingResultRepositoryInterface;
use App\Domain\Service\BoxService;
use App\Domain\Service\BoxServiceInterface;
use App\Domain\Service\PackingService;
use App\Domain\Service\PackingServiceInterface;
use App\Infrastructure\Api\ApiPackingCalculator;
use App\Infrastructure\Factory\EntityManagerFactory;
use App\Infrastructure\Fallback\LocalPackingCalculator;
use App\Infrastructure\HealthCheck\DoctrineHealthCheck;
use App\Infrastructure\Persistence\Repository\DoctrineBoxRepository;
use App\Infrastructure\Persistence\Repository\DoctrinePackingResultRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

return static function (): ContainerBuilder {
    $container = new ContainerBuilder();

    $container->setParameter('log_level', $_ENV['LOG_LEVEL'] ?? 'warning');
    $container->setParameter('doctrine.dev_mode', filter_var($_ENV['DOCTRINE_DEV_MODE'] ?? false, FILTER_VALIDATE_BOOLEAN));
    $container->setParameter('doctrine.entity_paths', [__DIR__ . '/../src/Infrastructure/Persistence/Entity']);
    $container->setParameter('db.driver', $_ENV['DB_DRIVER'] ?? 'pdo_mysql');
    $container->setParameter('db.host', $_ENV['DB_HOST'] ?? 'shipmonk-packing-mysql');
    $container->setParameter('db.user', $_ENV['DB_USER'] ?? 'root');
    $container->setParameter('db.password', $_ENV['DB_PASSWORD'] ?? 'secret');
    $container->setParameter('db.name', $_ENV['DB_NAME'] ?? 'packing');
    $container->setParameter('packing_api.username', $_ENV['PACKING_API_USERNAME'] ?? '');
    $container->setParameter('packing_api.key', $_ENV['PACKING_API_KEY'] ?? '');
    $container->setParameter('packing_api.url', rtrim($_ENV['PACKING_API_URL'] ?? '', '/'));
    $container->setParameter('api_key', $_ENV['API_KEY'] ?? '');

    $container->register(StreamHandler::class)
        ->addArgument('php://stderr')
        ->addArgument('%log_level%');

    $container->register(LoggerInterface::class, Logger::class)
        ->addArgument('packing')
        ->addMethodCall('pushHandler', [new Reference(StreamHandler::class)]);

    $container->register(EntityManagerInterface::class, EntityManager::class)
        ->setPublic(true)
        ->setFactory([EntityManagerFactory::class, 'create'])
        ->addArgument('%doctrine.entity_paths%')
        ->addArgument('%doctrine.dev_mode%')
        ->addArgument('%db.driver%')
        ->addArgument('%db.host%')
        ->addArgument('%db.user%')
        ->addArgument('%db.password%')
        ->addArgument('%db.name%');

    $container->register(ClientInterface::class, Client::class);

    $container->register(BoxRepositoryInterface::class, DoctrineBoxRepository::class)
        ->addArgument(new Reference(EntityManagerInterface::class));

    $container->register(PackingResultRepositoryInterface::class, DoctrinePackingResultRepository::class)
        ->addArgument(new Reference(EntityManagerInterface::class));

    $container->register(HealthCheckInterface::class, DoctrineHealthCheck::class)
        ->addArgument(new Reference(EntityManagerInterface::class));

    $container->register(ApiPackingCalculator::class)
        ->addArgument(new Reference(ClientInterface::class))
        ->addArgument('%packing_api.username%')
        ->addArgument('%packing_api.key%')
        ->addArgument('%packing_api.url%')
        ->addArgument(new Reference(LoggerInterface::class));

    $container->register(LocalPackingCalculator::class);

    $container->register(PackingServiceInterface::class, PackingService::class)
        ->addArgument(new Reference(ApiPackingCalculator::class))
        ->addArgument(new Reference(LocalPackingCalculator::class))
        ->addArgument(new Reference(PackingResultRepositoryInterface::class))
        ->addArgument(new Reference(BoxRepositoryInterface::class));

    $container->register(BoxServiceInterface::class, BoxService::class)
        ->addArgument(new Reference(BoxRepositoryInterface::class))
        ->addArgument(new Reference(PackingResultRepositoryInterface::class));

    $container->register('validator_builder', \Symfony\Component\Validator\ValidatorBuilder::class)
        ->setFactory([Validation::class, 'createValidatorBuilder'])
        ->addMethodCall('enableAttributeMapping');

    $container->register(ValidatorInterface::class)
        ->setFactory([new Reference('validator_builder'), 'getValidator']);

    $container->register(ResponseFactory::class);

    $container->register(RequestBodyParser::class)
        ->addArgument(new Reference(ValidatorInterface::class));

    $container->register(PackingHandler::class)
        ->addArgument(new Reference(PackingServiceInterface::class))
        ->addArgument(new Reference(RequestBodyParser::class))
        ->addArgument(new Reference(ResponseFactory::class));

    $container->register(BoxHandler::class)
        ->addArgument(new Reference(BoxServiceInterface::class))
        ->addArgument(new Reference(RequestBodyParser::class))
        ->addArgument(new Reference(ResponseFactory::class));

    $container->register(HealthHandler::class)
        ->addArgument(new Reference(HealthCheckInterface::class))
        ->addArgument(new Reference(ResponseFactory::class));

    $container->register(ApiKeyAuthMiddleware::class)
        ->addArgument('%api_key%')
        ->addArgument(new Reference(ResponseFactory::class));

    $container->register(Kernel::class)
        ->addArgument(new Reference(PackingHandler::class))
        ->addArgument(new Reference(BoxHandler::class))
        ->addArgument(new Reference(LoggerInterface::class))
        ->addArgument(new Reference(ApiKeyAuthMiddleware::class))
        ->addArgument(new Reference(HealthHandler::class))
        ->addArgument(new Reference(ResponseFactory::class))
        ->setPublic(true);

    return $container;
};
