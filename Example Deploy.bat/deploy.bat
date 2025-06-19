@echo off
setlocal enabledelayedexpansion

REM ─────────────────────────────────────────────────────
REM CONFIGURATION
REM ─────────────────────────────────────────────────────

REM Display name of the plugin (used for reference only, not operational)
SET "PLUGIN_NAME=My Plugin Name"

REM Tags/keywords related to the plugin (can be used in readme or deployment metadata)
SET "PLUGIN_TAGS=Tag1, Tag2, ExampleTag"

REM Path to a PHP script that inserts or updates headers inside the main plugin file
SET "HEADER_SCRIPT=C:\Path\To\Your\Scripts\insert_headers.php"

REM Full path to the plugin's root directory
SET "PLUGIN_DIR=C:\Path\To\Your\Plugin\my-plugin-folder\"

REM Ensure trailing backslash is removed from PLUGIN_DIR
IF "%PLUGIN_DIR:~-1%"=="\" SET "PLUGIN_DIR=%PLUGIN_DIR:~0,-1%"

REM Path to the main plugin file (entry point with plugin headers)
SET "PLUGIN_FILE=%PLUGIN_DIR%\my-plugin.php"

REM Path to the changelog file used for the plugin version history
SET "CHANGELOG_FILE=changelog.txt"

REM Path to a static readme section that may be merged or used in generation
SET "STATIC_FILE=static.txt"

REM Paths to WordPress readme.txt (official format) and a temporary edited copy
SET "README=%PLUGIN_DIR%\readme.txt"
SET "TEMP_README=%PLUGIN_DIR%\readme_temp.txt"

REM Optional: destination directory for build artifacts (not currently used)
SET "DEST_DIR="

REM Deployment type (can be `github` or `private` for custom distribution)
SET "DEPLOY_TARGET=github"

REM ─────────────────────────────────────────────────────
REM GITHUB SETTINGS (Used if DEPLOY_TARGET=github)
REM ─────────────────────────────────────────────────────

REM Format: username/repository
SET "GITHUB_REPO=username/my-plugin-repo"

REM Path to a file containing a GitHub Personal Access Token (for publishing releases or uploads)
SET "TOKEN_FILE=C:\Path\To\Your\Scripts\github_token.txt"

REM Read token into a variable from file
SET /P GITHUB_TOKEN=<"%TOKEN_FILE%"

REM Name of the plugin ZIP file that will be downloaded from GitHub releases
SET "ZIP_NAME=my-plugin.zip"

REM ─────────────────────────────────────────────────────
REM JSON INDEX GENERATION SETTINGS (For auto-update systems)
REM ─────────────────────────────────────────────────────

REM Path to the PHP script that generates index.json or other metadata files
SET "GENERATOR_SCRIPT=C:\Path\To\Your\Scripts\generate_index.php"

REM Root of the repo (parent of PLUGIN_DIR); used to locate where static files will be saved
SET "REPO_ROOT=%PLUGIN_DIR%\.."

REM Subfolder (e.g., uupd) where static index files like index.json will be saved
REM Escapes backslashes and appends "\uupd"
SET "STATIC_SUBFOLDER=%REPO_ROOT:\=\\%\uupd"

REM ─────────────────────────────────────────────────────
REM Script Version: Change this when the deployment script is updated
REM ─────────────────────────────────────────────────────
REM Helps you track which version of the script is in use
REM Can also be output for debugging or validation
REM
REM Example usage:
REM     echo Running Deployment Script v1.1
REM ─────────────────────────────────────────────────────


REM Script Version 1.1

REM ─────────────────────────────────────────────────────
REM VERIFY REQUIRED FILES
REM ─────────────────────────────────────────────────────
IF NOT EXIST "%PLUGIN_FILE%" (
    echo ❌ Plugin file not found: %PLUGIN_FILE%
    pause & exit /b
)
IF NOT EXIST "%CHANGELOG_FILE%" (
    echo ❌ Changelog file not found: %CHANGELOG_FILE%
    pause & exit /b
)
IF NOT EXIST "%STATIC_FILE%" (
    echo ❌ Static readme file not found: %STATIC_FILE%
    pause & exit /b
)

REM ─────────────────────────────────────────────────────
REM RUN HEADER SCRIPT
REM ─────────────────────────────────────────────────────
php "%HEADER_SCRIPT%" "%PLUGIN_FILE%"

