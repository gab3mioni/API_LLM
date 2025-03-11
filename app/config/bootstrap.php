<?php

set_time_limit(240);

require __DIR__ . '/../../vendor/autoload.php';

use Gab3mioni\ApiLlm\Controllers\ValidationController;
use Gab3mioni\ApiLlm\Services\ValidationService;
use GuzzleHttp\Client;
use Symfony\Component\Dotenv\Dotenv;
use Slim\Factory\AppFactory;

$dotenv = new Dotenv();
$dotenv->loadEnv(__DIR__ . '/../../.env');

$app = AppFactory::create();
$app->addBodyParsingMiddleware();
$app->addErrorMiddleware(true, true, true);

$client = new Client(['verify' => false, 'timeout' => 120]);
$endpoint = $_ENV['DEEPSEEK_ENDPOINT'];
$model = $_ENV['DEEPSEEK_MODEL'];

$validatorService = new ValidationService($client, $endpoint, $model);
$controller = new ValidationController($validatorService);

$app->post('/validar-mensagem', [$controller, 'validate']);

return $app;
