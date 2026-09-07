# Passo 5: Testes e Validação da API

**Objetivo:** Garantir a estabilidade e conformidade da API Adota+ por meio de uma coleção de testes manuais prontos e testes automatizados de integração (Feature Tests) cobrindo autenticação, controle de acesso e regras de negócio.

---

## 1. Roteiro de Testes Manuais com cURL

Salve essas variáveis de ambiente para facilitar a execução dos testes no terminal:
```bash
API_URL="http://127.0.0.1:8000/api"
```

### 1.1 Login do Administrador
```bash
curl -s -X POST "$API_URL/login" \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@adotamais.local","password":"admin123"}'
```
*Guarde o token retornado como `ADMIN_TOKEN`.*

### 1.2 Login do Agente
```bash
curl -s -X POST "$API_URL/login" \
  -H "Content-Type: application/json" \
  -d '{"email":"agente@adotamais.local","password":"agente123"}'
```
*Guarde o token retornado como `AGENTE_TOKEN`.*

### 1.3 Listagem Pública de Pets (sem token)
```bash
curl -s -X GET "$API_URL/pets"
```

### 1.4 Listagem com Filtros (sem token)
```bash
curl -s -X GET "$API_URL/pets?especie=cao&porte=medio&status=disponivel"
```

### 1.5 Agente Tenta Cadastrar Pet (Deve falhar com 403)
```bash
curl -s -X POST "$API_URL/pets" \
  -H "Authorization: Bearer $AGENTE_TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "nome": "Rex",
    "especie": "cao",
    "porte": "grande",
    "idade_aproximada": "3 anos",
    "sexo": "macho"
  }'
```
*Resultado esperado:* `HTTP 403 Forbidden` ("Acesso negado. Ação restrita a administradores.").

### 1.6 Admin Cadastra Pet com Sucesso (Retorna 201)
```bash
curl -s -X POST "$API_URL/pets" \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "nome": "Thor",
    "especie": "cao",
    "porte": "medio",
    "idade_aproximada": "1 ano",
    "sexo": "macho",
    "descricao": "Dócil e brincalhão",
    "vacinas": [{"nome": "V10", "data_aplicacao": "2026-02-01"}]
  }'
```
*Resultado esperado:* `HTTP 201 Created` com os dados do novo pet.

### 1.7 Atualização de Status para Adotado sem Nome do Adotante (Deve falhar com 422)
```bash
curl -s -X PATCH "$API_URL/pets/1/status" \
  -H "Authorization: Bearer $AGENTE_TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"status": "adotado"}'
```
*Resultado esperado:* `HTTP 422 Unprocessable Entity` ("O nome do adotante é obrigatório...").

### 1.8 Atualização de Status para Adotado com Sucesso
```bash
curl -s -X PATCH "$API_URL/pets/1/status" \
  -H "Authorization: Bearer $AGENTE_TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"status": "adotado", "adotante_nome": "Maria Silva"}'
```
*Resultado esperado:* `HTTP 200 OK`.

---

## 2. Testes Automatizados (Feature Tests)

### 2.1 Teste de Autenticação (`tests/Feature/AuthTest.php`)
- **Comando:**
  ```bash
  php artisan make:test AuthTest
  ```
- **Cenários a cobrir:**
  - `test_usuario_consegue_fazer_login_com_credenciais_validas()`
  - `test_usuario_nao_consegue_fazer_login_com_senha_errada()`
  - `test_usuario_autenticado_consegue_fazer_logout()`
  - `test_usuario_nao_autenticado_recebe_401_ao_consultar_perfil()`

### 2.2 Teste do Catálogo e Permissões (`tests/Feature/PetTest.php`)
- **Comando:**
  ```bash
  php artisan make:test PetTest
  ```
- **Cenários a cobrir:**
  ```php
  public function test_catalogo_de_pets_eh_publico(): void
  {
      Pet::factory()->count(3)->create();

      $response = $this->getJson('/api/pets');

      $response->assertStatus(200)
               ->assertJsonCount(3, 'data');
  }

  public function test_agente_nao_pode_cadastrar_novo_pet(): void
  {
      $agente = User::factory()->create(['role' => 'agente']);

      $response = $this->actingAs($agente)
                       ->postJson('/api/pets', [
                           'nome' => 'Bolinha',
                           'especie' => 'gato',
                           'porte' => 'pequeno',
                           'idade_aproximada' => '5 meses',
                           'sexo' => 'femea',
                       ]);

      $response->assertStatus(403);
  }

  public function test_admin_pode_cadastrar_novo_pet(): void
  {
      $admin = User::factory()->create(['role' => 'admin']);

      $response = $this->actingAs($admin)
                       ->postJson('/api/pets', [
                           'nome' => 'Bolinha',
                           'especie' => 'gato',
                           'porte' => 'pequeno',
                           'idade_aproximada' => '5 meses',
                           'sexo' => 'femea',
                       ]);

      $response->assertStatus(201)
               ->assertJsonPath('data.nome', 'Bolinha');
  }

  public function test_marcar_como_adotado_exige_nome_do_adotante(): void
  {
      $agente = User::factory()->create(['role' => 'agente']);
      $pet = Pet::factory()->create(['status' => 'disponivel']);

      $response = $this->actingAs($agente)
                       ->patchJson("/api/pets/{$pet->id}/status", [
                           'status' => 'adotado',
                       ]);

      $response->assertStatus(422)
               ->assertJsonValidationErrors(['adotante_nome']);
  }
  ```

---

## 3. Execução dos Testes

Para executar toda a suíte de testes com banco em memória:
```bash
php artisan test
```

Verifique se todos os testes passam (100% verde).
