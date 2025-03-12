<?php

declare(strict_types=1);

use DI\Container;
use DI\ContainerBuilder;
use Gab3mioni\ApiLlm\Controllers\ValidationController;
use Gab3mioni\ApiLlm\Interface\ValidationInterface;
use Gab3mioni\ApiLlm\Services\ValidationService;
use GuzzleHttp\Client;
use Slim\Factory\AppFactory;
use Symfony\Component\Dotenv\Dotenv;

set_time_limit(240);

require __DIR__ . '/../../vendor/autoload.php';

$dotenv = new Dotenv();
$dotenv->loadEnv(__DIR__ . '/../../.env');

$containerBuilder = new ContainerBuilder();
$containerBuilder->addDefinitions([
    Client::class => function () {
        return new Client([
            'verify' => false,
            'timeout' => 120
        ]);
    },
    ValidationInterface::class => function (Container $c) {
        return new ValidationService(
            $c->get(Client::class),
            $_ENV['DEEPSEEK_ENDPOINT'],
            $_ENV['DEEPSEEK_MODEL']
        );
    },
    ValidationController::class => function (Container $c) {
        return new ValidationController(
            $c->get(ValidationInterface::class)
        );
    }
]);

$container = $containerBuilder->build();

$app = AppFactory::createFromContainer($container);

$app->addBodyParsingMiddleware();
$app->addErrorMiddleware(true, true, true);

$app->post('/validar-mensagem', ValidationController::class . ':validate');

return $app;