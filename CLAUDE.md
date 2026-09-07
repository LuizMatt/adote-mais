# CLAUDE.md — Adota+

Este arquivo documenta a arquitetura e as convenções do projeto **Adota+** para uso do Claude Code (ou qualquer dev) durante a implementação. Projeto acadêmico, escopo intencionalmente simples.

> 💡 **Guia Detalhado de Implementação**: O passo a passo técnico completo, comandos artisan, regras e testes estão organizados em [backend/docs/ROADMAP.md](file:///c:/Users/luizm/Desktop/adote+/backend/docs/ROADMAP.md).

---

## 1. Visão Geral

**Adota+** é um catálogo público de animais resgatados pelo centro de zoonoses da cidade. Administradores e agentes públicos gerenciam o fluxo:
- **Administrador (`role: admin`)**: Responsável pelo cadastro inicial dos pets resgatados (foto, porte, vacinas) e exclusão.
- **Agente (`role: agente`)**: Realiza o atendimento, edita informações e atualiza o status para "Adotado" após a entrevista.
- **Visitante (Público)**: Navega e busca animais disponíveis no catálogo sem autenticação.

- **Backend**: PHP + Laravel 11 — API REST pura (sem views Blade).
- **Frontend**: Aplicação separada (Vue ou React, SPA) que consome a API. O mesmo app exibe ações protegidas quando o usuário está autenticado.
- **Banco de dados**: SQLite (zero config, ideal para ambiente de desenvolvimento/entrega).
- **Autenticação**: Laravel Sanctum, via Bearer Token, consumido pelo frontend.

---

## 2. Stack Técnica

| Camada           | Tecnologia                                                         |
| ---------------- | ------------------------------------------------------------------ |
| Backend          | PHP 8.2+, Laravel 11                                               |
| Banco            | SQLite                                                             |
| Auth & RBAC      | Laravel Sanctum (personal access tokens) + `role` (`admin`/`agente`) |
| Upload de imagem | Laravel Storage (disk `public` + `storage:link`)                   |
| Frontend         | Vue 3 ou React (SPA), consumindo a API via fetch/axios             |
| CORS             | Configurado em `config/cors.php` para liberar a origem do frontend |

---

## 3. Modelo de Dados

### `users` (equipe interna)

Tabela do Laravel com adição do campo `role`:
- `id`: bigint (PK)
- `name`: string
- `email`: string (unique)
- `role`: string/enum (`admin`, `agente`) — default: `agente`
- `password`: string (hash)
- `created_at` / `updated_at`: timestamps

### `pets`

| Campo                   | Tipo                                         | Observação                                |
| ----------------------- | -------------------------------------------- | ----------------------------------------- |
| id                      | bigint (PK)                                  |                                           |
| nome                    | string                                       |                                           |
| especie                 | enum: `cao`, `gato`, `outro`                 |                                           |
| porte                   | enum: `pequeno`, `medio`, `grande`           |                                           |
| idade_aproximada        | string ou integer                            | ex: "2 anos" ou meses                     |
| sexo                    | enum: `macho`, `femea`                       |                                           |
| foto_path               | string, nullable                             | caminho relativo no disk `public`         |
| descricao               | text, nullable                               |                                           |
| vacinas                 | json, nullable                               | array de objetos `{nome, data_aplicacao}` |
| status                  | enum: `disponivel`, `em_processo`, `adotado` | default `disponivel`                      |
| data_resgate            | date, nullable                               |                                           |
| adotante_nome           | string, nullable                             | preenchido ao marcar como adotado         |
| data_adocao             | date, nullable                               | preenchido ao marcar como adotado         |
| created_at / updated_at | timestamps                                   |                                           |

> Decisão de escopo: vacinas ficam como JSON na própria tabela `pets` em vez de uma tabela relacional separada, para manter o CRUD simples.

---

## 4. Rotas da API (`routes/api.php`)

### Públicas (sem autenticação)

```
GET  /api/pets              -> lista pets com paginação e filtros (?especie=, ?porte=, ?status=, ?busca=)
GET  /api/pets/{id}         -> detalhe de um pet
```

### Autenticação

```
POST /api/login             -> valida credenciais, retorna token Sanctum e dados do usuário (com role)
POST /api/logout            -> (auth:sanctum) invalida o token atual
GET  /api/user              -> (auth:sanctum) retorna dados do usuário autenticado
```

### Protegidas (auth:sanctum) — Ações de Agentes e Admins

```
PUT    /api/pets/{id}             -> atualiza dados gerais do pet (Agente e Admin)
PATCH  /api/pets/{id}/status       -> atualiza status (ex: marcar "adotado", exige adotante_nome)
```

### Protegidas Exclusivas de Administrador (`auth:sanctum` + `role.admin`)

```
POST   /api/pets                 -> cadastra novo pet (multipart/form-data, inclui foto) — EXCLUSIVO ADMIN
DELETE /api/pets/{id}             -> remove pet do catálogo — EXCLUSIVO ADMIN
```

---

## 5. Estrutura de Pastas (Backend)

```
backend/
├── docs/                        # Roteiro passo a passo do backend
│   ├── ROADMAP.md               # Visão geral e checklist
│   ├── 01-database-models.md    # Passo 1: Migrations, Models e Seeders
│   ├── 02-auth-roles-sanctum.md # Passo 2: Sanctum e autorização admin/agente
│   ├── 03-pets-api-crud.md      # Passo 3: CRUD, Form Requests e Resources
│   ├── 04-upload-storage-cors.md# Passo 4: Storage público e CORS
│   └── 05-testes-e-integracao.md# Passo 5: Testes de API e cURL
├── app/
│   ├── Http/
│   │   ├── Controllers/Api/
│   │   │   ├── AuthController.php
│   │   │   └── PetController.php
│   │   ├── Middleware/
│   │   │   └── CheckAdminRole.php
│   │   ├── Requests/
│   │   │   ├── StorePetRequest.php
│   │   │   ├── UpdatePetRequest.php
│   │   │   └── UpdatePetStatusRequest.php
│   │   └── Resources/
│   │       └── PetResource.php
│   └── Models/
│       ├── Pet.php
│       └── User.php
├── database/
│   ├── migrations/
│   └── seeders/
│       ├── PetSeeder.php
│       └── UserSeeder.php
└── routes/
    └── api.php
```

---

## 6. Regras de Negócio

1. **Catálogo Público**: `GET /api/pets` e `GET /api/pets/{id}` nunca exigem autenticação e não expõem dados do agente responsável.
2. **Cadastro e Exclusão Exclusivos de Admin**: Apenas usuários autenticados com `role = 'admin'` podem executar `POST /api/pets` e `DELETE /api/pets/{id}`. Agentes recebem `403 Forbidden`.
3. **Edição e Mudança de Status**: Tanto `admin` quanto `agente` podem atualizar dados gerais e alterar status do animal.
4. **Validação de Adoção**: Ao marcar um pet como `adotado` (via `PATCH /api/pets/{id}/status`), é obrigatório informar `adotante_nome`; `data_adocao` pode ser preenchida automaticamente com a data atual se omitida.
5. **Upload de Foto**: Aceitar apenas formatos de imagem (`jpg`, `jpeg`, `png`), com validação de tamanho máximo (2MB) na Form Request.
6. **Estado Inicial**: Todo pet cadastrado nasce com `status = disponivel`.

---

## 7. Fora de Escopo (por enquanto)

- Tabela relacional separada de vacinas
- Notificações por e-mail
- Painel administrativo com layout separado do catálogo público
