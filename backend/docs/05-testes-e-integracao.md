# Passo 5: Testes e Validação da API

**Objetivo:** Garantir a estabilidade e conformidade da API Adota+ por meio de uma coleção de testes manuais prontos e testes automatizados de integração (Feature Tests) cobrindo autenticação, controle de acesso e regras de negócio com código 100% em inglês.

---

## 1. Roteiro de Testes Manuais com cURL

```bash
API_URL="http://127.0.0.1:8000/api"
```

### 1.1 Login do Administrador
```bash
curl -s -X POST "$API_URL/login" \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@adotamais.local","password":"admin123"}'
```

### 1.2 Login do Agente
```bash
curl -s -X POST "$API_URL/login" \
  -H "Content-Type: application/json" \
  -d '{"email":"agent@adotamais.local","password":"agent123"}'
```

### 1.3 Listagem Pública de Pets (sem token)
```bash
curl -s -X GET "$API_URL/pets"
```

### 1.4 Listagem com Filtros (sem token)
```bash
curl -s -X GET "$API_URL/pets?species=dog&size=medium&status=available"
```

### 1.5 Agent Tenta Cadastrar Pet (Deve falhar com 403)
```bash
curl -s -X POST "$API_URL/pets" \
  -H "Authorization: Bearer $AGENT_TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Rex",
    "species": "dog",
    "size": "large",
    "approximate_age": "3 years",
    "gender": "male"
  }'
```
*Resultado esperado:* `HTTP 403 Forbidden` ("Access denied. Action restricted to administrators.").

### 1.6 Admin Cadastra Pet com Sucesso (Retorna 201)
```bash
curl -s -X POST "$API_URL/pets" \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Thor",
    "species": "dog",
    "size": "medium",
    "approximate_age": "1 year",
    "gender": "male",
    "description": "Docile and playful",
    "vaccines": [{"name": "DHPP", "applied_at": "2026-02-01"}]
  }'
```
*Resultado esperado:* `HTTP 201 Created` com os dados do novo pet.

### 1.7 Atualização de Status para Adopted sem Nome do Adotante (Deve falhar com 422)
```bash
curl -s -X PATCH "$API_URL/pets/1/status" \
  -H "Authorization: Bearer $AGENT_TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"status": "adopted"}'
```
*Resultado esperado:* `HTTP 422 Unprocessable Entity` ("The adopter name is required when marking a pet as adopted.").

### 1.8 Atualização de Status para Adopted com Sucesso
```bash
curl -s -X PATCH "$API_URL/pets/1/status" \
  -H "Authorization: Bearer $AGENT_TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"status": "adopted", "adopter_name": "Maria Silva"}'
```
*Resultado esperado:* `HTTP 200 OK`.

---

## 2. Testes Automatizados (Feature Tests)

### 2.1 Teste do Catálogo e Permissões (`tests/Feature/PetTest.php`)
```php
public function test_pets_catalog_is_public(): void
{
    Pet::factory()->count(3)->create();

    $response = $this->getJson('/api/pets');

    $response->assertStatus(200)
             ->assertJsonCount(3, 'data');
}

public function test_agent_cannot_create_new_pet(): void
{
    $agent = User::factory()->create(['role' => 'agent']);

    $response = $this->actingAs($agent)
                     ->postJson('/api/pets', [
                         'name' => 'Bolinha',
                         'species' => 'cat',
                         'size' => 'small',
                         'approximate_age' => '5 months',
                         'gender' => 'female',
                     ]);

    $response->assertStatus(403);
}

public function test_admin_can_create_new_pet(): void
{
    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin)
                     ->postJson('/api/pets', [
                         'name' => 'Bolinha',
                         'species' => 'cat',
                         'size' => 'small',
                         'approximate_age' => '5 months',
                         'gender' => 'female',
                     ]);

    $response->assertStatus(201)
             ->assertJsonPath('data.name', 'Bolinha');
}

public function test_marking_as_adopted_requires_adopter_name(): void
{
    $agent = User::factory()->create(['role' => 'agent']);
    $pet = Pet::factory()->create(['status' => 'available']);

    $response = $this->actingAs($agent)
                     ->patchJson("/api/pets/{$pet->id}/status", [
                         'status' => 'adopted',
                     ]);

    $response->assertStatus(422)
             ->assertJsonValidationErrors(['adopter_name']);
}
```

---

## 3. Execução dos Testes

```bash
php artisan test
```
