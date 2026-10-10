# UUPD 2.1 Signed Releases — Setup and Deployment Guide

This guide explains the complete process for enabling and deploying **signed UUPD 2.1 releases**, from generating the Ed25519 signing keys through to producing and publishing a signed `uupd/index.json`.

The signed-update feature is optional. Existing unsigned UUPD deployments can continue to work exactly as before by leaving:

```ini
UUPD_SIGNED_UPDATES=0
```

When signed updates are enabled, UUPD uses an **Ed25519 public/private key pair**:

- The **private key** is used only by the developer/release system to sign releases.
- The **public key** is embedded in the distributed plugin or theme and is used by UUPD to verify releases.
- The private key is never required by the installed plugin and must never be included in a release ZIP.

The signed metadata also contains the SHA-256 hash of the finished ZIP package. This means UUPD can authenticate both the update metadata and the actual package that WordPress installs.

---

## 1. Requirements

The signing system requires:

- PHP CLI
- PHP Sodium support
- `deploy.sh`
- `deploy.cfg`
- `generate_index.php`
- `uupd-sign-release.php`
- UUPD 2.1 in the plugin or theme
- Git and the existing deployment dependencies used by `deploy.sh`

Check that Sodium is available:

```bash
php -r "echo function_exists('sodium_crypto_sign_detached') ? 'Sodium OK' : 'Sodium missing';"
```

Expected result:

```text
Sodium OK
```

If Sodium is unavailable, signed releases cannot be generated or verified.

---

# 2. Understand the Key Pair

UUPD signed releases use **Ed25519 asymmetric signing**.

A key pair contains two different values.

## Private key

The private key:

- signs release metadata;
- must remain secret;
- stays on the developer/release side;
- must never be shipped inside the plugin;
- must never be committed to a public repository.

The UUPD signing helper stores it as a **Base64 encoded Ed25519 secret key**.

## Public key

The public key:

- verifies signatures;
- cannot be used to create signatures;
- is safe to distribute;
- belongs in the plugin/theme UUPD configuration.

The public key is also Base64 encoded.

The public key is not a password or shared secret. An attacker obtaining it does not gain the ability to sign a malicious update.

---

# 3. Generate the Signing Keys

Keys only need to be generated once unless you deliberately rotate them.

Run:

```bash
php uupd-sign-release.php \
  --generate \
  --private-key-file=uupd-release.key \
  --public-key-file=uupd-release.pub
```

On Windows Git Bash, the command is the same.

The helper creates two files:

```text
uupd-release.key
uupd-release.pub
```

The output should report that an Ed25519 key pair was generated.

---

# 4. Key File Formats

Both generated files contain a single Base64 encoded value followed by a newline.

## Private key file

Example structure:

```text
BASE64_ED25519_PRIVATE_KEY
```

Do not add:

```text
-----BEGIN PRIVATE KEY-----
```

or any PEM headers.

The current UUPD helper expects the raw Ed25519 secret key encoded directly as Base64.

## Public key file

Example structure:

```text
BASE64_ED25519_PUBLIC_KEY
```

Again, there are no PEM headers.

The public key value is the string that will be placed in the plugin's UUPD configuration.

---

# 5. Protect the Private Key

Treat `uupd-release.key` as a release credential.

Recommended rules:

- Store it outside the Git repository.
- Do not include it in Dropbox/shared folders unless that location is appropriately secured.
- Do not put it inside the plugin directory.
- Do not include it in `PACKAGE_EXCLUDES` as your only protection; it should ideally never be under the package source tree at all.
- Keep a secure backup. Losing the only private key means you cannot produce further releases that validate against the existing public key.
- Anyone who obtains the private key can create an update that appears authentic to installations trusting its public key.

The generated helper attempts to set the private-key file to Unix mode `0600` where supported.

---

# 6. Obtain the Public Key

To display the public key:

```bash
cat uupd-release.pub
```

Copy the complete Base64 value.

Alternatively, if you have only the private key, the signing helper can derive the matching public key:

```bash
php uupd-sign-release.php \
  --metadata=dummy.json \
  --package=dummy.zip \
  --output=unused.json \
  --private-key-file=uupd-release.key \
  --public-key-output
```

However, keeping the generated `.pub` file is simpler.

The public key is safe to store in source control.

---

# 7. Configure the Plugin or Theme to Trust the Public Key

The distributed plugin/theme receives the **public key only**.

A UUPD registration using signed updates looks conceptually like:

