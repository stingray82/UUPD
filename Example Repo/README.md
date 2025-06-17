# Example Plugin with UUPD

This is a sample plugin repository demonstrating how to use the UUPD (Universal Updater) system.

## 📦 Structure

```
example-plugin/
├── includes/
│   └── updater.php       ← Drop-in updater file
├── uupd/
│   ├── index.json        ← Generated metadata for updates
│   └── info.txt          ← URL reference for developers
├── example-plugin.php    ← Main plugin file
├── changelog.txt         ← Plugin changelog
└── README.md             ← This file
```

## 🧩 Integration

In your `example-plugin.php`, load the updater:

```php
add_action('plugins_loaded', function() {
    require_once __DIR__ . '/includes/updater.php';

    \UUPD\V1\UUPD_Updater_V1::register([
        'plugin_file' => plugin_basename(__FILE__),
        'slug'        => 'example-plugin',
        'name'        => 'Example Plugin',
        'version'     => '1.0.0',
        'server'      => 'https://raw.githubusercontent.com/YOUR_USER/example-plugin/main/uupd'
    ]);
});
```

## ⚙️ Deploy Tips

- Use `generate_index.php` to build your metadata
- Commit `uupd/` to your repo
- Push and tag your release
