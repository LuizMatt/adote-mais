# Passo 1: Modelos e Banco de Dados

**Objetivo:** Configurar o schema do banco SQLite, adicionar suporte a perfis de usuário (`admin` e `agent`), criar a tabela de pets com campos e enums padronizados em inglês, além de seeders para testes imediatos.

---

## 1. Modificações de Schema

### 1.1 Tabela `users` (Adição da coluna `role`)
- **Tipo:** `string` (ou `enum: 'admin', 'agent'`)
- **Default:** `'agent'`
- **Comando artisan:**
  ```bash
  php artisan make:migration add_role_to_users_table --table=users
  ```
- **Código da Migration:**
  ```php
  public function up(): void
  {
      Schema::table('users', function (Blueprint $table) {
          $table->string('role')->default('agent')->after('email');
      });
  }

  public function down(): void
  {
      Schema::table('users', function (Blueprint $table) {
          $table->dropColumn('role');
      });
  }
  ```

### 1.2 Tabela `pets`
- **Comando artisan:**
  ```bash
  php artisan make:migration create_pets_table
  ```
- **Campos e Tipos (100% em inglês):**
  ```php
  public function up(): void
  {
      Schema::create('pets', function (Blueprint $table) {
          $table->id();
          $table->string('name');
          $table->enum('species', ['dog', 'cat', 'other']);
          $table->enum('size', ['small', 'medium', 'large']);
          $table->string('approximate_age'); // ex: "2 years", "4 months"
          $table->enum('gender', ['male', 'female']);
          $table->string('photo_path')->nullable();
          $table->text('description')->nullable();
          $table->json('vaccines')->nullable(); // array de objetos: [{"name": "Rabies", "applied_at": "2026-01-10"}]
          $table->enum('status', ['available', 'in_process', 'adopted'])->default('available');
          $table->date('rescue_date')->nullable();
          $table->string('adopter_name')->nullable();
          $table->date('adoption_date')->nullable();
          $table->timestamps();
      });
  }

  public function down(): void
  {
      Schema::dropIfExists('pets');
  }
  ```

---

## 2. Modelos (`Models`)

### 2.1 `app/Models/User.php`
- Adicionar `'role'` ao `$fillable`.
- Adicionar helper method para verificar se é admin:
  ```php
  protected $fillable = [
      'name',
      'email',
      'password',
      'role',
  ];

  public function isAdmin(): bool
  {
      return $this->role === 'admin';
  }
  ```

### 2.2 `app/Models/Pet.php`
- **Comando artisan:**
  ```bash
  php artisan make:model Pet
  ```
- **Implementação:**
  ```php
  namespace App\Models;

  use Illuminate\Database\Eloquent\Factories\HasFactory;
  use Illuminate\Database\Eloquent\Model;

  class Pet extends Model
  {
      use HasFactory;

      protected $fillable = [
          'name',
          'species',
          'size',
          'approximate_age',
          'gender',
          'photo_path',
          'description',
          'vaccines',
          'status',
          'rescue_date',
          'adopter_name',
          'adoption_date',
      ];

      protected function casts(): array
      {
          return [
              'vaccines' => 'array',
              'rescue_date' => 'date',
              'adoption_date' => 'date',
          ];
      }
  }
  ```

---

## 3. Seeders

### 3.1 `database/seeders/UserSeeder.php`
```php
namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Administrador
        User::updateOrCreate(
            ['email' => 'admin@adotamais.local'],
            [
                'name' => 'System Administrator',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
            ]
        );

        // Agente
        User::updateOrCreate(
            ['email' => 'agent@adotamais.local'],
            [
                'name' => 'Zoonosis Agent',
                'password' => Hash::make('agent123'),
                'role' => 'agent',
            ]
        );
    }
}
```

### 3.2 `database/seeders/PetSeeder.php`
Cadastra animais com dados realistas (cães e gatos, portes diferentes, vacinados e disponíveis para adoção, usando enums em inglês).

### 3.3 Chamar os Seeders em `database/seeders/DatabaseSeeder.php`
```php
public function run(): void
{
    $this->call([
        UserSeeder::class,
        PetSeeder::class,
    ]);
}
```

---

## 4. Critérios de Validação do Passo 1

Execute:
```bash
php artisan migrate:fresh --seed
```

Verifique:
- [x] As tabelas `users` e `pets` foram criadas sem erros no SQLite.
- [x] A coluna `role` existe na tabela `users` com default `'agent'`.
- [x] Os usuários `admin@adotamais.local` e `agent@adotamais.local` foram criados.
- [x] A tabela `pets` contém registros com atributos em inglês (`name`, `species`, `size`, `vaccines`, etc.).
