# Guia de Testes - Refatoração Modular

## 🎯 Objetivo

Este guia contém todos os testes necessários para validar a refatoração modular do plugin **Pacientes, Avaliações e Bioimpedância**.

## ✅ Pré-requisitos

- WordPress funcionando
- Plugin ativado
- Acesso ao admin do WordPress
- Permissões de administrador

---

## 📋 Checklist de Testes

### 1. Teste de Carregamento do Plugin

- [ ] Plugin carrega sem erros PHP
- [ ] Nenhum erro no console JavaScript
- [ ] Nenhum erro no log do WordPress (`wp-content/debug.log`)

**Como verificar:**
1. Acesse o painel do WordPress
2. Verifique se não há mensagens de erro
3. Ative o WP_DEBUG no `wp-config.php` e verifique o arquivo de log

---

## 🧑 2. Testes - Paciente

### 2.1. Criar Novo Paciente

- [ ] Acessar "Pacientes" → "Adicionar Novo"
- [ ] Preencher todos os campos:
  - Nome
  - Gênero
  - Data de Nascimento
  - Altura (cm)
  - Celular/WhatsApp
  - E-mail
- [ ] Clicar em "Publicar"
- [ ] Verificar se os dados foram salvos corretamente

### 2.2. Editar Paciente Existente

- [ ] Abrir um paciente existente
- [ ] Modificar algum campo
- [ ] Salvar
- [ ] Reabrir e verificar se a alteração foi persistida

### 2.3. Metaboxes do Paciente

#### Metabox: Dados do Paciente
- [ ] Todos os campos aparecem
- [ ] Dados são salvos corretamente
- [ ] Validação de campos funciona

#### Metabox: Avaliações do Paciente
- [ ] Lista de avaliações aparece (se houver)
- [ ] Botão "Cadastrar Avaliação" funciona
- [ ] Ao clicar, abre nova avaliação já vinculada ao paciente

#### Metabox: Bioimpedâncias do Paciente
- [ ] Lista de bioimpedâncias aparece (se houver)
- [ ] Botão "Cadastrar Bioimpedância" funciona
- [ ] Ao clicar, abre nova bioimpedância já vinculada ao paciente

### 2.4. Proteção de Criação Direta

- [ ] Tentar criar avaliação sem paciente (deve redirecionar)
- [ ] Tentar criar bioimpedância sem paciente (deve redirecionar)

**Resultado Esperado:** Sistema redireciona para lista de pacientes

---

## 📝 3. Testes - Avaliação

### 3.1. Criar Nova Avaliação

- [ ] A partir de um paciente, clicar em "Cadastrar Avaliação"
- [ ] Verificar se paciente está vinculado automaticamente
- [ ] Preencher todos os formulários:
  - Anamnese (Q.P., H.D.A., Objetivos)
  - Hábitos de vida
  - Antecedentes patológicos
  - Histórico ginecológico (se aplicável)
- [ ] Salvar
- [ ] Verificar se título foi gerado: "Nome do Paciente - Avaliação - ID"

### 3.2. Metaboxes da Avaliação

#### Metabox: Paciente Vinculado
- [ ] Nome do paciente aparece
- [ ] Link para editar paciente funciona

#### Metabox: Anamnese
- [ ] Campos Q.P., H.D.A., Objetivos aparecem
- [ ] Dados são salvos

#### Metabox: Hábitos de Vida
- [ ] Campos condicionais funcionam (mostrar/ocultar)
- [ ] Exemplo: Ao selecionar "Sim" em "Consome bebida alcoólica", campo "Frequência" aparece
- [ ] Todos os campos salvam corretamente

#### Metabox: Antecedentes Patológicos
- [ ] Campos condicionais funcionam
- [ ] Dados são salvos

#### Metabox: Histórico Ginecológico
- [ ] Campos condicionais funcionam
- [ ] Dados são salvos

### 3.3. Save Handler

- [ ] Status da avaliação é sempre "Publicado"
- [ ] Título é atualizado automaticamente
- [ ] Nonce de segurança funciona

---

## 🔬 4. Testes - Bioimpedância

### 4.1. Criar Nova Bioimpedância

- [ ] A partir de um paciente, clicar em "Cadastrar Bioimpedância"
- [ ] Verificar se paciente está vinculado automaticamente
- [ ] Preencher dados de bioimpedância:
  - Peso (kg)
  - Gordura Corporal (%)
  - Músculo Esquelético (%)
  - Gordura Visceral (nível)
  - Metabolismo Basal (kcal)
  - Idade Corporal (anos)
- [ ] Salvar

### 4.2. Metabox: Paciente Vinculado