```php
\UUPD\V2\UUPD_Updater_V2::register( [
    'vendor'      => 'your-vendor',
    'plugin_file' => plugin_basename( __FILE__ ),
    'slug'        => 'your-plugin',
    'name'        => 'Your Plugin',
    'version'     => YOUR_PLUGIN_VERSION,
    'server'      => 'https://raw.githubusercontent.com/OWNER/REPO/main/uupd/index.json',

    'signed_updates'     => true,
    'signing_public_key' => 'BASE64_PUBLIC_KEY_HERE',
    'signing_key_id'     => 'release-2026',
] );
```

Replace:

```text
BASE64_PUBLIC_KEY_HERE
```

with the contents of `uupd-release.pub`.

The private key must **not** appear anywhere in this code.

---

# 8. Signing Key ID

The optional key ID identifies which signing key produced the release.

Example:

```ini
UUPD_SIGNING_KEY_ID=release-2026
```

The plugin can use the matching value:

```php
'signing_key_id' => 'release-2026',
```

The key ID:

- is public;
- is not cryptographic secret material;
- is useful for diagnostics;
- makes future key rotation easier to manage.

Choose a stable, descriptive value such as:

```text
release-2026
production-1
main-signing-2026
```

Do not use the private key itself as the key ID.

---

# 9. Configure `deploy.cfg`

Enable signed releases:

```ini
UUPD_SIGNED_UPDATES=1
```

Set the path to the signing helper:

```ini
UUPD_SIGNER_SCRIPT=/c/path/to/deployscripts/uupd-sign-release.php
```

Then choose how `deploy.sh` obtains the private key.

There are three supported methods:

1. interactive prompt;
2. value stored in `deploy.cfg`;
3. external private-key file.

---

# 10. Recommended Method — Prompt for the Private Key

For manual deployments, use:

```ini
UUPD_SIGNED_UPDATES=1
UUPD_SIGNING_KEY_SOURCE=prompt
UUPD_SIGNING_PRIVATE_KEY=
UUPD_SIGNING_PRIVATE_KEY_FILE=
UUPD_SIGNING_KEY_ID=release-2026
```

When `deploy.sh` reaches the signing stage, it prompts for the private key.

The input is hidden.

The key:

- is not written to a key file;
- is not supplied as a PHP command-line argument;
- is passed to the signer using standard input;
- is removed from the shell variable after signing.

To use this mode, open your private-key file and copy its Base64 value when performing the release.

For example:

```bash
cat /c/secure/uupd-release.key
```

Then run:

```bash
./deploy.sh
```

When prompted:

```text
UUPD Ed25519 private key (base64):
```

paste the key and press Enter.

This is the recommended simple method for an interactive release workstation.

---

# 11. Alternative — Private Key Stored in `deploy.cfg`

Set:

```ini
UUPD_SIGNED_UPDATES=1
UUPD_SIGNING_KEY_SOURCE=config
UUPD_SIGNING_PRIVATE_KEY=BASE64_PRIVATE_KEY_HERE
UUPD_SIGNING_KEY_ID=release-2026
```

This is convenient, but it means the private key is stored in `deploy.cfg`.

Only use this if the configuration file is adequately protected and definitely not committed or distributed.

If using this method, ensure `.gitignore` prevents accidental commits where appropriate.

Example:

```gitignore
deploy.cfg
```

This mode is less desirable for repositories where `deploy.cfg` is version controlled.

---

# 12. Alternative — External Private-Key File

Set:

```ini
UUPD_SIGNED_UPDATES=1
UUPD_SIGNING_KEY_SOURCE=file
UUPD_SIGNING_PRIVATE_KEY_FILE=/c/secure/uupd-release.key
UUPD_SIGNING_KEY_ID=release-2026
```

This is useful for:

- a dedicated release workstation;
- protected CI/CD storage;
- a secured removable drive;
- a secrets directory outside the repository.

The file must contain the Base64 Ed25519 private key generated by the helper.

Do not point this at the `.pub` file.

---

# 13. Prepare a Release Normally

Before running the deployment:

1. Make the required plugin changes.
2. Update the changelog.
3. Ensure the correct release branch is checked out.
4. Run tests appropriate to the plugin.
5. Confirm `deploy.cfg` contains the correct plugin slug, repository and ZIP name.
6. Confirm the plugin contains the correct public signing key.
7. Confirm the signing key ID matches the deployment configuration if key IDs are enforced.

Your normal deployment metadata remains in `generate_index.php`.

The generator still creates fields such as:

