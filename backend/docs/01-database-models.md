# Passo 1: Modelos e Banco de Dados

**Objetivo:** Configurar o schema do banco SQLite, adicionar suporte a perfis de usuário (`admin` e `agente`), criar a tabela de pets com campos e enums padronizados, além de seeders para testes imediatos.

---

## 1. Modificações de Schema

### 1.1 Tabela `users` (Adição da coluna `role`)
- **Tipo:** `string` (ou `enum: 'admin', 'agente'`)
- **Default:** `'agente'`
- **Comando artisan:**
  ```bash
  php artisan make:migration add_role_to_users_table --table=users
  ```
- **Código da Migration:**
  ```php
  public function up(): void
  {
      Schema::table('users', function (Blueprint $table) {
          $table->string('role')->default('agente')->after('email');
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
- **Campos e Tipos:**
  ```php
  public function up(): void
  {
      Schema::create('pets', function (Blueprint $table) {
          $table->id();
          $table->string('nome');
          $table->enum('especie', ['cao', 'gato', 'outro']);
          $table->enum('porte', ['pequeno', 'medio', 'grande']);
          $table->string('idade_aproximada'); // ex: "2 anos", "4 meses"
          $table->enum('sexo', ['macho', 'femea']);
          $table->string('foto_path')->nullable();
          $table->text('descricao')->nullable();
          $table->json('vacinas')->nullable(); // array de objetos: [{"nome": "V10", "data_aplicacao": "2026-01-10"}]
          $table->enum('status', ['disponivel', 'em_processo', 'adotado'])->default('disponivel');
          $table->date('data_resgate')->nullable();
          $table->string('adotante_nome')->nullable();
          $table->date('data_adocao')->nullable();
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
- **Implementação recomendada:**
  ```php
  namespace App\Models;

  use Illuminate\Database\Eloquent\Factories\HasFactory;
  use Illuminate\Database\Eloquent\Model;

  class Pet extends Model
  {
      use HasFactory;

      protected $fillable = [
          'nome',
          'especie',
          'porte',
          'idade_aproximada',
          'sexo',
          'foto_path',
          'descricao',
          'vacinas',
          'status',
          'data_resgate',
          'adotante_nome',
          'data_adocao',
      ];

      protected $casts = [
          'vacinas' => 'array',
          'data_resgate' => 'date',
          'data_adocao' => 'date',
      ];
  }
  ```

---

## 3. Seeders

### 3.1 `database/seeders/UserSeeder.php`
- **Comando artisan:**
  ```bash
  php artisan make:seeder UserSeeder
  ```
- **Implementação:**
  ```php
  namespace Database\Seeders;

  use App\Models\User;
  use Illuminate\Database\Seeder;
  use Illuminate\Support\Facades\Hash;

  class UserSeeder extends Seeder
  {
      public function run(): void
      {
          // Usuário Administrador (pode cadastrar e excluir pets)
          User::updateOrCreate(
              ['email' => 'admin@adotamais.local'],
              [
                  'name' => 'Administrador Zoonoses',
                  'password' => Hash::make('admin123'),
                  'role' => 'admin',
              ]
          );

          // Usuário Agente (atendimento, edição e atualização de status)
          User::updateOrCreate(
              ['email' => 'agente@adotamais.local'],
              [
                  'name' => 'Agente Zoonoses',
                  'password' => Hash::make('agente123'),
                  'role' => 'agente',
              ]
          );
      }
  }
  ```

### 3.2 `database/seeders/PetSeeder.php`
- **Comando artisan:**
  ```bash
  php artisan make:seeder PetSeeder
  ```
- **Implementação:** Cadastrar animais com dados realistas (cães e gatos, portes diferentes, vacinados e disponíveis para adoção).

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
- [ ] As tabelas `users` e `pets` foram criadas sem erros no SQLite.
- [ ] A coluna `role` existe na tabela `users`.
- [ ] O usuário `admin@adotamais.local` e `agente@adotamais.local` foram criados com suas respectivas senhas e perfis.
- [ ] A tabela `pets` contém os registros do seeder.