- [ ] Nome do paciente aparece
- [ ] Link para editar paciente funciona
- [ ] Após publicar, botão "Abrir Relatório Completo" aparece
- [ ] Link de compartilhamento é gerado
- [ ] Ao clicar no link, ele é copiado automaticamente
- [ ] Verificador de permalink ruim funciona (se aplicável)

### 4.3. Metabox: Dados de Bioimpedância

- [ ] Todos os 6 campos aparecem com ícones
- [ ] Placeholders nos campos funcionam
- [ ] Dados são salvos corretamente
- [ ] Alerta informativo aparece

### 4.4. Metabox: Avatares (OMS)

**Pré-requisito:** Paciente com gênero e altura preenchidos

- [ ] Avatares aparecem em linha horizontal
- [ ] Avatar correto é destacado baseado no IMC
- [ ] Classificação OMS é calculada
- [ ] Badge com classificação aparece
- [ ] Informações de IMC são exibidas

**Teste com diferentes IMCs:**
- [ ] IMC < 18.5 → Avatar "Baixo Peso" destacado
- [ ] IMC 18.5-24.9 → Avatar "Normal" destacado
- [ ] IMC 25-29.9 → Avatar "Sobrepeso" destacado
- [ ] IMC ≥ 30 → Avatar "Obesidade" destacado

### 4.5. Metabox: Composição Corporal

- [ ] Três colunas aparecem corretamente
- [ ] Cards com dados são exibidos:
  - Peso + Classificação
  - IMC + Classificação
  - Gordura Corporal + Classificação
  - Músculo Esquelético + Classificação
  - Gordura Visceral + Classificação
  - Metabolismo Basal
  - Idade Corporal (com diferença da idade real)
- [ ] Badges de classificação aparecem
- [ ] Cores dos badges estão corretas
- [ ] Legenda de classificações aparece no final

### 4.6. Metabox: Diagnóstico de Obesidade

- [ ] 4 cards aparecem:
  - IMC
  - Gordura Corporal
  - Gordura Visceral
  - Peso
- [ ] Classificações OMS são calculadas
- [ ] Alertas de risco aparecem quando aplicável
- [ ] Interpretação inteligente é gerada

**Teste com diferentes cenários:**
- [ ] Todos indicadores normais → Mensagem de composição adequada
- [ ] 1 indicador alterado → Alertas específicos aparecem
- [ ] Múltiplos indicadores alterados → Múltiplos alertas aparecem

### 4.7. Metabox: Histórico

**Pré-requisito:** Paciente com pelo menos 2 bioimpedâncias

- [ ] Header com total de avaliações aparece
- [ ] 4 gráficos são exibidos:
  - Evolução do Peso
  - Evolução do IMC
  - Gordura Corporal
  - Músculo Esquelético
- [ ] Gráficos são renderizados com Chart.js
- [ ] Linhas dos gráficos conectam os pontos
- [ ] Tabela comparativa aparece:
  - Primeira avaliação
  - Avaliação atual
  - Diferença calculada
- [ ] Ícones de evolução (📈 📉) aparecem corretamente
- [ ] Cores indicam melhora/piora
- [ ] Análise de progresso é gerada

**Teste de Evolução:**
- [ ] Redução de peso → Ícone 📈 verde
- [ ] Aumento de músculo → Ícone 📈 verde
- [ ] Aumento de gordura → Ícone 📉 vermelho

---

## 🧮 5. Testes - Funções Compartilhadas

### 5.1. Cálculo de Faixa de Peso Ideal

```
Paciente: Altura = 170cm
Resultado Esperado: 53.5 - 72.0 kg
```

- [ ] Função `pab_calc_faixa_peso_ideal()` retorna valores corretos

### 5.2. Cálculo de Idade Real

```
Paciente: Data Nascimento = 01/01/1990
Data Atual: 2025
Resultado Esperado: 35 anos (aproximadamente)
```

- [ ] Função `pab_calc_idade_real()` retorna idade correta

### 5.3. Classificação OMS

**Teste IMC:**
- [ ] IMC 17 → "Baixo Peso"
- [ ] IMC 22 → "Normal"
- [ ] IMC 27 → "Sobrepeso"
- [ ] IMC 32 → "Obesidade Grau I"

**Teste Gordura Corporal (Homem, 30 anos):**
- [ ] GC 10% → "Baixa/Essencial"
- [ ] GC 18% → "Normal"
- [ ] GC 25% → "Limítrofe/Sobrepeso"

**Teste Gordura Visceral:**
- [ ] GV 5 → "Normal"
- [ ] GV 12 → "Alto"
- [ ] GV 16 → "Muito Alto"

---

## 🔗 6. Testes de Integração

### 6.1. Fluxo Completo: Paciente → Avaliação

1. [ ] Criar novo paciente
2. [ ] Cadastrar avaliação para o paciente
3. [ ] Verificar que avaliação aparece na lista do paciente
4. [ ] Verificar vínculo correto

