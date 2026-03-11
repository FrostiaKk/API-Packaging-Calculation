<?php

declare(strict_types=1);

use App\Application\Kernel;
use GuzzleHttp\Psr7\ServerRequest;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/** @var ContainerBuilder $container */
$container = require __DIR__ . '/../bootstrap.php';

/** @var Kernel $application */
$application = $container->get(Kernel::class);

$request = ServerRequest::fromGlobals();
$response = $application->run($request);

while (ob_get_level() > 0) {
    ob_end_clean();
}

http_response_code($response->getStatusCode());

foreach ($response->getHeaders() as $name => $values) {
    $replace = true;
    foreach ($values as $value) {
        header("{$name}: {$value}", $replace);
        $replace = false;
    }
}

$body = $response->getBody();
if ($body->isSeekable()) {
    $body->rewind();
}

while (!$body->eof()) {
    echo $body->read(8192);
    if (connection_aborted()) {
        break;
    }
}
