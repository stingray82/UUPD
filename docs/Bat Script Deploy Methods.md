📅 GitHub Release Integration
----------------------------

-   Automatically fetches version and changelog from the latest release

-   Uploads ZIP via API (included in batch deployment script)

-   Works with or without GitHub authentication

If you exceed GitHub API limits or use private repos:

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
add_filter( 'uupd/github_token_override', function( $token, $slug ) {
    return 'your_github_pat';
}, 10, 2 );
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

 

🔧 Command Line Build & Deployment
---------------------------------

Use the included `deploy.bat` script to:

-   Extract plugin headers

-   Build `readme.txt`

-   Generate ZIP

-   Push to GitHub

-   Generate `index.json`

 

You can customize the script with:

-   `DEPLOY_TARGET=github` or `private`

-   Plugin source and changelog locations

-   Optional GitHub token loading