### 6.2. Fluxo Completo: Paciente → Bioimpedância

1. [ ] Criar novo paciente com dados completos
2. [ ] Cadastrar primeira bioimpedância
3. [ ] Cadastrar segunda bioimpedância (após alguns dias)
4. [ ] Verificar histórico de evolução
5. [ ] Verificar gráficos

### 6.3. Fluxo Completo: Múltiplas Bioimpedâncias

1. [ ] Criar paciente
2. [ ] Cadastrar 3+ bioimpedâncias com datas diferentes
3. [ ] Verificar que gráficos mostram evolução temporal
4. [ ] Verificar tabela comparativa
5. [ ] Verificar análise de progresso

---

## 🌐 7. Testes de Frontend Público

### 7.1. Relatório Público de Bioimpedância

- [ ] Publicar uma bioimpedância
- [ ] Copiar link de compartilhamento
- [ ] Abrir em navegador anônimo/privado
- [ ] Verificar se relatório público aparece
- [ ] Verificar todos os elementos visuais
- [ ] Verificar se dados estão corretos

---

## 🐛 8. Testes de Erros e Edge Cases

### 8.1. Dados Incompletos

- [ ] Criar bioimpedância sem peso → Sistema não quebra
- [ ] Criar bioimpedância sem paciente vinculado → Alerta aparece
- [ ] Criar paciente sem altura → Sistema não quebra

### 8.2. Valores Extremos

- [ ] Peso = 0.1 kg → Sistema aceita ou valida?
- [ ] Peso = 500 kg → Sistema aceita ou valida?
- [ ] Gordura Corporal = 0% → Sistema aceita
- [ ] Gordura Corporal = 100% → Sistema aceita

### 8.3. Deleção

- [ ] Deletar paciente → Avaliações órfãs?
- [ ] Deletar paciente → Bioimpedâncias órfãs?
- [ ] Mover para lixeira → Sistema não executa save handler

---

## 📊 9. Testes de Performance

- [ ] Criar 50+ bioimpedâncias para um paciente
- [ ] Verificar se gráficos carregam em tempo aceitável
- [ ] Verificar se histórico não trava
- [ ] Verificar consumo de memória

---

## 🔒 10. Testes de Segurança

### 10.1. Nonces

- [ ] Tentar salvar sem nonce → Bloqueado
- [ ] Tentar salvar com nonce inválido → Bloqueado

### 10.2. Sanitização

- [ ] Inserir `<script>alert('XSS')</script>` no nome → Sanitizado
- [ ] Inserir SQL injection em campos → Sanitizado

### 10.3. Permissões

- [ ] Usuário sem permissão não pode editar
- [ ] Usuário sem permissão não pode deletar

---

## 📱 11. Testes de Responsividade

- [ ] Abrir em desktop → Layout correto
- [ ] Abrir em tablet → Layout adapta
- [ ] Abrir em mobile → Layout adapta
- [ ] Gráficos aparecem corretamente em todas as telas

---

## 🎨 12. Testes Visuais

### 12.1. Cores e Badges

- [ ] Badge "normal" → Verde
- [ ] Badge "acima1" → Amarelo
- [ ] Badge "acima2" → Laranja
- [ ] Badge "alto1" → Vermelho

### 12.2. Ícones

- [ ] Ícones de emoji aparecem corretamente
- [ ] Ícones não quebram layout

### 12.3. Animações

- [ ] Classe `pab-fade-in` funciona
- [ ] Transições são suaves

---

## ✅ Checklist Final

Após completar todos os testes acima:

- [ ] **0 erros PHP** no log
- [ ] **0 erros JavaScript** no console
- [ ] **Todos os dados salvam** corretamente
- [ ] **Todas as metaboxes aparecem** como esperado
- [ ] **Gráficos funcionam** com Chart.js
- [ ] **Vinculações** entre post types funcionam
- [ ] **Cálculos OMS** estão corretos
- [ ] **Histórico** mostra evolução temporal
- [ ] **Relatório público** funciona
- [ ] **Performance** está aceitável

---

## 🚀 Aprovação para Produção

**Somente após todos os testes passarem:**

1. [ ] Remover arquivos `.bak`
2. [ ] Criar backup completo do site
3. [ ] Fazer commit no Git
4. [ ] Deploy para produção
5. [ ] Testar novamente em produção

---

## 📞 Suporte

Se encontrar qualquer problema durante os testes:

1. **Verificar logs**: `wp-content/debug.log`
2. **Console do navegador**: F12 → Console
3. **Arquivos de backup**: `includes/*.php.bak` (código original)

---

**Última Atualização:** Janeiro 2025  
**Versão do Plugin:** 1.0.9  
**Status da Refatoração:** ✅ 100% Completo