```json
{
  "slug": "your-plugin",
  "name": "Your Plugin",
  "version": "2.1.0",
  "download_url": "https://github.com/OWNER/REPO/releases/latest/download/your-plugin.zip"
}
```

The signer later adds the package hash and wraps this data in the signed envelope.

---

# 14. Use a Dry Run First

For a new signing setup, initially use:

```ini
DRY_RUN=1
UUPD_SIGNED_UPDATES=1
```

Then run:

```bash
./deploy.sh
```

A dry run allows the script to:

- update/check plugin headers;
- generate metadata;
- build the release ZIP;
- run package safety checks;
- calculate the ZIP SHA-256;
- sign the metadata;
- create the final signed `uupd/index.json`;

without performing the live GitHub release steps.

Inspect the generated files before switching to live mode.

---

# 15. Why Signed Metadata Is Generated After the ZIP

With unsigned UUPD releases, metadata could be generated before the release ZIP existed.

Signed UUPD 2.1 releases bind the metadata to the **exact finished ZIP**.

Therefore the sequence changes slightly.

The signed deployment flow is:

```text
Plugin source
    |
    v
Update/check plugin headers
    |
    v
Generate ordinary UUPD metadata
    |
    v
Stage metadata temporarily
    |
    v
Build final WordPress plugin ZIP
    |
    v
Run ZIP safety checks
    |
    v
Calculate SHA-256 of the finished ZIP
    |
    v
Add package_sha256 to metadata
    |
    v
Sign metadata using Ed25519 private key
    |
    v
Write uupd/index.json
    |
    v
Git commit / push
    |
    v
Create or update GitHub Release
    |
    v
Upload ZIP asset
```

This order is important.

If the ZIP changed after the hash was signed, installed UUPD clients would reject it.

---

# 16. What the Signer Adds

The ordinary metadata is read by `uupd-sign-release.php`.

The signer calculates:

```text
SHA-256(package ZIP)
```

and adds:

```json
"package_sha256": "..."
```

to the metadata payload.

The resulting payload is then encoded and signed with Ed25519.

---

# 17. Signed `index.json` Format

The published `uupd/index.json` is no longer the ordinary metadata object when signing is enabled.

It becomes a signed envelope similar to:

```json
{
  "alg": "ed25519",
  "signed": "BASE64_ENCODED_METADATA_PAYLOAD",
  "signature": "BASE64_ED25519_SIGNATURE",
  "key_id": "release-2026"
}
```

## `alg`

```json
"alg": "ed25519"
```

Identifies the signature algorithm.

## `signed`

Contains the exact JSON metadata payload encoded as Base64.

The decoded payload includes fields such as:

```json
{
  "slug": "your-plugin",
  "version": "2.1.0",
  "download_url": "https://github.com/OWNER/REPO/releases/latest/download/your-plugin.zip",
  "package_sha256": "..."
}
```

## `signature`

The Base64 encoded Ed25519 signature over the exact decoded `signed` payload.

## `key_id`

Optional public identifier for the signing key.

---

# 18. What Happens During a Live Deployment

After a successful dry run, set:

```ini
DRY_RUN=0
```

and ensure:

```ini
UUPD_SIGNED_UPDATES=1
```

Then run:

```bash
./deploy.sh
```

For prompt mode, enter the private key when requested.

The deployment performs the normal release process and also signs the updater metadata.

A successful signed deployment should result in:

```text
uupd/index.json
your-plugin.zip
```

where:

- `index.json` has a valid Ed25519 signature;
- its signed payload contains the SHA-256 of `your-plugin.zip`;
- the GitHub Release contains that exact ZIP.

---

# 19. What UUPD Verifies on the Customer Site

The installed plugin already contains the public key.

When it checks for an update, UUPD can perform the following checks.

## Stage 1 — Fetch signed metadata

UUPD downloads:

```text
uupd/index.json
```

An attacker may be able to see or intercept the network request, but that alone does not let them forge the release.

## Stage 2 — Decode the signed payload

UUPD extracts:

```text
signed
signature
alg
key_id
```

## Stage 3 — Verify Ed25519 signature

The signature is verified using the public key embedded in the installed plugin.

If an attacker changes any signed metadata, including:

- version;
- download URL;
- changelog;
- package SHA-256;

the signature no longer verifies.

The update is rejected.

## Stage 4 — Download the ZIP

If the metadata is authentic and a newer version exists, WordPress downloads the package from the signed download URL.

## Stage 5 — Verify package SHA-256

UUPD calculates the downloaded ZIP's SHA-256.

