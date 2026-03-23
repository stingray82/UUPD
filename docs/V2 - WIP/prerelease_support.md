🧪 Prerelease Version Support
-----------------------------

You can opt-in to allow updates to prerelease versions like `1.2.3-beta`, `2.0.0-rc.1`, or `3.1.0-alpha`.

This is disabled by default to protect stable installations.

To enable prerelease updates, add this to your config:

```php
'allow_prerelease' => true,
```

You can also dynamically toggle it using constants, filters, or site options:

```php
'allow_prerelease' => defined('MY_PLUGIN_BETA_UPDATES') && MY_PLUGIN_BETA_UPDATES,
```

Only versions matching common prerelease patterns (`-alpha`, `-beta`, `-rc`) are affected.
