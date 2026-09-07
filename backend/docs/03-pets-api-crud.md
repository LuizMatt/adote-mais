# Passo 3: CRUD de Pets e Regras de Negócio

**Objetivo:** Implementar os endpoints do catálogo público de animais e as operações administrativas protegidas por autenticação e perfil (`admin` vs `agent`), com validações rígidas de formulário e padronização JSON 100% em inglês.

---

## 1. Endpoints e Contrato da API

| Método | Endpoint | Acesso | Descrição |
| :--- | :--- | :--- | :--- |
| `GET` | `/api/pets` | Público | Lista pets com paginação e filtros (`?species=`, `?size=`, `?status=`, `?search=`) |
| `GET` | `/api/pets/{id}` | Público | Detalhes de um pet específico |
| `POST` | `/api/pets` | **Admin** | Cadastra novo animal (com upload de foto) |
| `PUT` | `/api/pets/{id}` | Agent / Admin | Atualiza dados gerais do pet |
| `PATCH` | `/api/pets/{id}/status` | Agent / Admin | Atualiza status (exige `adopter_name` se status for `adopted`) |
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
          'name' => 'required|string|max:100',
          'species' => 'required|in:dog,cat,other',
          'size' => 'required|in:small,medium,large',
          'approximate_age' => 'required|string|max:50',
          'gender' => 'required|in:male,female',
          'description' => 'nullable|string|max:1000',
          'vaccines' => 'nullable|array',
          'vaccines.*.name' => 'required_with:vaccines|string|max:100',
          'vaccines.*.applied_at' => 'nullable|date',
          'rescue_date' => 'nullable|date',
          'photo' => 'nullable|image|mimes:jpeg,jpg,png|max:2048',
      ];
  }
  ```

### 2.2 `UpdatePetRequest`
- **Comando:**
  ```bash
  php artisan make:request UpdatePetRequest
  ```
- **Regras:** Campos opcionais (`sometimes`). Qualquer agente autenticado pode autorizar.

### 2.3 `UpdatePetStatusRequest`
- **Comando:**
  ```bash
  php artisan make:request UpdatePetStatusRequest
  ```
- **Regra de Negócio (Adoção):**
  ```php
  public function authorize(): bool
  {
      return true;
  }

  public function rules(): array
  {
      return [
          'status' => 'required|in:available,in_process,adopted',
          'adopter_name' => 'required_if:status,adopted|nullable|string|max:150',
          'adoption_date' => 'nullable|date',
      ];
  }

  public function messages(): array
  {
      return [
          'adopter_name.required_if' => 'The adopter name is required when marking a pet as adopted.',
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
              'name' => $this->name,
              'species' => $this->species,
              'size' => $this->size,
              'approximate_age' => $this->approximate_age,
              'gender' => $this->gender,
              'photo_url' => $this->photo_path ? url(Storage::url($this->photo_path)) : null,
              'description' => $this->description,
              'vaccines' => $this->vaccines ?? [],
              'status' => $this->status,
              'rescue_date' => $this->rescue_date?->format('Y-m-d'),
              'adopter_name' => $this->adopter_name,
              'adoption_date' => $this->adoption_date?->format('Y-m-d'),
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

      if ($request->filled('species')) {
          $query->where('species', $request->species);
      }

      if ($request->filled('size')) {
          $query->where('size', $request->size);
      }

      if ($request->filled('status')) {
          $query->where('status', $request->status);
      }

      if ($request->filled('search')) {
          $query->where('name', 'like', '%' . $request->search . '%');
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
    // Ações de Agent e Admin
    Route::put('/pets/{id}', [PetController::class, 'update']);
    Route::patch('/pets/{id}/status', [PetController::class, 'updateStatus']);

    // Ações Exclusivas de Admin
    Route::middleware('role.admin')->group(function () {
        Route::post('/pets', [PetController::class, 'store']);
        Route::delete('/pets/{id}', [PetController::class, 'destroy']);
    });
});
```
