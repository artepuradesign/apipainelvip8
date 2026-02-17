

## Corrigir Criacao de Usuario e Adicionar Edicao de Dias nos Dois Modais

### Problemas Identificados

1. **Novo Usuario nao salva dados corretamente**: O `handleAddUser` em `GestaoUsuarios.tsx` usa `referralRegistrationService.registerWithReferral` que envia apenas dados basicos (email, password, full_name, user_role, aceite_termos) para `/auth/register`. Campos como CPF, telefone, plano, saldo, datas nao sao enviados. Alem disso, o `AddUserModal` nao repassa os dados de plano/saldo/datas para o componente pai.

2. **Falta opcao de alterar dias manualmente**: Ambos os modais permitem apenas adicionar os dias fixos do plano. O administrador deveria poder digitar quantos dias deseja adicionar.

### Solucao

**Estrategia em 2 etapas para criacao de usuario** (como sugerido pelo usuario):
1. Primeiro: Registrar o usuario via `/auth/register` (cria usuario basico)
2. Segundo: Atualizar os dados complementares via `adminUserApiService.updateUser` (plano, saldo, datas, CPF, etc.)

### Alteracoes por Arquivo

**1. `src/components/dashboard/users/AddUserModal.tsx`**
- Alterar `onSubmit` para receber dados extras (planBalance, planStartDate, planEndDate, planDiscount)
- No `handleSubmit`, chamar `onSubmit` passando os dados completos do plano
- Adicionar campo de input editavel para quantidade de dias (ao lado do switch "Adicionar dias")
- O administrador pode digitar manualmente quantos dias quer adicionar

**2. `src/components/dashboard/users/EditUserModal.tsx`**
- Adicionar campo de input editavel para quantidade de dias (ao lado do switch "Adicionar dias")
- Quando o switch estiver ativo, o admin pode alterar o numero de dias manualmente
- A data de termino recalcula automaticamente ao alterar o valor

**3. `src/pages/dashboard/GestaoUsuarios.tsx`**
- Alterar `handleAddUser` para:
  - Passo 1: Registrar usuario via `referralRegistrationService.registerWithReferral`
  - Passo 2: Obter o ID do usuario criado da resposta
  - Passo 3: Chamar `adminUserApiService.updateUser` com os dados complementares (tipoplano, saldo, saldo_plano, cpf, telefone, endereco, data_inicio, data_fim)
- Atualizar a interface do `AddUserModal` para receber callback com dados extras
- Alterar o `onSubmit` do `AddUserModal` para aceitar parametros de plano

### Detalhes Tecnicos

```text
FLUXO DE CRIACAO (CORRIGIDO):

AddUserModal (handleSubmit)
    |
    v
GestaoUsuarios (handleAddUser) recebe dados extras
    |
    v
[PASSO 1] POST /auth/register  -->  { email, password, full_name, user_role, aceite_termos, cpf, telefone }
    |
    v  (resposta com user.id)
    |
[PASSO 2] PUT /dashboard-admin/users/{id}  -->  { tipoplano, saldo, saldo_plano, data_inicio, data_fim, cpf, telefone, endereco }
    |
    v
Recarrega lista de usuarios
```

Campo de dias editavel nos dois modais:
- Input numerico ao lado do switch "Adicionar dias"
- Valor padrao: `duration_days` do plano selecionado
- Ao alterar o valor, recalcula a data de termino automaticamente
- Desabilitado quando o switch esta desligado

