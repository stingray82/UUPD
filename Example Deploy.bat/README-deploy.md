🔄 Plugin Deployment Script
==========================

 

This project contains a `deploy.bat` script that automates the build, packaging,
and deployment of a WordPress plugin to either:

-   📦 GitHub Releases

-   🔐 Private Server (optional)

-   🧾 Static JSON/CDN-based delivery using the [UUPD
    Updater](https://github.com/yourusername/uupd)

 

📁 Files Used
------------

-   `plugin.php` — Your main plugin file

-   `static.txt` — Readme content (description, installation, etc.)

-   `changelog.txt` — Changelog used in the readme and release notes

-   `generate_index.php` — Creates `index.json` for UUPD to read

🛠 What It Does
--------------

-   Extracts plugin metadata from headers

-   Builds a `readme.txt` combining static content and changelog

-   Commits and pushes to GitHub (if needed)

-   Generates a `.zip` of the plugin

-   Deploys release to GitHub or private folder

-   Generates `index.json` + `info.txt` for static/CDN delivery

-   Uploads banners and icons (if placed in `uupd/` folder)

 

📤 Example Output
----------------

After running the script:

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

 

✅ Requirements
--------------

-   PHP (CLI)

-   Git

-   7-Zip (for Windows ZIP creation)

-   GitHub personal access token with `repo` scope (saved to a `.txt` file)

 

💡 Tip
-----

You can use GitHub Pages or raw.githubusercontent.com to serve `index.json`
directly for UUPD:

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
https://raw.githubusercontent.com/yourusername/example-plugin/main/uupd/index.json
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

 

Use this as the `server` parameter when configuring the UUPD Updater in your
plugin.

🧪 Status
--------

>   Actively used for real plugin deployments. Works with GitHub +
>   static/CDN-based delivery via UUPD.
