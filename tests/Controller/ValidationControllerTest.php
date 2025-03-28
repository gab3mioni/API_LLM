<?php

namespace Gab3mioni\ApiLlm\Tests\Controller;

use PHPUnit\Framework\TestCase;
use Gab3mioni\ApiLlm\Controllers\ValidationController;
use Gab3mioni\ApiLlm\Interface\ValidationInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\StreamInterface;

class ValidationControllerTest extends TestCase
{
    private $validatorMock;
    private $requestMock;
    private $responseMock;
    private $streamMock;
    private ValidationController $controller;

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    protected function setUp(): void
    {
        $this->validatorMock = $this->createMock(ValidationInterface::class);
        $this->requestMock = $this->createMock(Request::class);
        $this->responseMock = $this->createMock(Response::class);
        $this->streamMock = $this->createMock(StreamInterface::class);

        $this->responseMock->method('getBody')->willReturn($this->streamMock);

        $this->controller = new ValidationController($this->validatorMock);
    }

    public function testValidateReturnsErrorWhenMessageNotProvided()
    {
        $this->requestMock->method('getParsedBody')->willReturn([]);

        $this->streamMock->expects($this->once())
            ->method('write')
            ->with(json_encode(['erro' => 'Mensagem não fornecida.']));

        $this->responseMock->expects($this->once())
            ->method('withHeader')
            ->with('Content-Type', 'application/json')
            ->willReturnSelf();

        $this->responseMock->expects($this->once())
            ->method('withStatus')
            ->with(400)
            ->willReturnSelf();

        $this->controller->validate($this->requestMock, $this->responseMock);
    }

    public function testValidateReturnsAnalysisWhenMessageProvided()
    {
        $message = 'Test message';
        $analysis = ['valid' => true];

        $this->requestMock->method('getParsedBody')->willReturn(['mensagem' => $message]);
        $this->validatorMock->method('validate')
            ->with($message, null)
            ->willReturn($analysis);

        $this->streamMock->expects($this->once())
            ->method('write')
            ->with(json_encode($analysis));

        $this->responseMock->expects($this->once())
            ->method('withHeader')
            ->with('Content-Type', 'application/json')
            ->willReturnSelf();

        $this->controller->validate($this->requestMock, $this->responseMock);
    }

    public function testValidateWithButtonsIncludesThem()
    {
        $message = 'Test message';
        $buttons = ['Sim', 'Não'];
        $analysis = ['valid' => true, 'botoes_sugeridos' => $buttons];

        $this->requestMock->method('getParsedBody')
            ->willReturn(['mensagem' => $message, 'botoes' => $buttons]);
        
        $this->validatorMock->method('validate')
            ->with($message, $buttons)
            ->willReturn($analysis);

        $this->streamMock->expects($this->once())
            ->method('write')
            ->with(json_encode($analysis));

        $this->responseMock->expects($this->once())
            ->method('withHeader')
            ->with('Content-Type', 'application/json')
            ->willReturnSelf();

        $this->controller->validate($this->requestMock, $this->responseMock);
    }
}