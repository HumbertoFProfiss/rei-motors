@echo off
title Rei Motors
color 0A
chcp 65001 >nul 2>&1
cls

echo.
echo  ╔══════════════════════════════════════╗
echo  ║         REI MOTORS - SISTEMA         ║
echo  ╚══════════════════════════════════════╝
echo.

:: ── Localizar PHP ──────────────────────────────────────────────────────────
set PHP_EXE=

if exist "C:\xampp\php\php.exe"         set PHP_EXE=C:\xampp\php\php.exe
if exist "C:\wamp64\bin\php\php8.2.0\php.exe" set PHP_EXE=C:\wamp64\bin\php\php8.2.0\php.exe
if exist "C:\wamp\bin\php\php8.2.0\php.exe"   set PHP_EXE=C:\wamp\bin\php\php8.2.0\php.exe

:: Tenta qualquer versão no wamp
if not defined PHP_EXE (
    for /d %%d in ("C:\wamp64\bin\php\php*") do set PHP_EXE=%%d\php.exe
    for /d %%d in ("C:\wamp\bin\php\php*")   do set PHP_EXE=%%d\php.exe
)

:: Tenta PHP no PATH do sistema
if not defined PHP_EXE (
    where php >nul 2>&1 && set PHP_EXE=php
)

if not defined PHP_EXE (
    echo  [ERRO] PHP nao encontrado!
    echo.
    echo  Instale o XAMPP: https://www.apachefriends.org/
    echo  Ou o WAMP:       https://www.wampserver.com/
    echo.
    pause
    exit /b 1
)

echo  PHP encontrado: %PHP_EXE%
echo.

:: ── Iniciar MySQL do XAMPP se estiver parado ───────────────────────────────
if exist "C:\xampp\mysql\bin\mysqld.exe" (
    tasklist /fi "imagename eq mysqld.exe" 2>nul | find /i "mysqld.exe" >nul
    if errorlevel 1 (
        echo  Iniciando MySQL...
        start "" /min "C:\xampp\mysql\bin\mysqld.exe" --defaults-file="C:\xampp\mysql\bin\my.ini" --standalone
        timeout /t 3 /nobreak >nul
    ) else (
        echo  MySQL ja esta rodando.
    )
)

:: ── Iniciar servidor PHP ───────────────────────────────────────────────────
echo  Iniciando servidor em http://localhost:8080
echo.
echo  ┌─────────────────────────────────────────┐
echo  │  Site:    http://localhost:8080          │
echo  │  Admin:   http://localhost:8080/admin/   │
echo  │  Cliente: http://localhost:8080/cliente/ │
echo  └─────────────────────────────────────────┘
echo.
echo  Nao feche esta janela enquanto usar o sistema.
echo  Para parar: pressione Ctrl+C
echo.

:: Abre o navegador após 1 segundo
start "" /b cmd /c "timeout /t 1 /nobreak >nul && start http://localhost:8080"

:: Inicia o servidor
"%PHP_EXE%" -S localhost:8080 -t "%~dp0public_html"

pause
