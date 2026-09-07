# CLAUDE.md — Adota+

Este arquivo documenta a arquitetura e as convenções do projeto **Adota+** para uso do Claude Code (ou qualquer dev) durante a implementação. Projeto acadêmico, escopo intencionalmente simples.

> 💡 **Guia Detalhado de Implementação**: O passo a passo técnico completo, comandos artisan, regras e testes estão organizados em [backend/docs/ROADMAP.md](file:///c:/Users/luizm/Desktop/adote+/backend/docs/ROADMAP.md).

---

## ⚠️ Convenção de Nomenclatura (IMPORTANTE)

**Todo o código deve ser estritamente em INGLÊS:**
- Nomes de tabelas e colunas no banco de dados (`name`, `species`, `size`, `vaccines`, etc.).
- Nomes de classes, métodos, funções, variáveis e parâmetros.
- Valores de enums e status (`dog`, `cat`, `small`, `medium`, `large`, `available`, `in_process`, `adopted`, etc.).
- Papéis de usuário (`admin`, `agent`).
- Query parameters e payloads JSON de requisição/resposta da API (`species`, `size`, `status`, `search`, etc.).
- Comentários e documentações explicativas podem ser em português, mas **identificadores de código são 100% em inglês**.

---

## 1. Visão Geral

**Adota+** é um catálogo público de animais resgatados pelo centro de zoonoses da cidade:
- **Administrador (`role: admin`)**: Responsável pelo cadastro inicial dos pets resgatados (foto, porte, vacinas) e exclusão.
- **Agente (`role: agent`)**: Realiza o atendimento, edita informações e atualiza o status para "Adopted" após a entrevista.
- **Visitante (Público)**: Navega e busca animais disponíveis no catálogo sem autenticação.

- **Backend**: PHP + Laravel 11 — API REST pura (sem views Blade).
- **Frontend**: Aplicação separada (Vue ou React, SPA) que consome a API. O mesmo app exibe ações protegidas quando o usuário está autenticado.
- **Banco de dados**: SQLite.
- **Autenticação**: Laravel Sanctum via Bearer Token.

---

## 2. Stack Técnica

| Camada           | Tecnologia                                                         |
| ---------------- | ------------------------------------------------------------------ |
| Backend          | PHP 8.2+, Laravel 11                                               |
| Banco            | SQLite                                                             |
| Auth & RBAC      | Laravel Sanctum (personal access tokens) + `role` (`admin`/`agent`) |
| Upload de imagem | Laravel Storage (disk `public` + `storage:link`)                   |
| Frontend         | Vue 3 ou React (SPA), consumindo a API via fetch/axios             |
| CORS             | Configurado em `config/cors.php` para liberar a origem do frontend |

---

## 3. Modelo de Dados

### `users` (equipe interna)

- `id`: bigint (PK)
- `name`: string
- `email`: string (unique)
- `role`: enum/string (`admin`, `agent`) — default: `agent`
- `password`: string (hash)
- `created_at` / `updated_at`: timestamps

### `pets`

| Campo                   | Tipo                                         | Observação                                |
| ----------------------- | -------------------------------------------- | ----------------------------------------- |
| id                      | bigint (PK)                                  |                                           |
| name                    | string                                       |                                           |
| species                 | enum: `dog`, `cat`, `other`                  |                                           |
| size                    | enum: `small`, `medium`, `large`             |                                           |
| approximate_age         | string                                       | ex: "2 years", "4 months"                 |
| gender                  | enum: `male`, `female`                       |                                           |
| photo_path              | string, nullable                             | relative path in `public` disk            |
| description             | text, nullable                               |                                           |
| vaccines                | json, nullable                               | array of objects `{name, applied_at}`     |
| status                  | enum: `available`, `in_process`, `adopted`   | default `available`                       |
| rescue_date             | date, nullable                               |                                           |
| adopter_name            | string, nullable                             | preenchido ao marcar como adopted         |
| adoption_date           | date, nullable                               | preenchido ao marcar como adopted         |
| created_at / updated_at | timestamps                                   |                                           |

> Decisão de escopo: vacinas ficam como JSON na própria tabela `pets` em vez de uma tabela relacional separada.

---

## 4. Rotas da API (`routes/api.php`)

### Públicas (sem autenticação)

```
GET  /api/pets              -> lista pets com paginação e filtros (?species=, ?size=, ?status=, ?search=)
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
PUT    /api/pets/{id}             -> atualiza dados gerais do pet (Agent e Admin)
PATCH  /api/pets/{id}/status       -> atualiza status (ex: marcar "adopted", exige adopter_name)
```

### Protegidas Exclusivas de Administrador (`auth:sanctum` + `role.admin`)

```
POST   /api/pets                 -> cadastra novo pet (multipart/form-data, inclui photo) — EXCLUSIVO ADMIN
DELETE /api/pets/{id}             -> remove pet do catálogo — EXCLUSIVO ADMIN
```

---

## 5. Regras de Negócio

1. **Catálogo Público**: `GET /api/pets` e `GET /api/pets/{id}` nunca exigem autenticação e não expõem dados do agente responsável.
2. **Cadastro e Exclusão Exclusivos de Admin**: Apenas usuários autenticados com `role = 'admin'` podem executar `POST /api/pets` e `DELETE /api/pets/{id}`. Agentes (`role = 'agent'`) recebem `403 Forbidden`.
3. **Edição e Mudança de Status**: Tanto `admin` quanto `agent` podem atualizar dados gerais e alterar status do animal.
4. **Validação de Adoção**: Ao marcar um pet como `adopted` (via `PATCH /api/pets/{id}/status`), é obrigatório informar `adopter_name`; `adoption_date` pode ser preenchida automaticamente com a data atual se omitida.
5. **Upload de Foto**: Aceitar apenas formatos de imagem (`jpg`, `jpeg`, `png`), com validação de tamanho máximo (2MB) na Form Request.
6. **Estado Inicial**: Todo pet cadastrado nasce com `status = available`.
