<?php

namespace Gab3mioni\ApiLlm\Services;

use Gab3mioni\ApiLlm\Interface\ValidationInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use JsonException;
use RuntimeException;

class ValidationService implements ValidationInterface
{
    private Client $client;
    private string $endpoint;
    private string $model;

    public function __construct(
        Client $client,
        string $endpoint,
        string $model
    )
    {
        $this->client = $client;
        $this->endpoint = $endpoint;
        $this->model = $model;
    }

    public function validate(string $message): array
    {
        if (empty($this->endpoint)) {
            return ['erro' => 'Endpoint não configurado. Ajuste nas variáveis do ambiente.'];
        }

        if (empty($this->model)) {
            return ['erro' => 'Modelo não configurado. Ajuste nas variáveis do ambiente.'];
        }

        $prompt = $this->generatePrompt($message);

        try {
            $response = $this->client->post($this->endpoint, [
                'json' => [
                    'model' => $this->model,
                    'prompt' => $prompt,
                    'stream' => false,
                    'options' => ['temperature' => 0.1]
                ],
                'timeout' => $_ENV['TIMEOUT_LIMIT']
            ]);

            return $this->processResponse($response->getBody()->getContents());

        } catch (GuzzleException $e) {
            return ['erro' => 'Erro de comunicação: ' . $e->getMessage()];
        } catch (RuntimeException $e) {
            return ['erro' => 'Erro de processamento: ' . $e->getMessage()];
        }
    }

    private function generatePrompt(string $message): string
    {
        return "Analise a seguinte mensagem para correções ortográficas, gramaticais e validação de compliance. Não é necessario ajustar termos técnicos. Siga estas etapas CRITICAMENTE:\n\n1. Verifique erros de português \n2. Identifique elementos de marketing\n3. Avalie uso de emojis conforme diretrizes\n4. Mantenha variáveis e elementos válidos\n5. Liste TODOS os erros encontrados\n6. Explique detalhadamente cada correção\n\nMensagem: \"$message\"\n\n**Regras de Validação:**\n- Emojis permitidos (máx 3): ★✨✓➡️🩺📅 (manter relevância médica)\n- Proibidos: 🛒🎉🤑💲🚨 (evitar tom comercial/alegre)\n- Variáveis devem manter formato {{nome}}\n- Proibir qualquer menção a preços/descontos\n- Termos técnicos com ® devem ser preservados\n\n**Formato de Resposta EXIGIDO (JSON):**\n{\n  \"valido\": <bool>,\n  \"erros\": [\"erro1\", \"erro2\", ...],\n  \"explicacao\": \"1. [erro1] explicação\\n2. [erro2] explicação\\n...\",\n  \"mensagem_sugerida\": \"texto com TODAS correções aplicadas\"\n}\n\n**Exemplo Completo:**\nMensagem: \"🌟{{Nome}}, Pantogar® está com SUPER desconto! 😍 Compre já 🛒\"\nResposta:\n{\n  \"valido\": false,\n  \"erros\": [\"marketing\", \"emoji_inadequado\", \"formato_variavel\", \"ortografia\"],\n  \"explicacao\": \"1. [marketing] Menção a 'SUPER desconto'\\n2. [emoji_inadequado] 😍🛒 removidos por tom comercial\\n3. [formato_variavel] {{Nome}} → {{nome}}\\n4. [ortografia] 'SUPER' em caixa alta desnecessária\",\n  \"mensagem_sugerida\": \"🌟 {{nome}}, Pantogar® está disponível para continuidade do seu tratamento.\"\n}";
    }

    private function processResponse(string $responseBody): array
    {
        try {
            $body = json_decode($responseBody, true, 512, JSON_THROW_ON_ERROR);

            if (!isset($body['response'])) {
                throw new RuntimeException('Resposta da API mal formatada');
            }

            return $this->extractJsonFromResponse($body['response']);

        } catch (JsonException $e) {
            return ['erro' => 'JSON inválido recebido', 'raw' => $responseBody];
        }
    }

    private function extractJsonFromResponse(string $content): array
    {
        $content = trim($content);

        // Tenta decodificar diretamente primeiro
        try {
            return json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            // Continua o processamento se falhar
        }

        // Extrai de blocos Markdown
        if (preg_match('/```json\s*({[\s\S]*?})\s*```/', $content, $matches)) {
            $content = $matches[1];
        } else {
            // Captura o maior objeto JSON possível
            preg_match('/\{[\s\S]*\}/', $content, $matches);
            $content = $matches[0] ?? $content;
        }

        try {
            return json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            error_log('JSON inválido: ' . $content); // Debug
            throw new RuntimeException('Nenhum JSON válido encontrado');
        }
    }
}
