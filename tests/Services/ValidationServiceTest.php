<?php

namespace Gab3mioni\ApiLlm\Tests\Services;

use PHPUnit\Framework\TestCase;
use Gab3mioni\ApiLlm\Services\ValidationService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

class ValidationServiceTest extends TestCase
{
    private Client $clientMock;
    private ValidationService $service;

    protected function setUp(): void
    {
        $this->clientMock = $this->createMock(Client::class);
        $this->service = new ValidationService(
            $this->clientMock,
            'http://api-teste.com',
            'modelo-teste'
        );
    }

    public function testConfiguracaoInvalida(): void
    {
        $service = new ValidationService($this->clientMock, '', 'modelo');
        $result = $service->validate('teste');
        $this->assertEquals(
            'Endpoint não configurado. Ajuste nas variáveis do ambiente.',
            $result['erro']
        );

        $service = new ValidationService($this->clientMock, 'http://teste', '');
        $result = $service->validate('teste');
        $this->assertEquals(
            'Modelo não configurado. Ajuste nas variáveis do ambiente.',
            $result['erro']
        );
    }

    public function testRespostaValida(): void
    {
        $responseJson = json_encode([
            'response' => '{"valido": true, "mensagem_sugerida": "Teste válido"}'
        ]);

        $this->clientMock->method('post')
            ->willReturn(new Response(200, [], $responseJson));

        $result = $this->service->validate('mensagem');

        $this->assertTrue($result['valido']);
        $this->assertEquals('Teste válido', $result['mensagem_sugerida']);
    }

    public function testRespostaValidaComBotoes(): void
    {
        $responseJson = json_encode([
            'response' => '{"valido": true, "mensagem_sugerida": "Teste válido", "botoes_sugeridos": ["Sim", "Não"]}'
        ]);

        $this->clientMock->method('post')
            ->willReturn(new Response(200, [], $responseJson));

        $result = $this->service->validate('mensagem', ['Sim', 'Não']);

        $this->assertTrue($result['valido']);
        $this->assertEquals('Teste válido', $result['mensagem_sugerida']);
        $this->assertEquals(['Sim', 'Não'], $result['botoes_sugeridos']);
    }

    public function testRespostaMalFormatada(): void
    {
        $this->clientMock->method('post')
            ->willReturn(new Response(200, [], 'Resposta inválida'));

        $result = $this->service->validate('mensagem');

        $this->assertArrayHasKey('erro', $result);
        $this->assertStringContainsString('JSON inválido', $result['erro']);
    }

    public function testErroDeConexao(): void
    {
        $this->clientMock->method('post')
            ->willThrowException(
                new RequestException(
                    'Timeout',
                    new Request('POST', 'http://teste')
                )
            );

        $result = $this->service->validate('mensagem');

        $this->assertArrayHasKey('erro', $result);
        $this->assertStringContainsString('comunicação', $result['erro']);
    }

    public function testJsonIncorretoNaResposta(): void
    {
        $this->clientMock->method('post')
            ->willReturn(new Response(200, [], '{"response": "{"valido": true, mensagem_sugerida: \'Erro\'}"}'));

        $result = $this->service->validate('mensagem');

        $this->assertArrayHasKey('erro', $result);
        $this->assertStringContainsString('JSON inválido', $result['erro']);
    }

    protected function tearDown(): void
    {
        unset($this->clientMock, $this->service);
    }
}