It compares the result against:

```text
package_sha256
```

from the authenticated metadata.

If the ZIP was changed, corrupted or substituted, the hash differs and installation is blocked.

---

# 20. Security Model

Signed UUPD releases protect against an attacker who can modify update traffic but does not possess the signing private key.

For example, an attacker cannot successfully change:

```text
version 2.1.0
```

to:

```text
version 99.0.0
```

because that changes the signed bytes.

They cannot change:

```text
https://github.com/you/plugin/releases/latest/download/plugin.zip
```

to:

```text
https://attacker.example/malware.zip
```

because the URL is inside the signed payload.

They cannot simply replace the legitimate ZIP at the expected URL because the ZIP hash must match the signed `package_sha256`.

---

# 21. Important Limitation — Protect the Private Key

Signature verification is only as trustworthy as the private key remains secret.

If an attacker obtains the private key, they can produce signatures that installations will trust.

Protecting the private key is therefore the most important operational requirement of signed releases.

The public key does not require secrecy.

---

# 22. Do Not Put the Private Key in the Plugin

Never configure something like:

```php
'signing_private_key' => '...'
```

inside a WordPress plugin.

There should be no private signing key in:

- plugin PHP;
- JavaScript;
- bundled configuration;
- `uupd/index.json`;
- GitHub source repositories;
- release ZIPs;
- public CI logs.

Only the public key belongs in distributed code.

---

# 23. Package Excludes

Your deployment configuration should continue to exclude release-only material.

For example:

```ini
PACKAGE_EXCLUDES=.git .github .idea .vscode build node_modules uupd deploy.sh deploy.cfg *.bak *.zip
```

The exact list depends on the plugin.

The key files should ideally be outside the repository altogether.

If they are ever stored within the repository directory locally, explicitly exclude them:

```ini
PACKAGE_EXCLUDES=.git .github uupd deploy.sh deploy.cfg uupd-release.key uupd-release.pub *.zip
```

However, keeping the private key outside the source tree remains preferable.

---

# 24. Git Ignore Recommendations

If private deployment files are kept in or near the repository, consider adding:

```gitignore
uupd-release.key
*.key
```

Only do this as an additional safety measure.

Do not rely solely on `.gitignore` to protect secrets that should live elsewhere.

The public key may safely be committed if desired:

```text
uupd-release.pub
```

although it normally does not need to be included in the plugin package once its value has been copied into the UUPD configuration.

---

# 25. Generate a New Key Pair for a New Signing Identity

To create another key pair:

```bash
php uupd-sign-release.php \
  --generate \
  --private-key-file=uupd-release-2027.key \
  --public-key-file=uupd-release-2027.pub
```

Use a new key ID:

```ini
UUPD_SIGNING_KEY_ID=release-2027
```

Do not overwrite an existing private key until you have deliberately planned the migration.

---

# 26. Key Rotation

Key rotation requires care because already-installed plugins trust the public key embedded in their currently installed version.

A safe conceptual rotation is:

1. Keep signing with the old private key.
2. Publish a plugin version that knows/trusts the new public key or implements the planned multi-key transition.
3. Allow installations to receive that transition release.
4. Start signing subsequent releases with the new private key.
5. Change the key ID accordingly.
6. Retire the old private key once the migration policy allows it.

Do **not** simply switch to a completely new key if existing installations only know the old public key. They will correctly reject releases signed only by the unknown key.

The exact rotation strategy depends on the final multi-key/key-rotation features supported by the UUPD client version you deploy.

---

# 27. Lost Private Key

If the private key is lost but has not been compromised:

- existing installations can still verify old signed metadata;
- you cannot create new releases signed with that key;
- a key-rotation path may be required;
- simply generating a new key is not enough if installed copies know only the old public key.

Keep at least one secure backup of production signing keys.

---

# 28. Compromised Private Key

If you believe the private key has been copied by an unauthorized party, treat the signing identity as compromised.

Do not continue trusting that key merely because the file still exists on your own machine.

A compromised private key can be used to generate apparently valid releases.

You will need an appropriate key revocation/rotation strategy and potentially another trusted delivery mechanism to move installed clients to a new public key.

---

# 29. Common Configuration Examples

## Signed release with interactive key

```ini
DRY_RUN=0

UUPD_SIGNED_UPDATES=1
UUPD_SIGNER_SCRIPT=/c/secure/deployscripts/uupd-sign-release.php

UUPD_SIGNING_KEY_SOURCE=prompt
UUPD_SIGNING_PRIVATE_KEY=
UUPD_SIGNING_PRIVATE_KEY_FILE=
UUPD_SIGNING_KEY_ID=release-2026
```

