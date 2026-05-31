# 👑 Rei Motors — Loja de Carros Online

Sistema completo de loja de carros com painel administrativo ERP.  
**Stack:** PHP 8.2 · MySQL 8 · CSS puro · JavaScript Vanilla · Sem frameworks

---

## ✅ O que está incluído

- **Site público:** Homepage, Estoque com 9 filtros, Galeria de fotos com lightbox, Embed de vídeo YouTube, Comparador de veículos, Simulador de financiamento, Formulário "Venda seu Carro"
- **Painel Admin completo:** Dashboard com gráfico de faturamento, Veículos (CRUD + fotos + custos), Leads, Clientes, Vendas (com troca), Garantias, Financeiro (DRE + fluxo de caixa), Usuários, Configurações e Banners
- **20 veículos importados** com 268 fotos reais da Rei Motors

---

## 🚀 Como rodar do zero (computador sem nada, só VS Code)

> ⏱️ Tempo estimado: **15–20 minutos**

---

### 📦 Passo 1 — Instalar o XAMPP

O XAMPP instala PHP + MySQL juntos de uma vez. É a forma mais simples.

1. Acesse **https://www.apachefriends.org/download.html**
2. Baixe a versão **XAMPP com PHP 8.2** para Windows
3. Instale no caminho padrão `C:\xampp`
4. Na seleção de componentes, marque pelo menos: **Apache**, **MySQL**, **PHP**
5. Conclua a instalação normalmente

---

### 🔧 Passo 2 — Instalar o Git

1. Acesse **https://git-scm.com/download/win**
2. Baixe e instale com todas as opções padrão (pode clicar Next em tudo)

---

### 📁 Passo 3 — Clonar o projeto

Abra o terminal no VS Code (`Ctrl + '`) e rode:

```bash
git clone https://github.com/humbertofco/rei-motors.git
cd rei-motors
```

---

### ▶️ Passo 4 — Iniciar o MySQL

1. Abra o **XAMPP Control Panel** (na área de trabalho ou em `C:\xampp\xampp-control.exe`)
2. Clique em **Start** ao lado de **MySQL**
3. Aguarde ficar verde

> Não precisa iniciar o Apache — vamos usar o servidor embutido do PHP.

---

### 🗄️ Passo 5 — Criar e importar o banco de dados

1. Com o MySQL rodando, abra no browser: **http://localhost/phpmyadmin**
2. No menu lateral, clique em **Novo**
3. Digite o nome `rei_motors_db` e clique em **Criar**
4. Com o banco `rei_motors_db` selecionado, clique na aba **Importar** (no topo)
5. Clique em **Escolher arquivo**, selecione o arquivo `schema.sql` que está na raiz do projeto
6. Clique em **Executar** — todas as tabelas serão criadas automaticamente

---

### 🔑 Passo 6 — Configurar a senha do banco

Abra o arquivo **`includes/db.php`** no VS Code e veja essa linha:

```php
define('DB_PASS', '#Admin2026');
```

**Opção A — Definir a senha do MySQL para `#Admin2026`** (recomendado, não precisa mudar o código):
- No phpMyAdmin, clique em **Contas de usuário** no menu do topo
- Clique em **Editar privilégios** ao lado de `root` / `localhost`
- Clique em **Alterar senha**, digite `#Admin2026` e salve

**Opção B — Deixar sem senha** (XAMPP padrão):
- Mude a linha no `db.php` para: `define('DB_PASS', '');`

---

### 👤 Passo 7 — Criar o usuário administrador

No phpMyAdmin, com o banco `rei_motors_db` selecionado, clique na aba **SQL** e execute:

```sql
INSERT INTO usuarios (nome, email, senha, role, ativo, criado_em)
VALUES (
    'Admin',
    'admin@reimotors.com',
    '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uDutXO22W',
    'admin',
    1,
    NOW()
);
```

> A senha acima é o bcrypt de `admin123`. Pode mudar pelo painel depois.

---

### 🌐 Passo 8 — Iniciar o servidor e acessar o site

No terminal do VS Code, entre na pasta `public_html` e inicie o servidor PHP:

```bash
cd public_html
C:\xampp\php\php.exe -S localhost:8080
```

> Deixe esse terminal aberto enquanto usar o site. Para parar, pressione `Ctrl + C`.

