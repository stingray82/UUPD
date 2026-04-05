# UUPD 1.4 → 2.0 Migration Guide

## Overview

UUPD 2.0 is a **breaking change**.

That is expected and intentional.

The key change is that identity is no longer based on just `slug`. In 2.0, updater instances are identified by:

- `vendor`
- `slug`

That means old **slug-only** filters and actions from 1.4 must be migrated to the new **vendor + slug** scoped naming.

The good news is that the underlying functionality is still present in 2.0. In most cases, what changed is **how hooks are named and scoped**, not what they do. The main migration task is updating registrations and hook names.

Based on the 1.4 and 2.0 updater files, 1.4 used slug-only scoped filters like `uupd/<filter>/<slug>`, while 2.0 uses layered resolution with:

1. `uupd/<filter>`
2. `uupd/<filter>/<vendor>`
3. `uupd/<filter>/<vendor>/<slug>`

2.0 explicitly states that slug-only filter naming is no longer supported.

---

## The biggest change

### UUPD 1.4

Updater identity was effectively:

```php
slug
```

So a scoped filter looked like:

```php
add_filter('uupd/allow_prerelease/my-plugin-slug', function ($allow) {
    return true;
}, 5);
```

### UUPD 2.0

Updater identity is now:

```php
vendor + slug
```

So the equivalent filter becomes:

```php
add_filter('uupd/allow_prerelease/acme/my-plugin-slug', function ($allow, $vendor, $slug) {
    return true;
}, 5, 3);
```

You can also hook more broadly:

```php
add_filter('uupd/allow_prerelease', function ($allow, $vendor, $slug) {
    return $allow;
}, 10, 3);
```

or vendor-wide:

```php
add_filter('uupd/allow_prerelease/acme', function ($allow, $vendor, $slug) {
    if ($slug === 'my-plugin-slug') {
        return true;
    }
    return $allow;
}, 10, 3);
```

---

## Registration changes

### 1.4 example

```php
\UUPD\V1\UUPD_Updater_V1::register([
    'plugin_file'  => plugin_basename(__FILE__),
    'slug'         => 'my-plugin-slug',
    'name'         => 'My Plugin Name',
    'version'      => MY_PLUGIN_VERSION,
    'server'       => 'https://github.com/user/repo',
    'github_token' => 'ghp_YourTokenHere',
]);
```

### 2.0 example

```php
\UUPD\V2\UUPD_Updater_V2::register([
    'vendor'       => 'acme',
    'plugin_file'  => plugin_basename(__FILE__),
    'slug'         => 'my-plugin-slug',
    'name'         => 'My Plugin Name',
    'version'      => MY_PLUGIN_VERSION,
    'server'       => 'https://github.com/user/repo',
    'github_token' => 'ghp_YourTokenHere',
]);
```

### What changed

- `vendor` is now required
- cache keys are vendor-aware by default
- manual checks are vendor-aware
- hook scoping is now base, vendor-wide, and vendor+slug

---

## Filter hierarchy in 2.0

In 1.4, a per-product filter was usually just:

```php
uupd/<filter>/<slug>
```

In 2.0, filters are resolved in this order:

```php
uupd/<filter>
uupd/<filter>/<vendor>
uupd/<filter>/<vendor>/<slug>
```

That means you can now write:

- one global override for everything
- one vendor-wide override for all products from the same vendor
- one product-specific override

---

## Migration map

Below is the practical mapping from old hook names to new ones.

### `allow_prerelease`

#### 1.4

```php
add_filter('uupd/allow_prerelease/my-plugin-slug', function ($allow) {
    return true;
}, 5);
```

#### 2.0

```php
add_filter('uupd/allow_prerelease/acme/my-plugin-slug', function ($allow, $vendor, $slug) {
    return true;
}, 5, 3);
```

#### Real example based on your case

##### 1.4

```php
add_filter('uupd/allow_prerelease/mainwp-client-notes-pro-reports-extention', function ($allow) {
    return get_option('mainwp_client_notes_proreport_allow_prerelease') === 'yes';
}, 5);
```

##### 2.0

```php
add_filter('uupd/allow_prerelease/<vendor>/mainwp-client-notes-pro-reports-extention', function ($allow, $vendor, $slug) {
    return get_option('mainwp_client_notes_proreport_allow_prerelease') === 'yes';
}, 5, 3);
```

---

### `filter_config`

#### 1.4

```php
add_filter('uupd/filter_config/my-plugin-slug', function ($config) {
    $config['server'] = 'https://example.com/custom.json';
    return $config;
});
```

#### 2.0

```php
add_filter('uupd/filter_config/acme/my-plugin-slug', function ($config, $vendor, $slug) {
    $config['server'] = 'https://example.com/custom.json';
    return $config;
}, 10, 3);
```

---

### `server_url`

#### 1.4

```php
add_filter('uupd/server_url/my-plugin-slug', function ($url) {
    return 'https://example.com/custom-endpoint.json';
});
```

#### 2.0

