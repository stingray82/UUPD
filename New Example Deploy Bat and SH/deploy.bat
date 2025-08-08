@echo off
setlocal enabledelayedexpansion

REM =====================================================
REM PATH SETUP
REM =====================================================
SET "SCRIPT_DIR=%~dp0"
IF "%SCRIPT_DIR:~-1%"=="\" SET "SCRIPT_DIR=%SCRIPT_DIR:~0,-1%"

REM =====================================================
REM LOAD CONFIG FROM deploy.cfg (same folder as this BAT)
REM Lines starting with # or ; are treated as comments.
REM Blank lines are ignored.
REM =====================================================
SET "CONFIG_FILE=%SCRIPT_DIR%\deploy.cfg"
IF NOT EXIST "%CONFIG_FILE%" (
    echo [ERROR] Config file not found: %CONFIG_FILE%
    pause & exit /b 1
)

FOR /F "usebackq tokens=1* delims== eol=#" %%A IN ("%CONFIG_FILE%") DO (
    SET "K=%%A"
    IF NOT "!K!"=="" IF NOT "!K:~0,1!"==";" (
        SET "%%A=%%B"
    )
)

REM =====================================================
REM CONSTANTS / SHARED TOOLS
REM (adjust these once if your environment changes)
REM =====================================================
SET "HEADER_SCRIPT=C:\Ignore By Avast\0. PATHED Items\Plugins\deployscripts\myplugin_headers.php"
SET "TOKEN_FILE=C:\Ignore By Avast\0. PATHED Items\Plugins\deployscripts\github_token.txt"
SET "GENERATOR_SCRIPT=C:\Ignore By Avast\0. PATHED Items\Plugins\deployscripts\generate_index.php"
IF EXIST "%TOKEN_FILE%" SET /P GITHUB_TOKEN=<"%TOKEN_FILE%"

REM =====================================================
REM DEFAULTS / VALIDATION
REM =====================================================
IF NOT DEFINED PLUGIN_SLUG (
    echo [ERROR] PLUGIN_SLUG is not defined in deploy.cfg
    pause & exit /b 1
)
IF NOT DEFINED GITHUB_REPO (
    echo [ERROR] GITHUB_REPO is not defined in deploy.cfg
    pause & exit /b 1
)

IF NOT DEFINED ZIP_NAME SET "ZIP_NAME=%PLUGIN_SLUG%.zip"
IF NOT DEFINED CHANGELOG_FILE SET "CHANGELOG_FILE=changelog.txt"
IF NOT DEFINED STATIC_FILE SET "STATIC_FILE=static.txt"
IF NOT DEFINED DEPLOY_TARGET SET "DEPLOY_TARGET=github"
IF NOT DEFINED PLUGIN_NAME SET "PLUGIN_NAME=%PLUGIN_SLUG%"
IF NOT DEFINED PLUGIN_TAGS SET "PLUGIN_TAGS="

REM =====================================================
REM DERIVED PATHS
REM =====================================================
SET "PLUGIN_DIR=%SCRIPT_DIR%\%PLUGIN_SLUG%"
IF "%PLUGIN_DIR:~-1%"=="\" SET "PLUGIN_DIR=%PLUGIN_DIR:~0,-1%"
SET "PLUGIN_FILE=%PLUGIN_DIR%\%PLUGIN_SLUG%.php"
SET "README=%PLUGIN_DIR%\readme.txt"
SET "TEMP_README=%PLUGIN_DIR%\readme_temp.txt"
SET "REPO_ROOT=%SCRIPT_DIR%"
SET "STATIC_SUBFOLDER=%REPO_ROOT:\=\\%\uupd"

REM =====================================================
REM VERIFY REQUIRED FILES
REM =====================================================
IF NOT EXIST "%PLUGIN_FILE%" (
    echo [ERROR] Plugin file not found: %PLUGIN_FILE%
    pause & exit /b 1
)
IF NOT EXIST "%CHANGELOG_FILE%" (
    echo [ERROR] Changelog file not found: %CHANGELOG_FILE%
    pause & exit /b 1
)
IF NOT EXIST "%STATIC_FILE%" (
    echo [ERROR] Static readme file not found: %STATIC_FILE%
    pause & exit /b 1
)

REM =====================================================
REM RUN HEADER SCRIPT (updates plugin headers if needed)
REM =====================================================
php "%HEADER_SCRIPT%" "%PLUGIN_FILE%"

