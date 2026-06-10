# CLAUDE.md — Rei Motors

Contexto completo do projeto para o Claude Code. Leia antes de qualquer alteração.

---

## O que é este projeto

Sistema completo de loja de carros online com painel administrativo (ERP).
- **Domínio:** https://worldcred.com.br (em produção)
- **Repositório:** https://github.com/HumbertoFProfiss/rei-motors
- **Loja real:** Rei Motors — R. Maj. Matheus, 236, Botucatu-SP

---

## Stack

| Camada | Tecnologia |
|--------|-----------|
| Backend | PHP 8.2 |
| Banco | MySQL 8 |
| Frontend | CSS puro + JavaScript Vanilla |
| Frameworks | **Nenhum** — zero dependências externas |

Não sugerir nem instalar frameworks (React, Laravel, Tailwind, etc.) salvo ordem explícita.

---

## Estrutura de pastas

```
rei-motors/
├── includes/               ← fora do servidor web (segurança)
│   ├── config.php          ← BASE_URL (auto-detecta ambiente), dados da loja, constantes
│   ├── db.php              ← conexão MySQL e credenciais (gitignored)
│   ├── secrets.php         ← tokens de API (gitignored)
│   └── functions.php       ← helpers: validação, email, formatação
│
└── public_html/            ← raiz servida pelo servidor web
    ├── index.php           ← homepage
    ├── estoque.php         ← catálogo com 9 filtros
    ├── veiculo.php         ← página individual do veículo
    ├── comparar.php        ← comparador de até 3 veículos
    ├── admin/              ← painel ERP completo
    │   ├── login.php
    │   ├── index.php       ← dashboard com gráfico de faturamento
    │   ├── veiculos/       ← CRUD + fotos + custos
    │   ├── leads/
    │   ├── clientes/
    │   ├── vendas/         ← inclui troca de veículos
    │   ├── garantias/
    │   ├── financeiro/     ← DRE + fluxo de caixa
    │   ├── usuarios/
    │   └── configuracoes/
    ├── api/
    │   ├── fipe.php        ← proxy FIPE + consulta de placa via wdapi2
    │   └── lead.php        ← endpoint AJAX para captura de leads
    ├── assets/
    │   ├── css/            ← CSS modular por seção (não há bundle)
    │   └── js/             ← theme.js, simulador.js, main.js
    └── uploads/            ← fotos dos veículos
```

---

## Ambiente local (desenvolvimento)

**Requisitos:** XAMPP (PHP 8.2 + MySQL 8), Git

**Iniciar:**
```bash
# MySQL pelo XAMPP Control Panel → Start MySQL
cd public_html
C:\xampp\php\php.exe -S localhost:8080
```

**URLs locais:**
- Site: http://localhost:8080/
- Admin: http://localhost:8080/admin/
- phpMyAdmin: http://localhost/phpmyadmin

**Banco local:**
- DB: `rei_motors_db`
- User: `root`
- Pass: `#Admin2026` (definido em `includes/db.php`)

**Login admin local:**
- Email: `admin@reimotors.com`
- Senha: `admin123`

---

## Deploy (produção)

**Hospedagem:** HostGator — `/home2/reidosco/worldcred.com.br/`
**Método:** GitHub Actions automático via FTP (`.github/workflows/deploy.yml`)

**Fluxo:**
1. Editar localmente no VS Code
2. `git commit` + `git push origin main`
3. GitHub Action faz upload automático dos arquivos alterados via FTP

**Arquivos que NÃO são deployados (gitignored ou excluídos):**
- `includes/db.php` — credenciais de produção (editar direto no servidor)
- `includes/secrets.php` — tokens de API (editar direto no servidor)
- `migracoes/` — scripts SQL (executar manualmente via phpMyAdmin)

**BASE_URL** em `includes/config.php` é detectada automaticamente:
- `worldcred.com.br` → `https://worldcred.com.br/`
- qualquer outro host → `http://localhost:8080/`

**Banco de produção:**
- DB: `reidosco_motors2`
- User: `reidosco_motors2_user`
- Credenciais no `includes/db.php` do servidor

---

## Banco de dados

Sempre verificar `includes/db.php` para credenciais do ambiente atual.

Migrações ficam em `migracoes/` — criar novos arquivos `.sql` numerados sequencialmente antes de alterar o schema. Executar manualmente via phpMyAdmin (HostGator não suporta `ALTER TABLE ... ADD COLUMN IF NOT EXISTS`).

---

## Regras para alterações

- **Nunca** usar frameworks JS (jQuery, React, Vue) sem ordem explícita
- **Nunca** usar frameworks PHP (Laravel, Symfony) sem ordem explícita
- CSS fica em `public_html/assets/css/` — modular por seção
- Novas páginas públicas ficam em `public_html/`
- Novas páginas do admin ficam em `public_html/admin/`
- Funções reutilizáveis vão em `includes/functions.php`
- Toda query SQL deve usar prepared statements (já é padrão no projeto)
- Migrações: sempre usar `CREATE TABLE IF NOT EXISTS`, nunca `ADD COLUMN IF NOT EXISTS`

---

## 🐛 BUGS PENDENTES

### BUG 1 — Upload de imagens quebrado (prioridade alta)

**Sintoma:** Ao fazer upload de foto de veículo no painel admin, a imagem fica com ícone de "imagem corrompida/não carregada" no admin e não aparece no site.

**Onde investigar:**
1. `public_html/admin/veiculos/fotos.php` — arquivo de upload
2. `public_html/uploads/` — verificar se o arquivo está sendo salvo com permissões corretas
3. Tabela `veiculos_fotos` — verificar se o caminho salvo está correto
4. `includes/config.php` — `BASE_URL` e `UPLOAD_PATH`

**Causas prováveis (verificar nesta ordem):**
- Caminho salvo no banco errado (barra dupla, caminho absoluto vs relativo)
- Pasta `uploads/` sem permissão de escrita (chmod 755)
- `move_uploaded_file()` falhando silenciosamente

---

### BUG 2 — FIPE: encontra placa mas falha ao buscar ano (em investigação)

**Sintoma:** Busca por placa preenche marca/modelo/ano corretamente, mas a busca FIPE automática não encontra o ano no modelo certo (especialmente modelos antigos com grafia "CONFORT" vs "COMFORT").

**Status:** Fix implementado (busca paralela em 15 candidatos), aguardando confirmação de deploy correto.

**Arquivo:** `public_html/api/fipe.php`, `public_html/admin/veiculos/novo.php`, `public_html/admin/veiculos/editar.php`
