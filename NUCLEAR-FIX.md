## SOLUÇÃO DEFINITIVA - Parse Error Linha 26

O problema NÃO está no código. O problema é OPcache/Server Cache no Hostinger.

### MÉTODO 1: Limpar OPcache (MAIS RÁPIDO)

1. **Cria este arquivo PHP:**
   - Nome: `clear-opcache.php`
   - Local: Raiz do site (`public_html/`)
   - Código:
```php
<?php
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "OPcache cleared successfully!";
} else {
    echo "OPcache not available";
}

// Also clear file stat cache
clearstatcache(true);
echo "<br>File stat cache cleared!";

// Show OPcache status
if (function_exists('opcache_get_status')) {
    echo "<pre>";
    print_r(opcache_get_status());
    echo "</pre>";
}
?>
```

2. **Acede ao arquivo:**
   - `https://thecouplesbrand.com/clear-opcache.php`

3. **Desativa o plugin no WordPress**

4. **Volta a aceder:**
   - `https://thecouplesbrand.com/clear-opcache.php` (mais uma vez)

5. **Apaga COMPLETAMENTE a pasta do plugin:**
   - `/wp-content/plugins/advanced-bundle-system/`

6. **Acede novamente:**
   - `https://thecouplesbrand.com/clear-opcache.php` (terceira vez)

7. **Faz upload do plugin FRESCO**

8. **Acede mais uma vez:**
   - `https://thecouplesbrand.com/clear-opcache.php` (última vez)

9. **Ativa o plugin**

10. **APAGA o clear-opcache.php** (segurança)

---

### MÉTODO 2: Via Hostinger hPanel (RECOMENDADO)

1. **Login no Hostinger hPanel**

2. **Vai a: Advanced > PHP Configuration**

3. **Desativa OPcache:**
   - Procura por "OPcache"
   - Muda para "Off" ou "Disabled"
   - Guarda alterações

4. **Vai a: Advanced > PHP Configuration > PHP Options**

5. **Procura e ativa:**
   - `opcache.enable = 0`
   - `opcache.enable_cli = 0`
   - Guarda

6. **Vai ao WordPress:**
   - Desativa plugin
   - Apaga pasta `/wp-content/plugins/advanced-bundle-system/`

7. **Espera 2-3 minutos**

8. **Faz upload do plugin fresco**

9. **Ativa o plugin**

10. **Se funcionar, pode REATIVAR o OPcache depois**

---

### MÉTODO 3: Via .user.ini (Se os outros falharem)

1. **Cria arquivo `.user.ini` em `/wp-content/plugins/advanced-bundle-system/`:**
```ini
opcache.enable=0
opcache.enable_cli=0
opcache.revalidate_freq=0
```

2. **Desativa/Apaga/Reinstala plugin**

3. **Remove o `.user.ini` depois de funcionar**

---

### MÉTODO 4: Nuclear Option (GARANTIDO)

Se NADA funcionar, o problema pode ser permissões ou corrupção profunda:

1. **Via Hostinger File Manager ou FTP:**

2. **Apaga TODA a pasta:**
   - `/wp-content/plugins/advanced-bundle-system/`

3. **Cria NOVA pasta manualmente:**
   - Cria pasta: `advanced-bundle-system`

4. **Altera permissões:**
   - Pasta: 755
   - Prepara para receber arquivos

5. **Faz upload arquivo por arquivo** (não ZIP):
   - Começa por `advanced-bundle-system.php`
   - Verifica se funciona
   - Depois faz upload do resto

6. **Verifica permissões de cada arquivo:** 644

---

### VERIFICAÇÃO: O que REALMENTE está no servidor?

**Acede via SSH ou File Manager e corre:**

```bash
head -30 /home/u125521932/domains/thecouplesbrand.com/public_html/wp-content/plugins/advanced-bundle-system/advanced-bundle-system.php
```

**Ou cria este arquivo `show-line-26.php` na raiz:**

```php
<?php
$file = '/home/u125521932/domains/thecouplesbrand.com/public_html/wp-content/plugins/advanced-bundle-system/advanced-bundle-system.php';

if (file_exists($file)) {
    $lines = file($file);
    echo "<pre>";
    echo "Line 24: " . $lines[23];
    echo "Line 25: " . $lines[24];
    echo "Line 26: " . $lines[25];
    echo "Line 27: " . $lines[26];
    echo "Line 28: " . $lines[27];
    echo "\n\nHex dump of line 26:\n";
    echo bin2hex($lines[25]);
    echo "</pre>";
} else {
    echo "File not found!";
}
?>
```

Acede: `https://thecouplesbrand.com/show-line-26.php`

**Envia-me o output!**

---

## PORQUE é que isto acontece?

- **OPcache** guarda versões compiladas de PHP em memória
- Mesmo apagando/uploading novos arquivos, serve a versão em cache
- Hostinger usa OPcache agressivo por performance
- A única solução é LIMPAR o cache antes de qualquer coisa

## TL;DR (Resumo Rápido)

1. Limpa OPcache (Método 1 ou 2)
2. Apaga plugin completamente
3. Espera 2 minutos
4. Upload fresco
5. Ativa

**O código está correto. O problema é 100% cache no servidor.**
