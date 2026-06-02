# Instalação — Rei Motors

## O que você vai precisar
- Computador com Windows
- Conexão com internet (só na instalação)

---

## Passo 1 — Instalar o XAMPP

1. Acessa: https://www.apachefriends.org/pt_br/index.html
2. Clica em **Baixar XAMPP para Windows**
3. Instala normalmente (pode deixar tudo padrão, Next → Next → Finish)

---

## Passo 2 — Instalar o Git

1. Acessa: https://git-scm.com/download/win
2. Baixa e instala (Next → Next → Finish, tudo padrão)

---

## Passo 3 — Baixar o projeto

1. Abre o **Prompt de Comando** (aperta Win + R, digita `cmd`, Enter)
2. Cola esse comando e aperta Enter:

```
git clone https://github.com/HumbertoFProfiss/rei-motors.git "C:\rei-motors"
```

---

## Passo 4 — Iniciar o MySQL

1. Abre o **XAMPP Control Panel** (fica na área de trabalho ou no menu iniciar)
2. Clica em **Start** ao lado de **MySQL**
3. Aguarda ficar verde

---

## Passo 5 — Importar o banco de dados

1. Com o MySQL rodando, abre no navegador: http://localhost/phpmyadmin
2. Clica em **Novo** (no menu esquerdo)
3. No campo "Nome do banco de dados" digita: `rei_motors_db`
4. Clica em **Criar**
5. Com o banco selecionado, clica na aba **Importar**
6. Clica em **Escolher arquivo** → navega até `C:\rei-motors\` → seleciona `schema.sql` → clica em **Executar**
7. Repete o passo 5 e 6, mas agora selecionando o arquivo `rei_motors_data.sql`

---

## Passo 6 — Ajustar a senha do banco

> O XAMPP por padrão usa usuário `root` sem senha. Precisa ajustar o arquivo de configuração.

1. Abre a pasta `C:\rei-motors\includes\`
2. Abre o arquivo `db.php` com o Bloco de Notas
3. Confirma que está assim:
```
'host' => 'localhost'
'dbname' => 'rei_motors_db'
'user' => 'root'
'pass' => ''
```
4. Se a senha estiver como `#Admin2026`, apaga e deixa vazio (só as aspas `''`)
5. Salva o arquivo

---

## Passo 7 — Iniciar o servidor

1. Abre a pasta `C:\rei-motors\`
2. Dá **dois cliques** no arquivo `iniciar_servidor.bat`
3. Vai abrir uma janela preta — **não feche ela**

---

## Passo 8 — Acessar o sistema

Abre o Chrome e acessa:

| O quê | Endereço |
|---|---|
| Site principal | http://localhost:8080/ |
| Painel admin | http://localhost:8080/admin/ |
| Área do cliente | http://localhost:8080/cliente/ |

**Login do admin:**
- E-mail: `admin@reimotors.com`
- Senha: `admin123`

---

## Problemas comuns

**"Não é possível acessar esse site"**
→ A janela preta do servidor foi fechada. Abre o `iniciar_servidor.bat` de novo.

**Erro de banco de dados**
→ O MySQL do XAMPP não está rodando. Abre o XAMPP Control Panel e clica Start no MySQL.

**Porta 3306 já em uso**
→ Tem outro MySQL instalado no PC. Abre o Gerenciador de Tarefas → aba Serviços → procura `MySQL` → clica com botão direito → Parar. Depois inicia pelo XAMPP normalmente.