```php
add_filter('uupd/server_url/acme/my-plugin-slug', function ($url, $vendor, $slug) {
    return 'https://example.com/custom-endpoint.json';
}, 10, 3);
```

---

### `cache_prefix`

#### 1.4

```php
add_filter('uupd/cache_prefix/my-plugin-slug', function ($prefix) {
    return 'upd_custom_';
});
```

#### 2.0

```php
add_filter('uupd/cache_prefix/acme/my-plugin-slug', function ($prefix, $vendor, $slug) {
    return 'uupd_acme_custom_';
}, 10, 3);
```

---

### `remote_url`

#### 1.4

```php
add_filter('uupd/remote_url/my-plugin-slug', function ($url) {
    return add_query_arg('channel', 'beta', $url);
});
```

#### 2.0

```php
add_filter('uupd/remote_url/acme/my-plugin-slug', function ($url, $vendor, $slug) {
    return add_query_arg('channel', 'beta', $url);
}, 10, 3);
```

---

### `metadata_result`

#### 1.4

```php
add_filter('uupd/metadata_result/my-plugin-slug', function ($meta) {
    $meta->tested = '6.8';
    return $meta;
});
```

#### 2.0

```php
add_filter('uupd/metadata_result/acme/my-plugin-slug', function ($meta, $vendor, $slug) {
    $meta->tested = '6.8';
    return $meta;
}, 10, 3);
```

---

### `github_token_override`

#### 1.4

```php
add_filter('uupd/github_token_override', function ($token, $slug) {
    $tokens = [
        'my-plugin-slug' => 'ghp_pluginToken',
    ];
    return $tokens[$slug] ?? $token;
}, 10, 2);
```

#### 2.0

```php
add_filter('uupd/github_token_override/acme/my-plugin-slug', function ($token, $vendor, $slug) {
    return 'ghp_pluginToken';
}, 10, 3);
```

Or vendor-wide:

```php
add_filter('uupd/github_token_override/acme', function ($token, $vendor, $slug) {
    $tokens = [
        'my-plugin-slug' => 'ghp_pluginToken',
    ];
    return $tokens[$slug] ?? $token;
}, 10, 3);
```

---

### `icons`

#### 1.4

```php
add_filter('uupd/icons/my-plugin-slug', function ($icons) {
    return [
        '1x' => 'https://cdn.example.com/icon-128.png',
        '2x' => 'https://cdn.example.com/icon-256.png',
    ];
});
```

#### 2.0

```php
add_filter('uupd/icons/acme/my-plugin-slug', function ($icons, $vendor, $slug) {
    return [
        '1x' => 'https://cdn.example.com/icon-128.png',
        '2x' => 'https://cdn.example.com/icon-256.png',
    ];
}, 10, 3);
```

---

### `banners`

#### 1.4

```php
add_filter('uupd/banners/my-plugin-slug', function ($banners) {
    return [
        'low'  => 'https://cdn.example.com/banner-772x250.png',
        'high' => 'https://cdn.example.com/banner-1544x500.png',
    ];
});
```

#### 2.0

```php
add_filter('uupd/banners/acme/my-plugin-slug', function ($banners, $vendor, $slug) {
    return [
        'low'  => 'https://cdn.example.com/banner-772x250.png',
        'high' => 'https://cdn.example.com/banner-1544x500.png',
    ];
}, 10, 3);
```

---

### `screenshots`

#### 1.4

```php
add_filter('uupd/screenshots/my-plugin-slug', function ($screenshots) {
    return [
        'https://cdn.example.com/screenshot-1.png',
    ];
});
```

#### 2.0

```php
add_filter('uupd/screenshots/acme/my-plugin-slug', function ($screenshots, $vendor, $slug) {
    return [
        'https://cdn.example.com/screenshot-1.png',
    ];
}, 10, 3);
```

---

### `screenshot`

#### 1.4

```php
add_filter('uupd/screenshot/my-plugin-slug', function ($screenshot) {
    return 'https://cdn.example.com/screenshot.png';
});
```

#### 2.0

```php
add_filter('uupd/screenshot/acme/my-plugin-slug', function ($screenshot, $vendor, $slug) {
    return 'https://cdn.example.com/screenshot.png';
}, 10, 3);
```

---

### `uupd_success_cache_ttl`

This one is slightly different.

In 1.4, the filter name stayed global, but the callback received the slug.

#### 1.4

```php
add_filter('uupd_success_cache_ttl', function ($ttl, $slug) {
    if ($slug === 'my-plugin-slug') {
        return HOUR_IN_SECONDS;
    }
    return $ttl;
}, 10, 2);
```

#### 2.0

```php
add_filter('uupd_success_cache_ttl/acme/my-plugin-slug', function ($ttl, $vendor, $slug) {
    return HOUR_IN_SECONDS;
}, 10, 3);
```

Or globally in 2.0:

```php
add_filter('uupd_success_cache_ttl', function ($ttl, $vendor, $slug) {
    if ($vendor === 'acme' && $slug === 'my-plugin-slug') {
        return HOUR_IN_SECONDS;
    }
    return $ttl;
}, 10, 3);
```