Agora abra no browser:

| | URL |
|---|---|
| 🌐 Site público | http://localhost:8080/ |
| 🔒 Painel admin | http://localhost:8080/admin/ |

**Login:**
- **E-mail:** `admin@reimotors.com`
- **Senha:** `admin123`

---

## 📂 Estrutura do projeto

```
rei-motors/
├── includes/               ← configurações e funções (fora do servidor web)
│   ├── config.php          ← URL base, dados da loja, constantes
│   ├── db.php              ← conexão com o banco e credenciais
│   └── functions.php       ← funções de validação, email, formatação
│
├── public_html/            ← tudo dentro aqui é servido pelo servidor web
│   ├── index.php           ← homepage
│   ├── estoque.php         ← catálogo com filtros
│   ├── veiculo.php         ← página individual do carro
│   ├── comparar.php        ← comparador de até 3 veículos
│   ├── admin/              ← painel administrativo
│   │   ├── login.php
│   │   ├── index.php       ← dashboard
│   │   ├── veiculos/
│   │   ├── leads/
│   │   ├── clientes/
│   │   ├── vendas/
│   │   ├── garantias/
│   │   ├── financeiro/
│   │   ├── usuarios/
│   │   └── configuracoes/
│   ├── api/
│   │   └── lead.php        ← endpoint AJAX para captura de leads
│   ├── assets/
│   │   ├── css/            ← CSS modular por seção
│   │   └── js/             ← theme.js, simulador.js, main.js
│   └── uploads/            ← fotos dos carros e outros arquivos
│
└── schema.sql              ← estrutura completa do banco de dados
```

---

## ⚙️ Arquivos de configuração

### `includes/db.php` — Credenciais do banco
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'rei_motors_db');
define('DB_USER', 'root');
define('DB_PASS', '#Admin2026');   // altere para sua senha do MySQL
```

### `includes/config.php` — Dados gerais
```php
define('BASE_URL', 'http://localhost:8080/');   // mudar ao colocar no ar
define('LOJA_WHATSAPP', '1438159000');
define('LOJA_EMAIL', 'reimotorsoficial@gmail.com');
// ...
```

---

## 🛠️ Extensões recomendadas para VS Code

Instale pelo VS Code com `Ctrl+Shift+X`:

| Extensão | Utilidade |
|----------|-----------|
| **PHP Intelephense** | Autocomplete, syntax highlight, erros do PHP |
| **MySQL** (by cweijan) | Ver e editar o banco diretamente no VS Code |
| **GitLens** | Ver histórico de commits e blame |
| **Prettier** | Formatação automática de código |

---

## 🐛 Problemas comuns

| Erro | Causa | Solução |
|------|-------|---------|
| "Erro ao conectar ao banco" | MySQL não está rodando | Abra o XAMPP e inicie o MySQL |
| Página sem CSS | Servidor rodando da pasta errada | Rode o PHP de dentro de `public_html/` |
| "php não reconhecido" | PHP não está no PATH | Use `C:\xampp\php\php.exe -S localhost:8080` |
| Fotos não aparecem | Pasta uploads ausente | Verifique se `public_html/uploads/` existe |
| Login não funciona | Usuário não criado | Execute a query do Passo 7 no phpMyAdmin |
| Banco vazio | schema.sql não importado | Repita o Passo 5 |

---

## 🌍 Subir para produção (HostGator)

Quando for colocar online:

1. Mude `BASE_URL` em `config.php` para `https://seu-dominio.com/`
2. Atualize as credenciais em `db.php` com os dados do cPanel
3. Importe o `schema.sql` via phpMyAdmin do cPanel
4. Faça upload dos arquivos via FTP (FileZilla) para a pasta `public_html` do servidor
5. O conteúdo de `includes/` vai para fora do `public_html` por segurança

---

## 📞 Sobre a Rei Motors

- **Endereço:** R. Maj. Matheus, 236 - Vila dos Lavradores, Botucatu - SP
- **Horário:** Seg-Sex 08h–18h30 | Sáb 08h–13h
- **Instagram:** [@reimotors_](https://instagram.com/reimotors_)
- **Facebook:** [Rei Motors](https://www.facebook.com/profile.php?id=61565888583290)