REM Extract metadata from plugin headers
for /f "tokens=2* delims=:" %%A in ('findstr /C:"Requires at least:" "%PLUGIN_FILE%"') do for /f "tokens=* delims= " %%X in ("%%A") do set "requires_at_least=%%X"
for /f "tokens=2* delims=:" %%A in ('findstr /C:"Tested up to:" "%PLUGIN_FILE%"') do for /f "tokens=* delims= " %%X in ("%%A") do set "tested_up_to=%%X"
for /f "tokens=2* delims=:" %%A in ('findstr /C:"Version:" "%PLUGIN_FILE%"') do for /f "tokens=* delims= " %%X in ("%%A") do set "version=%%X"
for /f "tokens=2* delims=:" %%A in ('findstr /C:"Requires PHP:" "%PLUGIN_FILE%"') do for /f "tokens=* delims= " %%X in ("%%A") do set "requires_php=%%X"

REM ─────────────────────────────────────────────────────
REM GENERATE STATIC index.json FILE FOR GITHUB DELIVERY
REM ─────────────────────────────────────────────────────
echo 🧾 Generating index.json for GitHub-based delivery...

REM Extract GitHub username and repo from GITHUB_REPO
FOR /F "tokens=1,2 delims=/" %%A IN ("%GITHUB_REPO%") DO (
    SET "GITHUB_USER=%%A"
    SET "REPO_NAME=%%B"
)

REM Construct raw CDN path for JSON delivery
SET "CDN_PATH=https://raw.githubusercontent.com/%GITHUB_USER%/%REPO_NAME%/main/uupd"

REM Ensure uupd directory exists
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
    echo ✅ index.json generated → %STATIC_SUBFOLDER%\index.json
) ELSE (
    echo ❌ Failed to generate index.json
)


REM ─────────────────────────────────────────────────────
REM CREATE README.TXT
REM ─────────────────────────────────────────────────────
(
    echo === %PLUIGN_NAME% ===
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

REM ─────────────────────────────────────────────────────
REM GIT COMMIT AND PUSH CHANGES
REM ─────────────────────────────────────────────────────
pushd "%PLUGIN_DIR%"
git add -A

git diff --cached --quiet
IF %ERRORLEVEL% EQU 1 (
    git commit -m "Version %version% Release"
    git push origin main
    echo ✅ Git commit and push complete.
) ELSE (
    echo ⚠️ No changes to commit.
)
popd

REM ─────────────────────────────────────────────────────
REM ZIP PLUGIN FOLDER
REM ─────────────────────────────────────────────────────
SET "SEVENZIP=C:\Program Files\7-Zip\7z.exe"
for %%a in ("%PLUGIN_DIR%") do (
  set "PARENT_DIR=%%~dpa"
  set "FOLDER_NAME=%%~nxa"
)
SET "ZIP_FILE=%PARENT_DIR%%ZIP_NAME%"

pushd "%PARENT_DIR%"
"%SEVENZIP%" a -tzip "%ZIP_FILE%" "%FOLDER_NAME%"
popd
echo ✅ Zipped to: %ZIP_FILE%

REM ─────────────────────────────────────────────────────
REM DEPLOY LOGIC
REM ─────────────────────────────────────────────────────
IF /I "%DEPLOY_TARGET%"=="private" (
    echo 🔄 Deploying to private server...
    copy "%ZIP_FILE%" "%DEST_DIR%"
    echo ✅ Copied to %DEST_DIR%
) ELSE IF /I "%DEPLOY_TARGET%"=="github" (
    echo 🚀 Deploying to GitHub...

    setlocal enabledelayedexpansion
    set "RELEASE_TAG=v%version%"
    set "RELEASE_NAME=%version%"
    set "BODY_FILE=%TEMP%\changelog_body.json"
    set "CHANGELOG_BODY="

    echo Creating body file...

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

    REM Try to get existing release by tag
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
        echo 📝 Release already exists. Updating body...

        curl -s -X PATCH "https://api.github.com/repos/%GITHUB_REPO%/releases/!RELEASE_ID!" ^
            -H "Authorization: token %GITHUB_TOKEN%" ^
            -H "Accept: application/vnd.github+json" ^
            -H "Content-Type: application/json" ^
            --data-binary "@!BODY_FILE!"
    ) else (
        echo 🆕 Creating new release...

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
        echo ❌ Could not determine release ID.
        type "%TEMP%\github_release_response.json"
        exit /b
    )

    echo ✅ Using Release ID: !RELEASE_ID!

    curl -s -X POST "https://uploads.github.com/repos/%GITHUB_REPO%/releases/!RELEASE_ID!/assets?name=%ZIP_NAME%" ^
        -H "Authorization: token %GITHUB_TOKEN%" ^
        -H "Accept: application/vnd.github+json" ^
        -H "Content-Type: application/zip" ^
        --data-binary "@%ZIP_FILE%"

    endlocal
)

echo.
echo ✅ Deployment complete → %DEPLOY_TARGET%
pause