## Signed release using protected key file

```ini
DRY_RUN=0

UUPD_SIGNED_UPDATES=1
UUPD_SIGNER_SCRIPT=/c/secure/deployscripts/uupd-sign-release.php

UUPD_SIGNING_KEY_SOURCE=file
UUPD_SIGNING_PRIVATE_KEY_FILE=/c/secure/keys/uupd-release.key
UUPD_SIGNING_KEY_ID=release-2026
```

## Unsigned legacy release

```ini
UUPD_SIGNED_UPDATES=0
```

When signed updates are disabled, the signing-key settings are not used.

---

# 30. First-Time Setup Checklist

For each signing identity:

- [ ] Confirm PHP Sodium support is available.
- [ ] Generate an Ed25519 key pair.
- [ ] Move the private key to secure storage.
- [ ] Back up the private key securely.
- [ ] Copy the public key into the plugin's UUPD configuration.
- [ ] Choose a signing key ID.
- [ ] Set the same key ID in `deploy.cfg`.
- [ ] Set `UUPD_SIGNED_UPDATES=1`.
- [ ] Choose `prompt`, `file`, or `config` key source.
- [ ] Set `DRY_RUN=1`.
- [ ] Run a test deployment.
- [ ] Inspect the generated signed `uupd/index.json`.
- [ ] Verify the plugin ZIP does not contain private/deployment material.
- [ ] Change to `DRY_RUN=0` only after the test succeeds.

---

# 31. Per-Release Checklist

Before every signed release:

- [ ] Be on the correct release branch.
- [ ] Confirm the plugin version is correct.
- [ ] Update the changelog.
- [ ] Run plugin tests.
- [ ] Confirm the correct public key remains configured in UUPD.
- [ ] Confirm the correct `UUPD_SIGNING_KEY_ID`.
- [ ] Confirm the correct private key is available.
- [ ] Run `./deploy.sh`.
- [ ] Enter the private key if using prompt mode.
- [ ] Confirm the ZIP safety checks pass.
- [ ] Confirm signed `uupd/index.json` is generated.
- [ ] Confirm the GitHub Release succeeds.
- [ ] Confirm the expected ZIP asset exists on the release.

---

# 32. Troubleshooting

## `PHP sodium/Ed25519 support is required`

PHP CLI does not have the Sodium functions available.

Check:

```bash
php -m | grep -i sodium
```

or:

```bash
php -r "var_dump(function_exists('sodium_crypto_sign_detached'));"
```

The result must be `true`.

---

## `Private key is invalid; expected base64 Ed25519 secret key`

Possible causes:

- the public key was supplied instead of the private key;
- whitespace or extra text was copied with the key;
- the key is not the raw UUPD generated Ed25519 secret key;
- a PEM/OpenSSH key was supplied rather than the Base64 format expected by the helper.

Generate keys using:

```bash
php uupd-sign-release.php \
  --generate \
  --private-key-file=uupd-release.key \
  --public-key-file=uupd-release.pub
```

---

## `No signing key entered`

Prompt mode was selected but no private key was supplied.

Check:

```ini
UUPD_SIGNING_KEY_SOURCE=prompt
```

and paste the complete Base64 private key at the prompt.

---

## `UUPD_SIGNING_PRIVATE_KEY is empty but key source is config`

You selected:

```ini
UUPD_SIGNING_KEY_SOURCE=config
```

but did not provide:

```ini
UUPD_SIGNING_PRIVATE_KEY=...
```

Either supply the key or change the source to `prompt` or `file`.

---

## `Signing private key file not found`

Check:

```ini
UUPD_SIGNING_PRIVATE_KEY_FILE=/correct/path/to/uupd-release.key
```

Remember that Git Bash typically uses paths such as:

```text
/c/secure/uupd-release.key
```

rather than:

```text
C:\secure\uupd-release.key
```

for shell commands.

---

## Signed metadata works but ZIP verification fails

This normally means the ZIP being downloaded is not byte-for-byte identical to the ZIP whose hash was signed.

Possible causes:

- the ZIP was rebuilt after `index.json` was signed;
- the wrong release asset was uploaded;
- the release asset filename points to another package;
- a deployment process modified/recompressed the ZIP after signing.

The correct process is always:

```text
finish ZIP
-> hash ZIP
-> sign metadata containing hash
-> publish that exact ZIP
```

