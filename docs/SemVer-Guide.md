# Dummy Guide to Semantic Versioning (SemVer)

Semantic Versioning (SemVer) is a versioning scheme that uses a three-part number: `MAJOR.MINOR.PATCH`.

---

## 🔢 Version Structure

**Format:**  
```
MAJOR.MINOR.PATCH[-PRERELEASE][+BUILD]
```

### Example Versions:
- `1.0.0` → Stable release
- `2.1.4` → Minor feature added
- `3.0.0` → Breaking changes introduced
- `1.4.2-beta` → Beta pre-release of 1.4.2
- `2.0.0-alpha.1+20230701` → Alpha release with metadata

---

## 📖 What Each Part Means

### MAJOR
Increase when:
- You break backward compatibility.
- The API or functionality changes in a way that old users must modify their code.

**Example:** `1.0.0` → `2.0.0`

---

### MINOR
Increase when:
- You add new functionality in a backwards-compatible way.
- No breaking changes introduced.

**Example:** `1.2.3` → `1.3.0`

---

### PATCH
Increase when:
- You fix bugs or make minor improvements.
- No new features or breaking changes.

**Example:** `1.2.3` → `1.2.4`

---

## 🚧 Pre-release Labels

Use `-alpha`, `-beta`, `-rc` (release candidate) to indicate in-progress versions.

Examples:
- `1.2.0-alpha`
- `1.2.0-beta.1`
- `1.2.0-rc.2`

These are useful for testing and early feedback.

---

## 🛠 Best Practices

- Keep a changelog to track what changed per version.
- Use pre-release tags (`-beta`, etc.) for testers before a full release.
- Don’t skip PATCH or MINOR levels—keep them consistent.
- `1.0.0` is your first "stable" release.

---

## 🧪 Bonus: Build Metadata

SemVer allows a `+` suffix for build information (ignored by most tooling).

Example:  
`1.2.3+build5678` → Still the same as `1.2.3`

---

## ✅ Summary

| Change Type       | Example        | Description                          |
|-------------------|----------------|--------------------------------------|
| Breaking change   | 1.0.0 → 2.0.0  | Major version bump                   |
| New feature       | 1.0.0 → 1.1.0  | Minor version bump                   |
| Bug fix           | 1.0.0 → 1.0.1  | Patch version bump                   |
| Beta release      | 2.0.0 → 2.0.0-beta | Prerelease testing version       |

---

Follow these principles and your users (and tools) will thank you!