Plugin Deployment Scripts
=========================

This project contains two deployment scripts — a Windows `deploy.bat` and a
cross-platform `deploy.sh` — that automate the build, packaging, and deployment
of a WordPress plugin.

You can deploy to:

-   📦 **GitHub Releases**

-   🔐 **Private Server** (optional)

-   🧾 **Static JSON/CDN delivery** for the [UUPD
    Updater](https://github.com/yourusername/uupd)

Files Used
----------

-   `deploy.cfg` – Configuration file containing plugin metadata and paths (one
    per plugin)

-   `deploy.bat` – Windows batch script for running from Command Prompt or
    double-click

-   `deploy.sh` – Bash script for running from Git Bash (Windows) or Linux/macOS

-   `plugin.php` – Main plugin file (must match `PLUGIN_SLUG`)

-   `static.txt` – Static readme content (description, installation, etc.)

-   `changelog.txt` – Changelog used in `readme.txt` and release notes

-   `generate_index.php` – Creates `index.json` for UUPD to read

How It Works
------------

1.  **Loads config from** `deploy.cfg`  
    This contains plugin name, slug, GitHub repo, changelog path, etc.

2.  **Updates plugin headers**  
    Calls a PHP script to set version numbers and other metadata.

3.  **Builds** `readme.txt`  
    Combines static content and changelog into a properly formatted readme.

4.  **Commits and pushes to GitHub**  
    If there are changes, they are committed and pushed to the `main` branch.

5.  **Creates** `.zip` **file**  
    Uses 7-Zip (Windows) or `tar` as a fallback.

6.  **Deploys**

    -   If `DEPLOY_TARGET=github`, uploads a release and asset to GitHub.

    -   If `DEPLOY_TARGET=private`, copies the `.zip` to a destination folder.

7.  **Generates** `index.json`  
    For use with UUPD, enabling auto-updates from GitHub or your own CDN.

Running the Scripts
-------------------

### **Windows (Batch version)**

Run from Command Prompt or double-click:

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ powershell
deploy.bat
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

### **Git Bash / Linux / macOS (Bash version)**

On Windows, install [Git Bash](https://git-scm.com/downloads), then run:

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ bash
"C:\Program Files\Git\bin\bash.exe" deploy.sh
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

On Linux/macOS:

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ bash
bash deploy.sh
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

>   **Note:** The Bash script uses `set -uo pipefail` for safety, so missing
>   variables in `deploy.cfg` or failed commands will stop execution.

⚙ `deploy.cfg` Example
----------------------

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ ini
PLUGIN_NAME=Automation for WPSubscriptions
PLUGIN_TAGS=Automation for WPSubscriptions, Flowmattic
PLUGIN_SLUG=automation-for-wpsubscription

GITHUB_REPO=stingray82/automation-for-wpsubscription
ZIP_NAME=automation-for-wpsubscription.zip
DEPLOY_TARGET=github

CHANGELOG_FILE=C:/Users/Nathan/Git/rup-changelogs/automation-for-wpsubscription.txt
STATIC_FILE=static.txt
DEST_DIR=D:/updater.reallyusefulplugins.com/plugin-updates/custom-packages
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

**Tip:** Use forward slashes in paths (`C:/path/to/file`) to avoid escaping
issues in Git Bash.

Example Output
--------------

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
example-plugin/
├── readme.txt
├── example-plugin.php
├── ...
└── uupd/
    ├── index.json
    ├── banner-772x250.png
    ├── icon-128.png
    ├── info.txt
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Requirements
------------

-   PHP (CLI)

-   Git

-   7-Zip (for Windows ZIP creation)

-   GitHub personal access token with `repo` scope (saved to a `.txt` file)

-   Git Bash (Windows) for running `deploy.sh`

Tips
----

-   Serve `index.json` from GitHub Pages or `raw.githubusercontent.com` for
    UUPD:

    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    https://raw.githubusercontent.com/yourusername/example-plugin/main/uupd/index.json
    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

-   Keep a separate `deploy.cfg` per plugin so you can reuse the same scripts.

-   The `.sh` version works well for automating inside CI/CD pipelines.

Status
------

Actively used for real plugin deployments. Works with GitHub releases, private
server delivery, and static/CDN-based updates via UUPD.
