@echo off
title Rei Motors - Servidor PHP
echo.
echo  ================================
echo   Rei Motors - Iniciando servidor
echo  ================================
echo.
echo  Servidor rodando em: http://localhost:8080
echo  Nao feche esta janela!
echo.
"C:\xampp\php\php.exe" -S localhost:8080 -t "%~dp0public_html"
pause
