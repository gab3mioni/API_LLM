<?php

namespace Gab3mioni\ApiLlm\Controllers;

use Gab3mioni\ApiLlm\Interface\ValidationInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ValidationController
{
    private ValidationInterface $validator;

    public function __construct(ValidationInterface $validator)
    {
        $this->validator = $validator;
    }

    public function validate(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        if (empty($data['mensagem'])) {
            $responseData = ['erro' => 'Mensagem não fornecida.'];
            $response->getBody()->write(json_encode($responseData));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $message = $data['mensagem'];
        $buttons = $data['botoes'] ?? null;
        
        $analise = $this->validator->validate($message, $buttons);

        $response->getBody()->write(json_encode($analise));
        return $response->withHeader('Content-Type', 'application/json');
    }
}