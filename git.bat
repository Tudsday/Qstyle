@echo off
chcp 65001 >nul

:: Get Git from registry, fallback to PATH
set GIT=git.exe
for /f "tokens=2*" %%a in ('reg query "HKLM\SOFTWARE\GitForWindows" /v InstallPath 2^>nul') do (
    if exist "%%b\cmd\git.exe" set GIT=%%b\cmd\git.exe
)
if "%GIT%"=="git.exe" where git.exe >nul 2>&1
if "%GIT%"=="git.exe" (
    echo Git not found. Please install Git for Windows.
    pause
    exit /b
)

:menu
cls
echo ========================================
echo     Qstyle Git Quick Menu
echo ========================================
echo   1. Status
echo   2. Commit and Push
echo   3. Pull
echo   4. Log
echo   5. Exit
echo ========================================
echo.
set /p n=Select [1-5]:
if "%n%"=="1" goto status
if "%n%"=="2" goto push
if "%n%"=="3" goto pull
if "%n%"=="4" goto log
if "%n%"=="5" goto end
echo Invalid input
pause
goto menu

:status
echo.
"%GIT%" status
echo.
pause
goto menu

:push
echo.
set /p msg=Commit message:
if "%msg%"=="" set msg=Update
echo.
"%GIT%" add -A
echo.
"%GIT%" commit -m "%msg%"
echo.
"%GIT%" push origin main
echo.
echo Done.
pause
goto menu

:pull
echo.
"%GIT%" pull origin main
echo.
pause
goto menu

:log
echo.
"%GIT%" log --oneline -5
echo.
pause
goto menu

:end
echo Bye.
pause
exit /b
