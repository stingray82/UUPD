# UUPD Migration Guide (v1 → v2)

This guide helps you upgrade from **UUPD v1.x** to **UUPD v2.0**.

---

##  Important: Breaking Changes

UUPD 2.0 introduces a **new identity model** and is **NOT backwards compatible**.

### Key breaking changes:

- ❌ Slug-only identity removed  
- ❌ Slug-only filters removed  
- ❌ Global filter assumptions removed  
- ✅ `vendor` is now REQUIRED  
- ✅ Filters are now vendor + slug scoped  

---

## Why This Change?

In v1, plugins were identified only by:

```
slug
```

This caused collisions when multiple plugins bundled UUPD.

In v2:

```
vendor + slug
```

This guarantees **unique updater instances** across all plugins and themes.

---

## Step-by-Step Migration

### 1. Add `vendor` to your config

**Before (v1):**
```php
\UUPD\V1\UUPD_Updater_V1::register([
    'slug' => 'my-plugin',
]);
```

**After (v2):**
```php
\UUPD\V2\UUPD_Updater_V2::register([
    'vendor' => 'my-company',
    'slug'   => 'my-plugin',
]);
```

---

### 2. Update your class reference

Replace:

```
UUPD\V1\UUPD_Updater_V1
```

With:

```
UUPD\V2\UUPD_Updater_V2
```

---

### 3. Update ALL filters

#### ❌ Old (v1):
```php
add_filter('uupd/server_url', function($url, $slug) {
    return $url;
});
```

#### ✅ New (v2):
```php
add_filter('uupd/server_url/myvendor/my-plugin', function($url, $vendor, $slug) {
    return $url;
}, 10, 3);
```

---

### 4. Update GitHub token filters

#### ❌ Old:
```php
add_filter('uupd/github_token_override', function($token, $slug) {
    return 'ghp_xxx';
}, 10, 2);
```

#### ✅ New:
```php
add_filter('uupd/github_token_override/myvendor/my-plugin', function($token, $vendor, $slug) {
    return 'ghp_xxx';
}, 10, 3);
```

---

### 5. Check cache prefix assumptions

v2 now defaults to:

```
uupd_<vendor>__
```

If you were relying on custom prefixes, verify compatibility.

---

### 6. Verify GitHub mode behaviour

No major changes, but:

- Ensure `server` is a **repo root**
- Or explicitly set:
```php
'mode' => 'github_release'
```

---

## 🧪 Testing Checklist

After migrating:

- ✅ Update appears in WP admin  
- ✅ Plugin downloads correctly  
- ✅ No infinite update loops  
- ✅ GitHub releases resolve properly  
- ✅ Filters are firing (test with logging)  

---

## Common Issues

### Infinite update loop
Usually caused by:
- Wrong package URL
- Zip structure mismatch

---

### Filters not firing
Cause:
- Still using slug-only filters

Fix:
- Convert to `vendor/slug` format

---

### GitHub downloads failing
Cause:
- Token filter not updated

Fix:
- Use scoped token filter

---

## When NOT to migrate yet

Stay on v1 if:

- You rely heavily on existing filters
- You need stability over isolation
- You haven’t tested GitHub edge cases

---

## Summary

| Area          | v1                | v2                     |
|---------------|------------------|------------------------|
| Identity      | slug             | vendor + slug          |
| Filters       | global/slug      | vendor/scoped          |
| Compatibility | stable           | breaking (alpha)       |
| Safety        | collision risk   | isolated instances     |

## Need Help?

If you hit issues:

- Check logs (`updater_enable_debug`)
- Verify filter names
- Confirm vendor + slug consistency

---

🎉 You’re now ready for UUPD 2.0!
