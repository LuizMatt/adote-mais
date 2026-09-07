# Passo 4: Upload de Mídia, Storage e CORS

**Objetivo:** Configurar o armazenamento de arquivos do Laravel para servir fotos públicas dos pets e habilitar o CORS para integração segura com a SPA frontend (Vue/React).

---

## 1. Configuração do Storage de Arquivos

### 1.1 Link Simbólico do Storage Público
O Laravel armazena arquivos em `storage/app/public`, que precisa ser exposto publicamente para `public/storage`:

```bash
php artisan storage:link
```

Verifique se a pasta `public/storage` foi criada como atalho simbólico apontando para `storage/app/public`.

### 1.2 Configuração do Disco Padrão
Em `.env`, certifique-se de que o disco público está definido:
```dotenv
FILESYSTEM_DISK=public
```

---

## 2. Lógica de Upload e Remoção de Imagens

### 2.1 Salvando a Foto no `PetController@store`
Ao receber o formulário `multipart/form-data`:
```php
if ($request->hasFile('foto')) {
    // Salva em storage/app/public/pets/<hash>.jpg
    $path = $request->file('foto')->store('pets', 'public');
    $petData['foto_path'] = $path;
}
```

### 2.2 Substituição de Foto no `PetController@update`
Se uma nova foto for enviada durante a atualização:
```php
if ($request->hasFile('foto')) {
    // Remove foto anterior se existir
    if ($pet->foto_path && Storage::disk('public')->exists($pet->foto_path)) {
        Storage::disk('public')->delete($pet->foto_path);
    }

    $petData['foto_path'] = $request->file('foto')->store('pets', 'public');
}
```

### 2.3 Exclusão de Foto no `PetController@destroy`
Ao excluir um animal do banco, limpe o arquivo físico:
```php
if ($pet->foto_path && Storage::disk('public')->exists($pet->foto_path)) {
    Storage::disk('public')->delete($pet->foto_path);
}
$pet->delete();
```

---

## 3. Configuração do CORS (`config/cors.php`)

Para permitir que a SPA frontend (consumindo via Vite em `http://localhost:5173` ou similar) envie requisições para a API e inclua o header `Authorization`:

Se o arquivo `config/cors.php` ainda não existir, publique as configurações do Laravel ou crie-o:
```php
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:5173',
        'http://localhost:3000',
        'http://127.0.0.1:5173',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,
];
```

No Laravel 11, o CORS também pode ser configurado fluentemente no `bootstrap/app.php`:
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->validateCsrfTokens(except: [
        'api/*',
    ]);
})
```

---

## 4. Critérios de Validação do Passo 4

1. **Upload de Foto:** Enviar `POST /api/pets` como `multipart/form-data` contendo um arquivo de imagem no campo `foto`.
2. **Acesso Público à Imagem:** Copiar a `foto_url` retornada no JSON e abrir no navegador. A imagem deve ser renderizada sem erros 404.
3. **Limpeza Automática:** Atualizar a foto ou deletar o pet e verificar se o arquivo correspondente em `storage/app/public/pets` foi removido do disco.
4. **Header CORS:** Realizar uma requisição de pré-vôo (`OPTIONS /api/pets`) com header `Origin: http://localhost:5173` e confirmar se a resposta contém `Access-Control-Allow-Origin`.
