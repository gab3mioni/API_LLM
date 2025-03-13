# Rodada de Testes API_LLM - Cenário 1 Pantogar

### **Ambiente de testes:**

- 1. Windows 10, via Postman, utilizando o modelo deepseek-r1:14b, rodando localmente via Ollama.

## **Ambiente de testes 1**

**1. Paciente cadastrado após a primeira compra:**

```bash
curl --location 'http://localhost:8080/validar-mensagem' \
--header 'Content-Type: application/json' \
--data '{
  "mensagem": "👋 Olá, {{nome}}! Seja bem-vindo(a) ao programa Saúde em Evolução! 🎉 🌟 Você optou por receber contatos do Saúde em Evolução para te ajudar com o seu tratamento com Pantogar®. 💊 Como usar Pantogar? É simples! Tome 1 cápsula com um copo de água 🥤 após as refeições, todos os dias ou conforme orientação médica. Manter a rotina do tratamento é essencial para alcançar os melhores resultados! ✨ Cuidar dos seus cabelos é também cuidar de você! Estamos aqui para te apoiar nessa jornada. 📲 Caso queira encerrar sua participação no acompanhamento, envie '\''SAIR'\'' a qualquer momento. Você pode contar conosco sempre que precisar! Atenciosamente, Equipe Saúde em Evoluçãou"
}'
```

Resultado:

```json
{
  "valido": false,
  "erros": [
    "emoji_proibido",
    "marketing",
    "ortografia"
  ],
  "explicacao": [
    "1. [emoji_proibido] O emoji 🎉 foi removido pois está na lista de emojis proibidos.",
    "2. [marketing] A frase 'Cuidar dos seus cabelos é também cuidar de você!' foi removida por conter elementos de marketing e não ser diretamente relacionada ao tratamento médico.",
    "3. [ortografia] O erro de digitação 'Evoluçãou' foi corrigido para 'Evolução'."
  ],
  "mensagem_sugerida": "👋 Olá, {{nome}}! Seja bem-vindo(a) ao programa Saúde em Evolução! 🌟 Você optou por receber contatos do Saúde em Evolução para te ajudar com o seu tratamento com Pantogar®. Tome 1 cápsula com um copo de água 🥤 após as refeições, todos os dias ou conforme orientação médica. Manter a rotina do tratamento é essencial para alcançar os melhores resultados! Caso queira encerrar sua participação no acompanhamento, envie 'SAIR' a qualquer momento. Você pode contar conosco sempre que precisar! Atenciosamente, Equipe Saúde em Evolução."
}
```

**2. Após 7 dias com ou sem compra:**

```bash
curl --location 'http://localhost:8080/validar-mensagem' \
--header 'Content-Type: application/json' \
--data '{
  "mensagem": "🎉 Você completou a sua primeira semana de tratamento! 👏🌟 ✨ Lembre-se: seguir corretamente as orientações do seu médico é essencial para alcançar os melhores resultados."
}'
```

Resultado:

```json
{
  "valido": false,
  "erros": [
    "emoji_proibido"
  ],
  "explicacao": "1. [emoji_proibido] O emoji 🎉 foi removido pois está na lista de emojis proibidos.",
  "mensagem_sugerida": "👏🌟 Você completou a sua primeira semana de tratamento! ✨ Lembre-se: seguir corretamente as orientações do seu médico é essencial para alcançar os melhores resultados."
}
```

**3. Caso responda "Sim" na mensagem anterior:**

```bash
curl --location 'http://localhost:8080/validar-mensagem' \
--header 'Content-Type: application/json' \
--data-raw '{
  "mensagem": "🎉 Que ótimo! Manter a disciplina no tratamento é essencial para alcançar os melhores resultados. Continue seguindo as orientações médicas e conte conosco para te apoiar nessa jornada! 📞 Se precisar de suporte adicional, entre em contato com o nosso SAC pelo telefone 0800 724 6522 ou envie um e-mail para faleconosco@biolabfarma.com.br."
}'
```

Resultado:

```json
{
  "valido": false,
  "erros": [
    "emoji_proibido"
  ],
  "explicacao": "1. [emoji_proibido] Os emojis 🎉 no início e final da mensagem foram removidos pois estão na lista de proibidos.",
  "mensagem_sugerida": "Que ótimo! Manter a disciplina no tratamento é essencial para alcançar os melhores resultados. Continue seguindo as orientações médicas e conte conosco para te apoiar nessa jornada! 📞 Se precisar de suporte adicional, entre em contato com o nosso SAC pelo telefone 0800 724 6522 ou envie um e-mail para faleconosco@biolabfarma.com.br."
}
```

**4. Caso responda "Não" na mensagem anterior:**

```bash
curl --location 'http://localhost:8080/validar-mensagem' \
--header 'Content-Type: application/json' \
--data-raw '{
  "mensagem": "😊 Tudo bem! Ainda dá tempo de iniciar o seu tratamento. Lembre-se de seguir a recomendação médica para obter os melhores resultados. 💡 Tome 1 cápsula com um copo de água 🥤 após as refeições, todos os dias, ou conforme a orientação do seu médico. 📞 Se tiver dúvidas ou precisar de ajuda, estamos à disposição pelo 0800 724 6522 ou envie um para faleconosco@biolabfarma.com.br."
}'
```

Resultado:

```json
{
    "valido": true,
    "erros": [],
    "explicacao": "",
    "mensagem_sugerida": "😊 Tudo bem! Ainda dá tempo de iniciar o seu tratamento. Lembre-se de seguir a recomendação médica para obter os melhores resultados. 💡 Tome 1 cápsula com um copo de água 🥤 após as refeições, todos os dias, ou conforme a orientação do seu médico. 📞 Se tiver dúvidas ou precisar de ajuda, estamos à disposição pelo 0800 724 6522 ou envie um para faleconosco@biolabfarma.com.br."
}
```

**5. Após 21 dias com compra:**

```bash
curl --location 'http://localhost:8080/validar-mensagem' \
--header 'Content-Type: application/json' \
--data-raw '{
  "mensagem": "🎉 Olá, {{nome}}! Vi que você comprou o seu produto nesses últimos 21 dias! 💊 É muito importante que você siga corretamente o tratamento prescrito pelo seu médico. Se precisar de suporte ou tiver dúvidas, estamos aqui para ajudar! 💙 📞 Duvidas? Fale com nosso SAC pelo 0800 724 6522 ou envie um e-mail para faleconosco@biolabfarma.com.br."
}'
```

Resultado:
```json
{
    "valido": false,
    "erros": [
        "marketing",
        "emoji_proibido",
        "formato_variavel",
        "ortografia"
    ],
    "explicacao": [
        "1. [marketing] A menção de que o usuário comprou o produto recentemente pode ser considerada uma abordagem comercial.",
        "2. [emoji_proibido] O emoji 🎉 é proibido por ter tom comercial.",
        "3. [formato_variavel] Nenhuma variável foi identificada incorretamente.",
        "4. [ortografia] 'Duvidas?' está mal escrito; a forma correta é 'Dúvidas.'"
    ],
    "mensagem_sugerida": "Olá, {{nome}}! Notamos que você adquiriu o produto recentemente. É crucial que você siga rigorosamente as instruções médicas fornecidas. Caso necessite de suporte ou tenha dúvidas, estamos disponíveis para ajudar. 📞 Entre em contato conosco pelo SAC 0800 724 6522 ou envie um e-mail para faleconosco@biolabfarma.com.br."
}
```