Do not rebuild the ZIP after signing.

---

## Signature verification fails on installed plugins

Check:

- the public key matches the private key used for signing;
- `signing_key_id` matches where enforced;
- the published `index.json` has not been manually edited;
- the plugin is actually running the signed-update capable UUPD version;
- the server points at the signed `index.json`, not an old unsigned file.

Never manually edit the contents of a signed payload after signing.

Any change is supposed to invalidate the signature.

---

# 33. Recommended Production Layout

A useful layout is:

```text
C:/secure/uupd-keys/
    production-release.key
    production-release.pub

C:/deployscripts/
    deploy.sh
    generate_index.php
    myplugin_headers.php
    uupd-sign-release.php

plugin-repository/
    deploy.cfg
    plugin-folder/
    uupd/
        index.json
```

The important separation is:

```text
PRIVATE KEY -> secure location outside repository
PUBLIC KEY  -> distributed plugin configuration
```

---

# 34. Recommended Manual Release Configuration

For a developer manually deploying releases, the recommended combination is:

```ini
UUPD_SIGNED_UPDATES=1
UUPD_SIGNING_KEY_SOURCE=prompt
UUPD_SIGNING_PRIVATE_KEY=
UUPD_SIGNING_PRIVATE_KEY_FILE=
UUPD_SIGNING_KEY_ID=release-2026
```

This avoids storing the private key in the plugin configuration or `deploy.cfg`.

If frequent manual pasting becomes inconvenient, the protected external-file mode is a reasonable next step:

```ini
UUPD_SIGNING_KEY_SOURCE=file
UUPD_SIGNING_PRIVATE_KEY_FILE=/c/secure/uupd-release.key
```

---

# 35. Complete Example

## Generate keys once

```bash
php /c/deployscripts/uupd-sign-release.php \
  --generate \
  --private-key-file=/c/secure/uupd-release.key \
  --public-key-file=/c/secure/uupd-release.pub
```

## Read public key

```bash
cat /c/secure/uupd-release.pub
```

## Add public key to plugin

```php
\UUPD\V2\UUPD_Updater_V2::register( [
    'vendor'      => 'example-vendor',
    'plugin_file' => plugin_basename( __FILE__ ),
    'slug'        => 'example-plugin',
    'name'        => 'Example Plugin',
    'version'     => EXAMPLE_PLUGIN_VERSION,
    'server'      => 'https://raw.githubusercontent.com/example/example-plugin/main/uupd/index.json',

    'signed_updates'     => true,
    'signing_public_key' => 'PASTE_PUBLIC_KEY_HERE',
    'signing_key_id'     => 'release-2026',
] );
```

## Configure deploy.cfg

```ini
UUPD_SIGNED_UPDATES=1
UUPD_SIGNER_SCRIPT=/c/deployscripts/uupd-sign-release.php

UUPD_SIGNING_KEY_SOURCE=prompt
UUPD_SIGNING_PRIVATE_KEY=
UUPD_SIGNING_PRIVATE_KEY_FILE=
UUPD_SIGNING_KEY_ID=release-2026

DRY_RUN=1
```

## Test

```bash
./deploy.sh
```

Inspect the generated files.

## Enable live deployment

```ini
DRY_RUN=0
```

Then:

```bash
./deploy.sh
```

Paste the Base64 private key when prompted.

The deployment builds the final ZIP, calculates its SHA-256, signs the metadata, publishes `uupd/index.json`, commits/pushes the release changes and uploads the package according to the configured deployment target.

---

# 36. Summary

The complete trust model is:

```text
ONE-TIME SETUP

Ed25519 key generation
        |
        +---- PRIVATE KEY ----> secure developer/release storage
        |
        +---- PUBLIC KEY -----> embedded in plugin/theme


EACH RELEASE

Plugin source
    |
Build final ZIP
    |
SHA-256 ZIP
    |
Create metadata containing ZIP hash
    |
Sign metadata with PRIVATE key
    |
Publish signed index.json + exact ZIP


CUSTOMER UPDATE

Download signed index.json
    |
Verify signature with PUBLIC key
    |
Read authenticated version/download URL/package hash
    |
Download ZIP
    |
Verify ZIP SHA-256
    |
Install only if verification succeeds
```

The central rule is simple:

> **The private key signs. The public key verifies. The private key never ships.**

With that separation maintained, a compromised distribution path or man-in-the-middle attacker cannot replace either the update metadata or the release package with a modified version that passes UUPD's signed-update verification.