REM Extract metadata from plugin headers
for /f "tokens=2* delims=:" %%A in ('findstr /C:"Requires at least:" "%PLUGIN_FILE%"') do for /f "tokens=* delims= " %%X in ("%%A") do set "requires_at_least=%%X"
for /f "tokens=2* delims=:" %%A in ('findstr /C:"Tested up to:" "%PLUGIN_FILE%"') do for /f "tokens=* delims= " %%X in ("%%A") do set "tested_up_to=%%X"
for /f "tokens=2* delims=:" %%A in ('findstr /C:"Version:" "%PLUGIN_FILE%"') do for /f "tokens=* delims= " %%X in ("%%A") do set "version=%%X"
for /f "tokens=2* delims=:" %%A in ('findstr /C:"Requires PHP:" "%PLUGIN_FILE%"') do for /f "tokens=* delims= " %%X in ("%%A") do set "requires_php=%%X"

REM =====================================================
REM GENERATE STATIC index.json FOR GITHUB DELIVERY
REM =====================================================
echo [INFO] Generating index.json for GitHub delivery...

FOR /F "tokens=1,2 delims=/" %%A IN ("%GITHUB_REPO%") DO (
    SET "GITHUB_USER=%%A"
    SET "REPO_NAME=%%B"
)

SET "CDN_PATH=https://raw.githubusercontent.com/%GITHUB_USER%/%REPO_NAME%/main/uupd"

IF NOT EXIST "%STATIC_SUBFOLDER%" (
    mkdir "%STATIC_SUBFOLDER%"
)

php "%GENERATOR_SCRIPT%" ^
    "%PLUGIN_FILE%" ^
    "%CHANGELOG_FILE%" ^
    "%STATIC_SUBFOLDER%" ^
    "%GITHUB_USER%" ^
    "%CDN_PATH%" ^
    "%REPO_NAME%" ^
    "%REPO_NAME%" ^
    "%STATIC_FILE%" ^
    "%ZIP_NAME%"

IF EXIST "%STATIC_SUBFOLDER%\index.json" (
    echo [OK] index.json generated: %STATIC_SUBFOLDER%\index.json
) ELSE (
    echo [ERROR] Failed to generate index.json
)

REM =====================================================
REM CREATE README.TXT
REM =====================================================
(
    echo === %PLUGIN_NAME% ===
    echo Contributors: reallyusefulplugins
    echo Donate link: https://reallyusefulplugins.com/donate
    echo Tags: %PLUGIN_TAGS%
    echo Requires at least: %requires_at_least%
    echo Tested up to: %tested_up_to%
    echo Stable tag: %version%
    echo Requires PHP: %requires_php%
    echo License: GPL-2.0-or-later
    echo License URI: https://www.gnu.org/licenses/gpl-2.0.html
    echo.
) > "%TEMP_README%"

type "%STATIC_FILE%" >> "%TEMP_README%"
echo. >> "%TEMP_README%"
echo == Changelog == >> "%TEMP_README%"
type "%CHANGELOG_FILE%" >> "%TEMP_README%"

IF EXIST "%README%" copy "%README%" "%README%.bak" >nul
move /Y "%TEMP_README%" "%README%"

REM =====================================================
REM GIT COMMIT AND PUSH CHANGES
REM =====================================================
pushd "%PLUGIN_DIR%"
git add -A

git diff --cached --quiet
IF %ERRORLEVEL% EQU 1 (
    git commit -m "Version %version% Release"
    git push origin main
    echo [OK] Git commit and push complete.
) ELSE (
    echo [INFO] No changes to commit.
)
popd

REM =====================================================
REM ZIP PLUGIN FOLDER
REM =====================================================
SET "SEVENZIP=C:\Program Files\7-Zip\7z.exe"
IF NOT EXIST "%SEVENZIP%" (
    echo [ERROR] 7-Zip not found at %SEVENZIP%
    pause & exit /b 1
)

for %%a in ("%PLUGIN_DIR%") do (
  set "PARENT_DIR=%%~dpa"
  set "FOLDER_NAME=%%~nxa"
)
SET "ZIP_FILE=%PARENT_DIR%%ZIP_NAME%"

pushd "%PARENT_DIR%"
"%SEVENZIP%" a -tzip "%ZIP_FILE%" "%FOLDER_NAME%"
popd
echo [OK] Zipped to: %ZIP_FILE%

