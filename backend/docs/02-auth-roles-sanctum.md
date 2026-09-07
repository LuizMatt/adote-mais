# Passo 2: Autenticação, Sanctum e Papéis

**Objetivo:** Implementar o fluxo de autenticação por tokens Bearer usando o Laravel Sanctum, expondo login/logout e criando o controle de autorização para diferenciar ações de `admin` e `agente`.

---

## 1. Configuração do Sanctum

O Laravel 11 já inclui a tabela de `personal_access_tokens`. Caso ainda não esteja publicado o arquivo de rotas/API:
```bash
php artisan install:api
```
Certifique-se de que a trait `HasApiTokens` esteja presente no model `User`:
```php
namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    // ...
}
```

---

## 2. Controller de Autenticação (`AuthController`)

- **Comando artisan:**
  ```bash
  php artisan make:controller Api/AuthController
  ```

- **Implementação recomendada (`app/Http/Controllers/Api/AuthController.php`):**
  ```php
  namespace App\Http\Controllers\Api;

  use App\Http\Controllers\Controller;
  use App\Models\User;
  use Illuminate\Http\Request;
  use Illuminate\Support\Facades\Hash;
  use Illuminate\Validation\ValidationException;

  class AuthController extends Controller
  {
      public function login(Request $request)
      {
          $request->validate([
              'email' => 'required|email',
              'password' => 'required|string',
          ]);

          $user = User::where('email', $request->email)->first();

          if (! $user || ! Hash::check($request->password, $user->password)) {
              throw ValidationException::withMessages([
                  'email' => ['Credenciais fornecidas são inválidas.'],
              ]);
          }

          // Revoga tokens anteriores para manter apenas uma sessão ativa por dispositivo (opcional)
          $user->tokens()->delete();

          $token = $user->createToken('auth_token')->plainTextToken;

          return response()->json([
              'message' => 'Login realizado com sucesso',
              'access_token' => $token,
              'token_type' => 'Bearer',
              'user' => [
                  'id' => $user->id,
                  'name' => $user->name,
                  'email' => $user->email,
                  'role' => $user->role,
              ],
          ]);
      }

      public function logout(Request $request)
      {
          $request->user()->currentAccessToken()->delete();

          return response()->json([
              'message' => 'Logout efetuado com sucesso',
          ]);
      }

      public function me(Request $request)
      {
          return response()->json([
              'user' => [
                  'id' => $request->user()->id,
                  'name' => $request->user()->name,
                  'email' => $request->user()->email,
                  'role' => $request->user()->role,
              ],
          ]);
      }
  }
  ```

---

## 3. Controle de Acesso e Permissão (`AdminRoleMiddleware`)

Para proteger rotas que exigem perfil exclusivo de administrador:

- **Comando artisan:**
  ```bash
  php artisan make:middleware CheckAdminRole
  ```

- **Implementação (`app/Http/Middleware/CheckAdminRole.php`):**
  ```php
  namespace App\Http\Middleware;

  use Closure;
  use Illuminate\Http\Request;
  use Symfony\Component\HttpFoundation\Response;

  class CheckAdminRole
  {
      public function handle(Request $request, Closure $next): Response
      {
          if (! $request->user() || ! $request->user()->isAdmin()) {
              return response()->json([
                  'message' => 'Acesso negado. Ação restrita a administradores.',
              ], Response::HTTP_FORBIDDEN);
          }

          return $next($request);
      }
  }
  ```

- **Registrar o alias em `bootstrap/app.php`:**
  ```php
  ->withMiddleware(function (Middleware $middleware) {
      $middleware->alias([
          'role.admin' => \App\Http\Middleware\CheckAdminRole::class,
      ]);
  })
  ```

---

## 4. Definição das Rotas em `routes/api.php`

```php
use App\Http\Controllers\Api\AuthController;

// Rotas públicas de autenticação
Route::post('/login', [AuthController::class, 'login']);

// Rotas protegidas (exigem Token Bearer válido)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'me']);
});
```

---

## 5. Critérios de Validação do Passo 2

1. **Teste de Login com Sucesso:**
   ```bash
   curl -X POST http://127.0.0.1:8000/api/login \
     -H "Content-Type: application/json" \
     -d '{"email":"admin@adotamais.local","password":"admin123"}'
   ```
   *Esperado:* `200 OK` com `access_token` e objeto `user` contendo `"role": "admin"`.

2. **Teste de Credenciais Inválidas:**
   ```bash
   curl -X POST http://127.0.0.1:8000/api/login \
     -H "Content-Type: application/json" \
     -d '{"email":"admin@adotamais.local","password":"senha-errada"}'
   ```
   *Esperado:* `422 Unprocessable Entity`.

3. **Teste de Rota Protegida sem Token:**
   ```bash
   curl -X GET http://127.0.0.1:8000/api/user -H "Accept: application/json"
   ```
   *Esperado:* `401 Unauthorized`.

4. **Teste de Logout:**
   ```bash
   curl -X POST http://127.0.0.1:8000/api/logout \
     -H "Accept: application/json" \
     -H "Authorization: Bearer <SEU_TOKEN>"
   ```
   *Esperado:* `200 OK` e token invalidado.