---

### `uupd_fetch_remote_error_ttl`

#### 1.4

```php
add_filter('uupd_fetch_remote_error_ttl', function ($ttl, $slug) {
    if ($slug === 'my-plugin-slug') {
        return 15 * MINUTE_IN_SECONDS;
    }
    return $ttl;
}, 10, 2);
```

#### 2.0

```php
add_filter('uupd_fetch_remote_error_ttl/acme/my-plugin-slug', function ($ttl, $vendor, $slug) {
    return 15 * MINUTE_IN_SECONDS;
}, 10, 3);
```

---

### `manual_check_redirect`

#### 1.4

```php
add_filter('uupd/manual_check_redirect/my-plugin-slug', function ($url) {
    return admin_url('plugins.php');
});
```

#### 2.0

```php
add_filter('uupd/manual_check_redirect/acme/my-plugin-slug', function ($url, $vendor, $slug) {
    return admin_url('plugins.php');
}, 10, 3);
```

---

## Action migration

### `uupd/before_fetch_remote`

This is one of the places where migration is easy to miss.

#### 1.4

Generic action:

```php
do_action('uupd/before_fetch_remote', $slug, $config);
```

There was no vendor context.

#### 2.0

Generic action:

```php
do_action('uupd/before_fetch_remote', $vendor, $slug, $config);
```

Scoped action:

```php
do_action("uupd/before_fetch_remote/{$vendor}/{$slug}", $config);
```

#### Example consumer code in 2.0

```php
add_action('uupd/before_fetch_remote/acme/my-plugin-slug', function ($config) {
    // inspect config before remote fetch
}, 10, 1);
```

---

### `uupd_metadata_fetch_failed`

2.0 keeps vendor-aware scoping and also still emits the old slug-only failure action for compatibility.

#### 1.4

```php
add_action('uupd_metadata_fetch_failed/my-plugin-slug', function ($data) {
    error_log('Update fetch failed');
});
```

#### 2.0

Preferred form:

```php
add_action('uupd_metadata_fetch_failed/acme/my-plugin-slug', function ($data) {
    error_log('Update fetch failed');
});
```

Legacy compatibility still exists for:

```php
add_action('uupd_metadata_fetch_failed/my-plugin-slug', function ($data) {
    error_log('Update fetch failed');
});
```

But new code should use the vendor-aware version.

---

## Manual check changes

Manual checks are now vendor-aware in 2.0.

### 1.4

Manual check action and nonce were slug-based.

### 2.0

Manual check action and nonce are based on the vendor + slug instance identity.

That means any custom code that was constructing manual check URLs or verifying nonce/action names must be updated alongside the hook changes.

---

## Common migration mistakes

### 1. Forgetting to add `vendor`

2.0 requires `vendor` in updater registration.

*Vendor and Slug are normalised to lowercase please remember this when adding in your vendor to the filters.*

### 2. Renaming only the hook string, but not the callback signature

Many 2.0 filters now pass:

- `$value`
- `$vendor`
- `$slug`

So old one-argument callbacks may still work in simple cases, but if you want to use vendor-aware logic you should update accepted arguments explicitly.

### 3. Keeping slug-only hooks in custom code

This is the most common migration miss.

Example that no longer works in 2.0:

```php
add_filter('uupd/server_url/my-plugin-slug', function ($url) {
    return 'https://example.com/custom-endpoint.json';
});
```

You must move it to:

```php
add_filter('uupd/server_url/acme/my-plugin-slug', function ($url, $vendor, $slug) {
    return 'https://example.com/custom-endpoint.json';
}, 10, 3);
```

### 4. Forgetting vendor-aware manual checks

If you had custom manual-check links, actions, or redirects, update them too.

---

## Quick checklist

Use this when migrating a product from 1.4 to 2.0:

- Add `vendor` to the updater config
- Confirm `slug` stays the intended product slug
- Update all `uupd/.../<slug>` filters to `uupd/.../<vendor>/<slug>`
- Update vendor-wide behavior to `uupd/.../<vendor>` where useful
- Update callback signatures to accept vendor and slug where needed
- Review any manual check code
- Review any custom failure/fetch hooks
- Retest prerelease visibility if you rely on `allow_prerelease`
- Retest GitHub private repo access if you rely on `github_token_override`

---

## Recommended migration pattern

For most products, the cleanest migration is:

1. add `vendor`
2. replace every old slug-only filter with vendor+slug
3. keep the logic unchanged
4. retest update detection, prerelease visibility, and package download

That gives you a true 2.0 migration without needing any compatibility shim.

---

## Summary

UUPD 2.0 is a breaking change, but the core updater functionality is still there.

What changed is the scoping model:

- 1.4 = slug-only identity
- 2.0 = vendor + slug identity

So the migration is mostly about updating:

- updater registration
- hook names
- callback signatures
- any custom manual-check wiring

If all old slug-only hooks have been properly migrated to vendor-aware forms, then you are in good shape.
