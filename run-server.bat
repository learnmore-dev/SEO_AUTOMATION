@echo off
title SEO 80/20 System Web Server
cd /d "%~dp0"
echo ================================================================
echo   SkyRank Solution SEO System
echo   Server URL: http://localhost:8000
echo   Reset DB  : http://localhost:8000/reset-all.php
echo   Setup Check: http://localhost:8000/setup.php
echo ================================================================
echo.
php -c "%~dp0php.ini" -S localhost:8000
pause