REM =====================================================
REM DEPLOY LOGIC
REM =====================================================
IF /I "%DEPLOY_TARGET%"=="private" (
    echo [INFO] Deploying to private server...
    copy "%ZIP_FILE%" "%DEST_DIR%"
    echo [OK] Copied to %DEST_DIR%
) ELSE IF /I "%DEPLOY_TARGET%"=="github" (
    echo [INFO] Deploying to GitHub...

    setlocal enabledelayedexpansion
    set "RELEASE_TAG=v%version%"
    set "RELEASE_NAME=%version%"
    set "BODY_FILE=%TEMP%\changelog_body.json"
    set "CHANGELOG_BODY="

    echo [INFO] Creating release body file...

    for /f "usebackq delims=" %%l in ("%CHANGELOG_FILE%") do (
        set "line=%%l"
        set "line=!line:"=\\\"!"
        set "CHANGELOG_BODY=!CHANGELOG_BODY!!line!\n"
    )
    set "CHANGELOG_BODY=!CHANGELOG_BODY:~0,-2!"

    (
        echo {
        echo   "tag_name": "!RELEASE_TAG!",
        echo   "name": "!RELEASE_NAME!",
        echo   "body": "!CHANGELOG_BODY!",
        echo   "draft": false,
        echo   "prerelease": false
        echo }
    ) > "!BODY_FILE!"

    echo -------- BEGIN JSON BODY --------
    type "!BODY_FILE!"
    echo -------- END JSON BODY ----------

    curl -s -w "%%{http_code}" -o "%TEMP%\github_release_response.json" ^
        -H "Authorization: token %GITHUB_TOKEN%" ^
        -H "Accept: application/vnd.github+json" ^
        https://api.github.com/repos/%GITHUB_REPO%/releases/tags/!RELEASE_TAG! > "%TEMP%\github_http_status.txt"

    set /p HTTP_STATUS=<"%TEMP%\github_http_status.txt"
    set "RELEASE_ID="

    if "!HTTP_STATUS!"=="200" (
        for /f "tokens=2 delims=:," %%i in ('findstr /C:"\"id\"" "%TEMP%\github_release_response.json"') do (
            if not defined RELEASE_ID set "RELEASE_ID=%%i"
        )
        set "RELEASE_ID=!RELEASE_ID: =!"
        set "RELEASE_ID=!RELEASE_ID:,=!"
        echo [INFO] Release already exists. Updating body...

        curl -s -X PATCH "https://api.github.com/repos/%GITHUB_REPO%/releases/!RELEASE_ID!" ^
            -H "Authorization: token %GITHUB_TOKEN%" ^
            -H "Accept: application/vnd.github+json" ^
            -H "Content-Type: application/json" ^
            --data-binary "@!BODY_FILE!"
    ) else (
        echo [INFO] Creating new release...

        curl -s -X POST "https://api.github.com/repos/%GITHUB_REPO%/releases" ^
            -H "Authorization: token %GITHUB_TOKEN%" ^
            -H "Accept: application/vnd.github+json" ^
            -H "Content-Type: application/json" ^
            --data-binary "@!BODY_FILE!" > "%TEMP%\github_release_response.json"

        for /f "tokens=2 delims=:," %%i in ('findstr /C:"\"id\"" "%TEMP%\github_release_response.json"') do (
            if not defined RELEASE_ID set "RELEASE_ID=%%i"
        )
        set "RELEASE_ID=!RELEASE_ID: =!"
        set "RELEASE_ID=!RELEASE_ID:,=!"
    )

    IF NOT DEFINED RELEASE_ID (
        echo [ERROR] Could not determine release ID.
        type "%TEMP%\github_release_response.json"
        exit /b 1
    )

    echo [OK] Using Release ID: !RELEASE_ID!

    curl -s -X POST "https://uploads.github.com/repos/%GITHUB_REPO%/releases/!RELEASE_ID!/assets?name=%ZIP_NAME%" ^
        -H "Authorization: token %GITHUB_TOKEN%" ^
        -H "Accept: application/vnd.github+json" ^
        -H "Content-Type: application/zip" ^
        --data-binary "@%ZIP_FILE%"

    endlocal
)

echo.
echo [OK] Deployment complete: %DEPLOY_TARGET%
pause
