UUPD Filters (v2.0)

⚠️ BREAKING CHANGE:
Filters are now scoped as:

uupd/{filter}/{vendor}/{slug}

Example:

add_filter('uupd/server_url/myvendor/my-plugin', function($url){
    return 'https://example.com';
});

Old slug-only filters no longer apply.
