# Introducing UUPD: The Universal Updater Drop-in for WordPress Plugins & Themes

Tired of rigid update systems? UUPD is a lightweight, open-source solution that lets you serve updates directly from GitHub or any static server — without WordPress.org, license keys, or bloated APIs.

---

## 🧩 What Is UUPD?

UUPD (Universal Updater Drop-in) is a single-file, version-controlled updater you can add to any WordPress plugin or theme. It uses WordPress’s built-in update mechanisms and supports:

- ✅ GitHub-hosted plugin ZIPs
- ✅ Static metadata via JSON
- ✅ PHP-based endpoints
- ✅ Icons, banners, changelogs, caching — all optional

It’s **GPL-2.0**, fast to set up, and fully portable.

---

## 🎯 Why UUPD?

While building client and commercial plugins, I often needed something:

- **Lightweight** — no frameworks, no Composer, no heavy SDKs
- **Flexible** — support GitHub/CDN/private servers
- **Cacheable** — avoid GitHub API rate limits
- **Visual** — include plugin banners/icons like WordPress.org
- **Open** — no lock-in, MIT/GPL licensed

Existing solutions often required workarounds or were too opinionated. So I built UUPD to be what I needed — and maybe what you need too.

> You can follow the development journey in detail:
>
> - [GitHub Update Caching Gateway](https://techarticles.co.uk/github-update-caching-gateway/)
> - [Updater Plugins & Icons](https://techarticles.co.uk/updater-plugins-icons/)
> - [Mini Update Server Plugin](https://techarticles.co.uk/mini-update-server-plugin/)
> - [SureCart + Host-Served Licensing](https://techarticles.co.uk/surecart-hoster-licencing-update/)
> - [Plugin Updates: Part 2](https://techarticles.co.uk/plugin-updates-part-2/)

---

## 🧪 Live Example Plugin

Want to see it in action?

🧩 [Example Plugin on GitHub](https://github.com/stingray82/example-plugin)

Download the latest release, install it in WordPress, and when I push the next update... watch it appear automatically from GitHub using UUPD.

Update metadata is delivered via GitHub’s CDN:

```
https://raw.githubusercontent.com/stingray82/example-plugin/main/uupd/index.json
```

---

## ⚙️ How It Works

### 1. Drop in the Updater

In your plugin or theme, add:

```php
require_once __DIR__ . '/includes/updater.php';

\UUPD\V1\UUPD_Updater_V1::register([
    'plugin_file' => plugin_basename(__FILE__),
    'slug'        => 'your-plugin-slug',
    'name'        => 'Your Plugin Name',
    'version'     => '1.2.3',
    'server'      => 'https://raw.githubusercontent.com/you/your-plugin/main/uupd',
]);
```

### 2. Generate Your `index.json`

Use the included `generate_index.php` to convert your plugin headers, changelog, and icons into update metadata.

Or use the batch deploy script (`deploy.bat`) if you’re on Windows — it handles:

- Committing to GitHub
- Zipping your plugin
- Uploading the release
- Generating `readme.txt` and `index.json`
- Writing `info.txt` with direct URL

---

## 🚀 Use It Your Way

Serve updates via:

- 🟦 GitHub (raw CDN)
- 🌐 Static hosting (Netlify, Cloudflare Pages, etc.)
- 🐘 PHP-based endpoints (like [WP Update Server](https://github.com/YahnisElsts/plugin-update-checker))

UUPD doesn’t care — it just reads JSON.

---

## 📺 Video Demo (Coming Soon!)

I'll be publishing a short walkthrough showing:

- Installing the example plugin
- Pushing a release
- Auto-update appearing in the WP dashboard

You can subscribe on [TechArticles YouTube](https://techarticles.co.uk) or follow @ReallyUsefulWP on Twitter for updates.

---

## 🤝 Contribute or Fork

UUPD is 100% open source and available here:

🔗 [https://github.com/stingray82/uupd](https://github.com/stingray82/uupd)

MIT & GPL dual-licensed. Contributions, ideas, and feedback welcome.

---

## ✨ Happy Updating!

Let’s make plugin delivery fast, flexible, and finally frustration-free.