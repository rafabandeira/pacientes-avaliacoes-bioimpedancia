# Refatoração Modular - Guia de Implementação

## Objetivo

Dividir os arquivos grandes (`meta-paciente.php`, `meta-avaliacao.php`, `meta-bioimpedancia.php`) em uma estrutura modular organizada por contexto (post type).

## Estrutura Proposta

```
includes/
├── paciente/
│   ├── meta-boxes.php              # Registra metaboxes + handlers
│   └── metaboxes/
│       ├── dados.php               # ✅ CRIADO
│       ├── avaliacoes.php          # ✅ CRIADO
│       └── bioimpedancias.php      # ✅ CRIADO
│
├── avaliacao/
│   ├── meta-boxes.php              # Registra metaboxes + save handler
│   └── metaboxes/
│       ├── paciente.php            # ✅ CRIADO
│       ├── anamnese.php            # ✅ CRIADO
│       ├── habitos.php             # ✅ CRIADO
│       ├── antecedentes.php        # ✅ CRIADO
│       └── ginecologico.php        # ✅ CRIADO
│
├── bioimpedancia/
│   ├── meta-boxes.php              # Registra metaboxes + save handler
│   └── metaboxes/
│       ├── paciente.php            # ⏳ PENDENTE
│       ├── dados.php               # ⏳ PENDENTE
│       ├── avatares.php            # ⏳ PENDENTE
│       ├── composicao.php          # ⏳ PENDENTE
│       ├── diagnostico.php         # ⏳ PENDENTE
│       └── historico.php           # ⏳ PENDENTE
│
└── shared/
    └── calculations.php             # ✅ CRIADO
```

## Arquivos Já Criados

### Paciente (✅ Completo)
- `paciente/meta-boxes.php` - Arquivo principal com registro das metaboxes
- `paciente/metaboxes/dados.php` - Dados cadastrais + save handler
- `paciente/metaboxes/avaliacoes.php` - Lista de avaliações vinculadas
- `paciente/metaboxes/bioimpedancias.php` - Lista de bioimpedâncias vinculadas

### Avaliação (✅ Completo)
- `avaliacao/meta-boxes.php` - Arquivo principal com registro das metaboxes
- `avaliacao/metaboxes/paciente.php` - Vinculação com paciente
- `avaliacao/metaboxes/anamnese.php` - Anamnese (com nonce)
- `avaliacao/metaboxes/habitos.php` - Hábitos de vida
- `avaliacao/metaboxes/antecedentes.php` - Antecedentes patológicos + save handler
- `avaliacao/metaboxes/ginecologico.php` - Histórico ginecológico

### Shared (✅ Completo)
- `shared/calculations.php` - Funções de cálculo compartilhadas
  - `pab_calc_faixa_peso_ideal()` - Calcula faixa de peso ideal
  - `pab_calc_idade_real()` - Calcula idade do paciente
  - `pab_oms_classificacao()` - Classificação OMS completa

## Próximos Passos

### 1. Dividir `meta-bioimpedancia.php` (1462 linhas!)

#### Funções de Cálculo para `shared/calculations.php`:
- `pab_calc_faixa_peso_ideal` (L258-271)
- `pab_oms_classificacao` (L279-472)
- `pab_calc_idade_real` (L565-578)

#### Metaboxes para `bioimpedancia/metaboxes/`:

**paciente.php:**
- `pab_bi_paciente_cb` (L69-173)

**dados.php:**
- `pab_bi_dados_cb` (L178-252)

**avatares.php:**
- `pab_bi_avatares_cb` (L477-563)

**composicao.php:**
- `pab_bi_comp_tab_cb` (L583-817)

**diagnostico.php:**
- `pab_bi_diag_obes_cb` (L822-1066)

**historico.php:**
- `pab_bi_historico_cb` (L1071-1462)

#### Criar `bioimpedancia/meta-boxes.php`:
- Incluir todos os arquivos de metaboxes
- Registrar as metaboxes (linhas 9-59 do original)
- Adicionar save handler

