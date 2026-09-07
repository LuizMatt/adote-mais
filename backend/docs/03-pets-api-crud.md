# Passo 3: CRUD de Pets e Regras de Negócio

**Objetivo:** Implementar os endpoints do catálogo público de animais e as operações administrativas protegidas por autenticação e perfil (`admin` vs `agente`), com validações rígidas de formulário e padronização JSON.

---

## 1. Endpoints e Contrato da API

| Método | Endpoint | Acesso | Descrição |
| :--- | :--- | :--- | :--- |
| `GET` | `/api/pets` | Público | Lista pets com paginação e filtros via query params |
| `GET` | `/api/pets/{id}` | Público | Detalhes de um pet específico |
| `POST` | `/api/pets` | **Admin** | Cadastra novo animal (com upload de foto) |
| `PUT` | `/api/pets/{id}` | Agente / Admin | Atualiza dados gerais do pet |
| `PATCH` | `/api/pets/{id}/status` | Agente / Admin | Atualiza status (exige adotante se status for `adotado`) |
| `DELETE` | `/api/pets/{id}` | **Admin** | Remove o registro do animal |

---

## 2. Form Requests (Validações)

### 2.1 `StorePetRequest`
- **Comando:**
  ```bash
  php artisan make:request StorePetRequest
  ```
- **Regras:**
  ```php
  public function authorize(): bool
  {
      return $this->user() && $this->user()->isAdmin();
  }

  public function rules(): array
  {
      return [
          'nome' => 'required|string|max:100',
          'especie' => 'required|in:cao,gato,outro',
          'porte' => 'required|in:pequeno,medio,grande',
          'idade_aproximada' => 'required|string|max:50',
          'sexo' => 'required|in:macho,femea',
          'descricao' => 'nullable|string|max:1000',
          'vacinas' => 'nullable|array',
          'vacinas.*.nome' => 'required_with:vacinas|string|max:100',
          'vacinas.*.data_aplicacao' => 'nullable|date',
          'data_resgate' => 'nullable|date',
          'foto' => 'nullable|image|mimes:jpeg,jpg,png|max:2048',
      ];
  }
  ```

### 2.2 `UpdatePetRequest`
- **Comando:**
  ```bash
  php artisan make:request UpdatePetRequest
  ```
- **Regras:** Semelhante a `StorePetRequest`, porém com campos opcionais (`sometimes`). Qualquer agente autenticado pode autorizar.

### 2.3 `UpdatePetStatusRequest`
- **Comando:**
  ```bash
  php artisan make:request UpdatePetStatusRequest
  ```
- **Regra de Negócio Crucial (Adoção):**
  ```php
  public function authorize(): bool
  {
      return true; // Protegido pelo auth:sanctum na rota
  }

  public function rules(): array
  {
      return [
          'status' => 'required|in:disponivel,em_processo,adotado',
          'adotante_nome' => 'required_if:status,adotado|nullable|string|max:150',
          'data_adocao' => 'nullable|date',
      ];
  }

  public function messages(): array
  {
      return [
          'adotante_nome.required_if' => 'O nome do adotante é obrigatório ao marcar o pet como adotado.',
      ];
  }
  ```

---

## 3. Resource JSON (`PetResource`)

Garante que o retorno JSON seja padronizado e a URL da foto seja resolvida para acesso web:

- **Comando:**
  ```bash
  php artisan make:resource PetResource
  ```

- **Implementação (`app/Http/Resources/PetResource.php`):**
  ```php
  namespace App\Http\Resources;

  use Illuminate\Http\Request;
  use Illuminate\Http\Resources\Json\JsonResource;
  use Illuminate\Support\Facades\Storage;

  class PetResource extends JsonResource
  {
      public function toArray(Request $request): array
      {
          return [
              'id' => $this->id,
              'nome' => $this->nome,
              'especie' => $this->especie,
              'porte' => $this->porte,
              'idade_aproximada' => $this->idade_aproximada,
              'sexo' => $this->sexo,
              'foto_url' => $this->foto_path ? url(Storage::url($this->foto_path)) : null,
              'descricao' => $this->descricao,
              'vacinas' => $this->vacinas ?? [],
              'status' => $this->status,
              'data_resgate' => $this->data_resgate?->format('Y-m-d'),
              'adotante_nome' => $this->adotante_nome,
              'data_adocao' => $this->data_adocao?->format('Y-m-d'),
              'created_at' => $this->created_at?->toIso8601String(),
              'updated_at' => $this->updated_at?->toIso8601String(),
          ];
      }
  }
  ```

---

## 4. Controller (`PetController`)

- **Comando:**
  ```bash
  php artisan make:controller Api/PetController
  ```

- **Filtros no método `index`:**
  ```php
  public function index(Request $request)
  {
      $query = Pet::query();

      if ($request->filled('especie')) {
          $query->where('especie', $request->especie);
      }

      if ($request->filled('porte')) {
          $query->where('porte', $request->porte);
      }

      if ($request->filled('status')) {
          $query->where('status', $request->status);
      }

      if ($request->filled('busca')) {
          $query->where('nome', 'like', '%' . $request->busca . '%');
      }

      $pets = $query->latest()->paginate($request->get('per_page', 15));

      return PetResource::collection($pets);
  }
  ```

---

## 5. Rotas em `routes/api.php`

```php
use App\Http\Controllers\Api\PetController;

// Catálogo Público
Route::get('/pets', [PetController::class, 'index']);
Route::get('/pets/{id}', [PetController::class, 'show']);

// Gestão de Pets (Protegida)
Route::middleware('auth:sanctum')->group(function () {
    // Ações do Agente e Admin
    Route::put('/pets/{id}', [PetController::class, 'update']);
    Route::patch('/pets/{id}/status', [PetController::class, 'updateStatus']);

    // Ações Exclusivas do Administrador
    Route::middleware('role.admin')->group(function () {
        Route::post('/pets', [PetController::class, 'store']);
        Route::delete('/pets/{id}', [PetController::class, 'destroy']);
    });
});
```

---

## 6. Critérios de Validação do Passo 3

1. **Listagem Pública:** `GET /api/pets` retorna lista com status `200 OK` sem exigência de token.
2. **Filtro por Espécie:** `GET /api/pets?especie=cao` retorna apenas cães.
3. **Bloqueio de Agente no Cadastro:** `POST /api/pets` com token de agente retorna `403 Forbidden`.
4. **Sucesso de Admin no Cadastro:** `POST /api/pets` com token de admin retorna `201 Created` e objeto criado.
5. **Validação de Status Adotado:** `PATCH /api/pets/{id}/status` com `status: adotado` sem `adotante_nome` retorna erro `422`.
