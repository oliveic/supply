# 📚 Guia Passo a Passo: Enviar Projeto para o GitHub

## Pré-requisitos

1. ✅ Git instalado (já está instalado)
2. ⚠️ Conta no GitHub (se ainda não tiver, crie em: https://github.com)

---

## Passo 1: Verificar se o arquivo connection.php está sendo ignorado

O arquivo `connection.php` contém credenciais sensíveis e NÃO deve ser enviado ao GitHub.
O arquivo `.gitignore` já está configurado para ignorá-lo.

---

## Passo 2: Adicionar todos os arquivos ao Git

```powershell
git add .
```

Este comando adiciona todos os arquivos (exceto os ignorados pelo .gitignore) ao staging area.

---

## Passo 3: Fazer o primeiro commit

```powershell
git commit -m "Initial commit: Sistema de gerenciamento de supply"
```

---

## Passo 4: Criar repositório no GitHub

1. Acesse https://github.com e faça login
2. Clique no botão **"+"** no canto superior direito
3. Selecione **"New repository"**
4. Preencha:
   - **Repository name**: `supply` (ou o nome que preferir)
   - **Description**: Sistema de gerenciamento de suprimentos
   - **Visibility**: Escolha Público ou Privado
   - **NÃO marque** "Add a README file" (já temos um)
   - **NÃO marque** "Add .gitignore" (já temos um)
   - **NÃO marque** "Choose a license"
5. Clique em **"Create repository"**

---

## Passo 5: Conectar repositório local ao GitHub

Após criar o repositório, o GitHub mostrará instruções. Execute estes comandos:

```powershell
git branch -M main
git remote add origin https://github.com/SEU_USUARIO/supply.git
```

⚠️ **IMPORTANTE**: Substitua `SEU_USUARIO` pelo seu nome de usuário do GitHub!

---

## Passo 6: Enviar código para o GitHub

```powershell
git push -u origin main
```

Você será solicitado a inserir suas credenciais do GitHub:
- **Username**: seu nome de usuário do GitHub
- **Password**: use um **Personal Access Token** (não a senha normal)

### Como criar um Personal Access Token:

1. GitHub → Settings → Developer settings → Personal access tokens → Tokens (classic)
2. Generate new token (classic)
3. Dê um nome (ex: "Supply Project")
4. Marque a opção **"repo"** (acesso completo aos repositórios)
5. Clique em **"Generate token"**
6. **COPIE O TOKEN** (você não poderá vê-lo novamente!)
7. Use este token como senha ao fazer o push

---

## Verificação Final

Após o push, acesse seu repositório no GitHub e verifique se todos os arquivos aparecem (exceto `connection.php`, que deve estar oculto).

---

## Comandos Úteis para o Futuro

### Adicionar mudanças e fazer novo commit:
```powershell
git add .
git commit -m "Descrição das mudanças"
git push
```

### Ver status dos arquivos:
```powershell
git status
```

### Ver histórico de commits:
```powershell
git log
```

---

## ⚠️ Problemas Comuns

### Erro: "remote origin already exists"
```powershell
git remote remove origin
git remote add origin https://github.com/SEU_USUARIO/supply.git
```

### Erro de autenticação
- Use Personal Access Token ao invés da senha
- Verifique se o token tem permissão "repo"

### Esqueceu de ignorar arquivo sensível
Se você já fez commit do `connection.php`:
```powershell
git rm --cached connection.php
git commit -m "Remove connection.php do repositório"
git push
```

