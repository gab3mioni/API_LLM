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
        return "Analise a seguinte mensagem para correções ortográficas, gramaticais e validação de compliance. Siga estas etapas CRITICAMENTE:

1. Verifique erros de português
2. Identifique elementos de marketing e converta para teor utilitário
3. Avalie uso de emojis conforme diretrizes.
4. Preserve variáveis nos formatos: {{nome}}
5. Liste TODOS os erros encontrados

**Critérios de Rejeição por Marketing:**
- Frases de boas-vindas promocionais (ex: \"Seja bem-vindo ao nosso programa!\", \"Aproveite nossa oferta especial!\")
- Chamadas para ação comerciais (ex: \"Compre agora\", \"Aproveite antes que acabe\")
- Menção a preços, descontos ou condições comerciais
- Uso de emojis comerciais proibidos: 🛒🎉🤑💲🚨
- Qualquer termo que induza engajamento comercial ao invés de informação transacional ou utilitária

**Regras de Validação:**

- Emojis que reforçarem o caráter comercial devem ser removidos, o resto deve ser mantido para manter a humanização.
- Variáveis válidas: {{var}}
- Números devem permanecer como algarismos (ex: 3, nunca três)
- Termos técnicos com ® devem ser preservados
- Tom médico-profissional obrigatório

Mensagem: \"$message\"

**Formato de Resposta EXIGIDO (JSON):**
{
  \"valido\": <bool>,
  \"erros\": [\"erro1\", \"erro2\", ...],
  \"explicacao\": \"1. [erro1] explicação\\n2. [erro2] explicação\\n...\",
  \"mensagem_sugerida\": \"texto com correções aplicadas\"
}

**Exemplo Completo Corrigido:**
{
  \"mensagem\": \"🌟 {{nome}}, como está seu tratamento com Pantogar®? ✨ Estamos aqui para te ajudar! Lembrando que é importante seguir as orientações do seu médico e que o tempo mínimo esperado de tratamento, conforme bula, é de 3 meses. 🎉 Caso necessite adquirir novamente o produto, verifique no site do programa Saúde em Evolução os descontos vigentes no site: 🔗 Acesse aqui Ou compre diretamente: 🛒 Pantogar® - Drogaria São Paulo 📞 Se tiver dúvidas, estamos à disposição. Entre em contato pelo SAC 0800 724 6522 ou envie um e-mail para faleconosco@biolabfarma.com.br.\",
  \"valido\": false,
  \"erros\": [\"marketing\", \"emoji_proibido\", \"formato_variavel\"],
  \"explicacao\": \"1. [marketing] Removida menção a descontos\\n2. [emoji_proibido] 🎉 e 🛒 removidos\\n3. [formato_variavel] {{nome}} mantido\",
  \"mensagem_sugerida\": \"🌟 {{nome}}, como está seu tratamento com Pantogar®? 🌟 Estamos aqui para te ajudar! Lembrando que é importante seguir as orientações do seu médico e que o tempo mínimo esperado de tratamento, conforme bula, é de 3 meses. Caso necessite adquirir novamente o produto, verifique disponibilidade no programa Saúde em Evolução. 📞 Se tiver dúvidas, estamos à disposição. Entre em contato pelo SAC 0800 724 6522 ou envie um e-mail para faleconosco@biolabfarma.com.br.\"
}";
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
