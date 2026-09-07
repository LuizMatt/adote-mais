# Backend Roadmap — Adota+ API

Este documento organiza as etapas de desenvolvimento do backend da **Adota+ API**, definindo a ordem dos passos, dependências técnicas, matriz de controle de acesso e checklist de progresso.

> ⚠️ **Convenção de Código:** Todo o código-fonte, nomes de tabelas, colunas, variáveis, rotas, query params e valores de enums são estritamente em **INGLÊS** (`name`, `species`, `size`, `vaccines`, `status`, etc.).

---

## 1. Visão Geral da Arquitetura

- **Framework**: PHP 8.2+ e Laravel 11
- **Banco de Dados**: SQLite
- **Autenticação**: Laravel Sanctum (Bearer Tokens)
- **Armazenamento de Mídia**: Laravel Storage (disco `public` + link simbólico)
- **Documentação de Steps**: Pasta `backend/docs/`

---

## 2. Matriz de Controle de Acesso (RBAC)

O sistema possui dois níveis de usuário interno (`admin` e `agent`), além do acesso público não autenticado para consulta ao catálogo:

| Funcionalidade | Endpoint | Público | Agent | Admin |
| :--- | :--- | :---: | :---: | :---: |
| **Listar pets (com filtros)** | `GET /api/pets` | ✅ Sim | ✅ Sim | ✅ Sim |
| **Visualizar detalhes do pet** | `GET /api/pets/{id}` | ✅ Sim | ✅ Sim | ✅ Sim |
| **Login / Obter token** | `POST /api/login` | ✅ Sim | ✅ Sim | ✅ Sim |
| **Ver dados do usuário logado** | `GET /api/user` | ❌ Não | ✅ Sim | ✅ Sim |
| **Logout / Revogar token** | `POST /api/logout` | ❌ Não | ✅ Sim | ✅ Sim |
| **Cadastrar novo pet** | `POST /api/pets` | ❌ Não | 🚫 **Bloqueado (403)** | ✅ **Permitido** |
| **Atualizar dados gerais do pet** | `PUT /api/pets/{id}` | ❌ Não | ✅ Sim | ✅ Sim |
| **Atualizar status do pet** | `PATCH /api/pets/{id}/status` | ❌ Não | ✅ Sim | ✅ Sim |
| **Excluir pet** | `DELETE /api/pets/{id}` | ❌ Não | 🚫 **Bloqueado (403)** | ✅ **Permitido** |

---

## 3. Checklist dos Steps de Desenvolvimento

Execute os passos na sequência indicada. Cada arquivo possui o roteiro técnico detalhado, códigos e comandos de validação:

- [x] **[Passo 1: Modelos e Banco de Dados](file:///c:/Users/luizm/Desktop/adote+/backend/docs/01-database-models.md)**
  - Migração para adicionar coluna `role` (`admin` / `agent`) na tabela `users`.
  - Migração da tabela `pets` (colunas em inglês: `name`, `species`, `size`, `approximate_age`, `gender`, `status`, `vaccines` em JSON, etc.).
  - Models `User` e `Pet` com casts e helpers de perfil (`isAdmin()`).
  - Seeders com contas de `admin`, `agent` e dados fictícios de pets.

- [ ] **[Passo 2: Autenticação, Sanctum e Papéis](file:///c:/Users/luizm/Desktop/adote+/backend/docs/02-auth-roles-sanctum.md)**
  - Configuração do Sanctum para emissão e revogação de Bearer Tokens.
  - Implementação de `AuthController` (`login`, `logout`, `me`).
  - Middleware / Gate / Policy para validação de `role = admin`.

- [ ] **[Passo 3: CRUD de Pets e Regras de Negócio](file:///c:/Users/luizm/Desktop/adote+/backend/docs/03-pets-api-crud.md)**
  - Implementação do `PetController` (rotas públicas e protegidas).
  - Form Requests de validação (`StorePetRequest`, `UpdatePetRequest`, `UpdatePetStatusRequest`).
  - `PetResource` para padronização do contrato JSON e URL de fotos (`photo_url`).
  - Regra de negócio de adoção (exigência de `adopter_name` no status `adopted`).

- [ ] **[Passo 4: Upload de Mídia, Storage e CORS](file:///c:/Users/luizm/Desktop/adote+/backend/docs/04-upload-storage-cors.md)**
  - Configuração do disco `public` e link simbólico (`php artisan storage:link`).
  - Tratamento de upload de fotos (validação de tamanho/formato e remoção de fotos antigas).
  - Configuração de CORS em `config/cors.php` para integrar com o frontend SPA.

- [ ] **[Passo 5: Testes e Validação da API](file:///c:/Users/luizm/Desktop/adote+/backend/docs/05-testes-e-integracao.md)**
  - Criação de testes de integração automatizados (`AuthTest`, `PetCrudTest`).
  - Roteiro de testes manuais via cURL e coleção de requisições.
  - Validação final com `php artisan test`.

---

## 4. Guia Rápido de Ambiente Local

Comandos essenciais para iniciar o backend:

```bash
# Entrar na pasta do backend
cd backend

# Instalar dependências (caso não tenha instalado)
composer install

# Criar arquivo de variáveis de ambiente
cp .env.example .env

# Gerar chave da aplicação
php artisan key:generate

# Criar banco SQLite e rodar migrações com dados iniciais
php artisan migrate:fresh --seed

# Criar link simbólico do storage de uploads
php artisan storage:link

# Iniciar servidor local da API (porta 8000)
php artisan serve
```
