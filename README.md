# **API_LLM - Validação e correção de Templates de WhatsApp**

API para validação e correção de templates de WhatsApp, utilizando LLM para verificar ortografia, gramática, compliance e uso adequado de emojis, especialmente em contextos médico-farmacêuticos.

## **Visão Geral**

Esta API utiliza o modelo DeepSeek para analisar mensagens e garantir que estejam em conformidade com diretrizes específicas para comunicações na área médica. 

A validação inclui:

- Verificação de erros ortográficos e gramaticais
- Identificação de elementos de marketing inadequados
- Avaliação do uso de emojis conforme diretrizes estabelecidas
- Preservação de variáveis e elementos técnicos válidos

### Requisitos

- PHP 7.4+
- Composer
- Acesso a uma instância do DeepSeek (local ou remota)
- Extensão cURL do PHP habilitada
 
## **Instalação**

1. Clone o repositório
```bash
git clone https://github.com/gab3mioni/API_LLM.git
```

2. Acesse o repositório
```
cd api-llm
```

3. Instale as dependências
```bash
composer install
```

4. Configure as variáveis de ambiente
```bash
cp .env.example .env
```

5. Edite o arquivo `.env` e adicione as informações necessárias
```
DEEPSEEK_ENDPOINT=http://localhost:11434/api/generate
DEEPSEEK_MODEL=deepseek-r1:32b
```

5. Inicie o servidor embutido do php
```bash
php -S localhost:8080 -t app/public
```

## **ENDPOINT**

### **POST /validar-mensagem**

Corpo da requisição:

```json
{
  "mensagem": "Olaaaa, todo bem.."
}
```

Resposta:

```json
{
  "valido": true|false,
  "erros": ["erro1", "erro2", ...],
  "explicacao": "1. [erro1] explicação\n2. [erro2] explicação\n...",
  "mensagem_sugerida": "Olá, tudo bem?"
}
```

## **Exemplos práticos**

### **Exemplo 1: Mensagem válida**
```bash
curl --location 'http://localhost:8080/validar-mensagem' \
--header 'Content-Type: application/json' \
--data '{
  "mensagem": "Você sabia que o tratamento correto pode transformar a forma como o TDAH impacta a vida? 🌈 Seja com medicamentos, terapia ou mudanças na rotina, cada passo ajuda. Quer conhecer mais sobre a jornada de tratamento?"
}'
```

Retorno:

```json
{
    "valido": true,
    "erros": [],
    "explicacao": "",
    "mensagem_sugerida": "Você sabia que o tratamento correto pode transformar a forma como o TDAH impacta a vida? 🌈 Seja com medicamentos, terapia ou mudanças na rotina, cada passo ajuda. Quer conhecer mais sobre a jornada de tratamento?"
}
```

### **Exemplo 2: Mensagem inválida**
```bash
curl --location 'http://localhost:8080/validar-mensagem' \
--header 'Content-Type: application/json' \
--data '{
  "mensagem": "Voçe sabia que o tratamento correto pode transformar a forma como o TDAH impacta a vida? 🌈 Seja com medddddicamentos, terapia ou mudanças na rotina, cada passo ajuda. Quer conhecer mais sobre a jornada de tratamento!"
}'
```

```json
{
  "valido": true,
  "erros": [
    "ortografia",
    "grammatica"
  ],
  "explicacao": "1. [ortografia] 'medddddicamentos' → 'medicamentos'\n2. [grammatica] 'Voçe' → 'Vocês'",
  "mensagem_sugerida": "Vocês sabiam que o tratamento correto pode transformar a forma como o TDAH impacta a vida? 🌈 Seja com medicamentos, terapia ou mudanças na rotina, cada passo ajuda. Quer conhecer mais sobre a jornada de tratamento!"
}
```

## **Testes Unitários**

Execute os testes unitários com o PHPUnit

```bash
./vendor/bin/phpunit
```

## **Estrutura**

- app/: Código principal da aplicação
  - Controllers/: Controladores da API
  - Services/: Serviços da aplicação
  - Interface/: Interfaces para injeção de dependência
- public/: Ponto de entrada da API
- tests/: Testes unitários
- vendor/: Dependências do gerenciadas pelo Composer

## **Regras de validação**

- Emojis permitidos (máx 3): ★✨✓➡️🩺📅 (manter relevância médica)
- Emojis proibidos: 🛒🎉🤑💲🚨 (evitar tom comercial/alegre)
- Variáveis devem manter formato {{nome}}
- Sem menções a preços/descontos
- Termos técnicos com ® devem ser preservados

### **Recursos Adicionais**

- [Como configurar e executar o DeepSeek R1 localmente com o Ollama
  ](https://www.datacamp.com/pt/tutorial/deepseek-r1-ollama)