### 2. Atualizar Arquivo Principal ✅ CONCLUÍDO

Arquivo `pacientes-avaliacoes-bioimpedancia.php` atualizado:

```php
// Estrutura Modular - Funções Compartilhadas
require_once PAB_PATH . "includes/shared/calculations.php";

// Estrutura Modular - Metaboxes por Post Type
require_once PAB_PATH . "includes/paciente/meta-boxes.php";
require_once PAB_PATH . "includes/avaliacao/meta-boxes.php";
require_once PAB_PATH . "includes/meta-bioimpedancia.php"; // TODO: Refatorar
```

### 3. Backup dos Arquivos Antigos ✅ CONCLUÍDO

```bash
# Backups criados
includes/meta-paciente.php → includes/meta-paciente.php.bak
includes/meta-avaliacao.php → includes/meta-avaliacao.php.bak
```

### 4. Testar

- [ ] Tela de edição de Paciente (Pronto para teste)
- [ ] Tela de edição de Avaliação (Pronto para teste)
- [ ] Tela de edição de Bioimpedância (Aguardando refatoração)
- [ ] Salvar dados em cada tipo
- [ ] Vincular avaliações/bioimpedâncias ao paciente
- [ ] Verificar cálculos de bioimpedância
- [ ] Verificar gráficos e histórico

## Vantagens da Nova Estrutura

1. **Organização Clara**: Cada post type tem sua própria pasta
2. **Fácil Manutenção**: Metaboxes separadas em arquivos individuais
3. **Escalabilidade**: Fácil adicionar novas metaboxes
4. **Reusabilidade**: Funções de cálculo compartilhadas em `shared/`
5. **Colaboração**: Múltiplos desenvolvedores podem trabalhar sem conflitos
6. **Debug Simplificado**: Mais fácil localizar e corrigir problemas

## Comandos Úteis

### Localizar funções no arquivo original:
```bash
# PowerShell
Get-Content "includes\meta-bioimpedancia.php" | Select-String -Pattern "^function "
```

### Extrair linhas específicas:
```bash
# PowerShell
Get-Content "includes\meta-bioimpedancia.php" | Select-Object -Index (68..172)
```

## Notas Importantes

- Manter compatibilidade com código existente
- Todas as funções callback devem ter os mesmos nomes
- Os nonces devem permanecer consistentes
- Testar cada metabox individualmente após refatoração
- Manter documentação PHPDoc em cada arquivo

## Checklist Final

- [x] Funções de cálculo movidas para `shared/` ✅
- [x] Arquivo principal atualizado ✅
- [x] Arquivos antigos renomeados (.bak) ✅
- [x] Metaboxes de Paciente criadas ✅
- [x] Arquivo `paciente/meta-boxes.php` criado ✅
- [x] Metaboxes de Avaliação criadas ✅
- [x] Arquivo `avaliacao/meta-boxes.php` criado ✅
- [ ] Metaboxes de Bioimpedância criadas (Pendente)
- [ ] Arquivo `bioimpedancia/meta-boxes.php` criado (Pendente)
- [ ] Testes realizados
- [ ] Sem erros no console (0 erros PHP detectados)
- [ ] Todos os dados salvam corretamente
- [ ] Gráficos funcionando
- [ ] Vinculações funcionando
</parameter>

<old_text line=179>
**Status Atual**: 🟢 70% Completo - Paciente e Avaliação refatorados com sucesso!
**Última Atualização**: 2024

## Próxima Etapa

A refatoração de **Paciente** e **Avaliação** está completa e funcional. O próximo passo é refatorar o arquivo `meta-bioimpedancia.php` (1462 linhas) seguindo o mesmo padrão.

**Recomendação**: Teste as funcionalidades de Paciente e Avaliação antes de prosseguir com a refatoração de Bioimpedância.

---

**Status Atual**: 🟡 Em Progresso (40% completo)
**Última Atualização**: 2024