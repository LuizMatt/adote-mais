# CLAUDE.md — Adota+

Este arquivo documenta a arquitetura e as convenções do projeto **Adota+** para uso do Claude Code (ou qualquer dev) durante a implementação. Projeto acadêmico, escopo intencionalmente simples.

## 1. Visão Geral

**Adota+** é um catálogo público de animais resgatados pelo centro de zoonoses da cidade. O agente público cadastra o pet (foto, porte, vacinas) e atualiza o status para "Adotado" após a entrevista.

- **Backend**: PHP + Laravel — API REST pura (sem views Blade).
- **Frontend**: aplicação separada (Vue ou React, SPA) que consome a API. Não existe um "painel admin" com layout próprio — é o mesmo app, com telas/ações protegidas por login para quem tem papel de agente público.
- **Banco de dados**: SQLite (zero config, ideal para ambiente de desenvolvimento/entrega).
- **Autenticação**: Laravel Sanctum, via token (Bearer token), consumido pelo frontend separado.

## 2. Stack Técnica

| Camada           | Tecnologia                                                         |
| ---------------- | ------------------------------------------------------------------ |
| Backend          | PHP 8.2+, Laravel 11                                               |
| Banco            | SQLite                                                             |
| Auth             | Laravel Sanctum (personal access tokens)                           |
| Upload de imagem | Laravel Storage (disk `public` + `storage:link`)                   |
| Frontend         | Vue 3 ou React (SPA), consumindo a API via fetch/axios             |
| CORS             | Configurado em `config/cors.php` para liberar a origem do frontend |

## 3. Modelo de Dados

### `users` (agente público)

Tabela padrão do Laravel (`id`, `name`, `email`, `password`, timestamps). Um único papel — não há hierarquia de permissões neste escopo.

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

> Decisão de escopo: vacinas ficam como JSON na própria tabela `pets` em vez de uma tabela relacional separada, para manter o CRUD simples. Pode evoluir para uma tabela `pet_vacinas` (1:N) depois, se o projeto crescer.

## 4. Rotas da API (`routes/api.php`)

### Públicas (sem autenticação)

```
GET  /api/pets              -> lista pets, com filtros via query string (?especie=, ?porte=, ?status=)
GET  /api/pets/{id}         -> detalhe de um pet
```

### Autenticação

```
POST /api/login             -> valida credenciais, retorna token Sanctum
POST /api/logout            -> (auth:sanctum) invalida o token atual
```

### Protegidas (auth:sanctum) — ações do agente público

```
POST   /api/pets                 -> cadastra novo pet (multipart/form-data, inclui foto)
PUT    /api/pets/{id}             -> atualiza dados gerais do pet
PATCH  /api/pets/{id}/status       -> atualiza status (ex: marcar "adotado", exige adotante_nome)
DELETE /api/pets/{id}             -> remove pet
```

## 5. Estrutura de Pastas (Backend)

```
app/
  Http/
    Controllers/
      Api/
        AuthController.php
        PetController.php
    Requests/
      StorePetRequest.php
      UpdatePetRequest.php
      UpdatePetStatusRequest.php
    Resources/
      PetResource.php
  Models/
    Pet.php
    User.php
database/
  migrations/
    xxxx_create_pets_table.php
  seeders/
    PetSeeder.php
    UserSeeder.php
routes/
  api.php
```

## 6. Fluxo de Autenticação

1. Agente público envia `POST /api/login` com `email` e `password`.
2. Backend valida e retorna um token Sanctum (`personal access token`).
3. Frontend armazena o token (ex: `localStorage`) e envia em todas as requisições protegidas:
   ```
   Authorization: Bearer <token>
   ```
4. Rotas protegidas usam o middleware `auth:sanctum`.
5. CORS: `config/cors.php` precisa liberar a origem do frontend (ex: `http://localhost:5173`) e permitir o header `Authorization`.

## 7. Regras de Negócio

- Todo pet cadastrado nasce com `status = disponivel`.
- Apenas agente autenticado pode criar, editar, deletar ou mudar status de um pet.
- Catálogo público (`GET /api/pets` e `GET /api/pets/{id}`) nunca exige autenticação e não deve expor dados do agente responsável.
- Ao marcar um pet como `adotado` (via `PATCH /api/pets/{id}/status`), é obrigatório informar `adotante_nome`; `data_adocao` pode ser preenchida automaticamente com a data atual se não informada.
- Upload de foto: aceitar apenas imagens (`jpg`, `jpeg`, `png`), validar tamanho máximo (ex: 2MB) na Form Request.

## 8. Checklist de Implementação

1. [ ] Criar projeto Laravel (`laravel new adote-um-pet-api`) e configurar `.env` para SQLite
2. [ ] Rodar `php artisan migrate` inicial e instalar Sanctum (`php artisan install:api` ou manual)
3. [ ] Criar migration + model `Pet`
4. [ ] Criar seeder de `User` (agente de teste) e opcionalmente `PetSeeder` com dados fake
5. [ ] Implementar `AuthController` (login/logout)
6. [ ] Implementar `PetController` (index com filtros, show, store, update, updateStatus, destroy)
7. [ ] Criar Form Requests de validação (`StorePetRequest`, `UpdatePetRequest`, `UpdatePetStatusRequest`)
8. [ ] Criar `PetResource` para padronizar o JSON de resposta
9. [ ] Configurar upload de foto (`php artisan storage:link`, salvar em `storage/app/public/pets`)
10. [ ] Configurar CORS para aceitar o frontend
11. [ ] Testar todas as rotas com Postman/Insomnia (incluir collection no repo)
12. [ ] Escrever README com instruções de setup (backend e frontend)

## 9. Fora de Escopo (por enquanto)

- Múltiplos papéis/permissões de usuário
- Tabela relacional de vacinas
- Notificações por e-mail
- Painel administrativo com layout distinto do catálogo público
