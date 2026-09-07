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
if ($request->hasFile('photo')) {
    // Salva em storage/app/public/pets/<hash>.jpg
    $path = $request->file('photo')->store('pets', 'public');
    $petData['photo_path'] = $path;
}
```

### 2.2 Substituição de Foto no `PetController@update`
Se uma nova foto for enviada durante a atualização:
```php
if ($request->hasFile('photo')) {
    // Remove foto anterior se existir
    if ($pet->photo_path && Storage::disk('public')->exists($pet->photo_path)) {
        Storage::disk('public')->delete($pet->photo_path);
    }

    $petData['photo_path'] = $request->file('photo')->store('pets', 'public');
}
```

### 2.3 Exclusão de Foto no `PetController@destroy`
Ao excluir um animal do banco, limpe o arquivo físico:
```php
if ($pet->photo_path && Storage::disk('public')->exists($pet->photo_path)) {
    Storage::disk('public')->delete($pet->photo_path);
}
$pet->delete();
```

---

## 3. Configuração do CORS (`config/cors.php`)

Para permitir que a SPA frontend envie requisições para a API e inclua o header `Authorization`:

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

---

## 4. Critérios de Validação do Passo 4

1. **Upload de Foto:** Enviar `POST /api/pets` como `multipart/form-data` contendo um arquivo de imagem no campo `photo`.
2. **Acesso Público à Imagem:** Copiar a `photo_url` retornada no JSON e abrir no navegador.
3. **Limpeza Automática:** Atualizar a foto ou deletar o pet e verificar se o arquivo correspondente em `storage/app/public/pets` foi removido do disco.
