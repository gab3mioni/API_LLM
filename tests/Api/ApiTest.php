<?php

namespace Gab3mioni\ApiLlm\Tests\Api;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Slim\Factory\AppFactory;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Stream;

class ValidationApiTest extends TestCase
{
    private \Slim\App $app;
    private MockHandler $mockHandler;

    protected function setUp(): void
    {
        $this->mockHandler = new MockHandler();
        $handlerStack = HandlerStack::create($this->mockHandler);
        $mockClient = new Client(['handler' => $handlerStack]);

        $_ENV['DEEPSEEK_ENDPOINT'] = 'http://mock-api';
        $_ENV['DEEPSEEK_MODEL'] = 'test-model';

        $this->app = require __DIR__ . '/../../app/config/bootstrap.php';

        $container = $this->app->getContainer();
        $container->set(Client::class, $mockClient);
    }

    private function createRequest(
        string $method,
        string $path,
        array $body = null
    ) {
        $factory = new ServerRequestFactory();

        $request = $factory->createServerRequest($method, $path)
            ->withHeader('Content-Type', 'application/json');

        if ($body) {
            $request = $request->withParsedBody($body);
        }

        return $request;
    }

    public function testValidMessageShouldReturnAnalysis()
    {
        // Configura a resposta mockada
        $mockResponse = json_encode([
            'response' => json_encode([
                'valido' => true,
                'erros' => [],
                'explicacao' => '',
                'mensagem_sugerida' => 'Mensagem corrigida'
            ])
        ]);

        $this->mockHandler->append(new Response(200, [], $mockResponse));

        $message = "Você sabia que o tratamento correto pode transformar a forma como o TDAH impacta a vida? 🌈";

        $request = $this->createRequest(
            'POST',
            '/validar-mensagem',
            ['mensagem' => $message]
        );

        $response = $this->app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());

        $responseData = json_decode((string)$response->getBody(), true);

        $this->assertArrayHasKey('valido', $responseData);
        $this->assertArrayHasKey('erros', $responseData);
        $this->assertArrayHasKey('explicacao', $responseData);
        $this->assertArrayHasKey('mensagem_sugerida', $responseData);
    }

    public function testInvalidJsonShouldHandleGracefully()
    {
        $request = $this->createRequest('POST', '/validar-mensagem')
            ->withBody(new Stream(fopen('php://temp', 'r+')));

        $request->getBody()->write('{invalid json}');
        $request->getBody()->rewind();

        $response = $this->app->handle($request);

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            '{"erro":"Mensagem não fornecida."}',
            (string)$response->getBody()
        );
    }
}