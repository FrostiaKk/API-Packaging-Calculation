<?php

declare(strict_types=1);

use App\Application\Kernel;
use GuzzleHttp\Psr7\Message;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/** @var ContainerBuilder $container */
$container = require __DIR__ . '/bootstrap.php';

/** @var Kernel $kernel */
$kernel = $container->get(Kernel::class);

$headers = ['Content-Type' => 'application/json'];
if (isset($_ENV['API_KEY'])) {
    $headers['X-Api-Key'] = $_ENV['API_KEY'];
}

$request = new Request('POST', new Uri('http://localhost/pack'), $headers, $argv[1]);

$response = $kernel->run($request);

echo "<<< In:\n" . Message::toString($request) . "\n\n";
echo ">>> Out:\n" . Message::toString($response) . "\n\n";
