#!/usr/bin/env bash
set -euo pipefail

: "${TMPDIR:=$(mktemp -d)}"

script_dir="$(cd -- "$(dirname -- "$0")" && pwd -P)"
config_file="$script_dir/deploy.cfg"

if [[ ! -f "$config_file" ]]; then
  echo "[ERROR] Config file not found: $config_file"
  exit 1
fi

# Clear known variables so old shell state does not leak in.
unset SCRIPT_LABEL PLUGIN_NAME PLUGIN_TAGS PLUGIN_SLUG HEADER_SCRIPT CHANGELOG_FILE STATIC_FILE DEST_DIR DEPLOY_TARGET
unset GITHUB_REPO TOKEN_FILE ZIP_NAME GENERATOR_SCRIPT GITHUB_TOKEN SOURCE_MODE MAIN_PLUGIN_FILE
unset RELEASE_BRANCH UUPD_DIR DRY_RUN REQUIRE_RELEASE_BRANCH SKIP_README_GENERATION
unset PACKAGE_EXCLUDES ZIP_FORBIDDEN_PATHS REQUIRE_RSYNC
unset UUPD_SIGNED_UPDATES UUPD_SIGNER_SCRIPT UUPD_SIGNING_KEY_SOURCE UUPD_SIGNING_PRIVATE_KEY
unset UUPD_SIGNING_PRIVATE_KEY_FILE UUPD_SIGNING_KEY_ID
unset README_CONTRIBUTORS README_DONATE_LINK README_LICENSE README_LICENSE_URI

# Parse KEY=VALUE config. Supports # and ; comments.
while IFS= read -r line || [[ -n "$line" ]]; do
  line="${line//$'\r'/}"
  [[ -z "$line" || "${line:0:1}" == "#" || "${line:0:1}" == ";" ]] && continue
  key="${line%%=*}"
  val="${line#*=}"
  key="$(echo "$key" | sed -e 's/^[[:space:]]*//' -e 's/[[:space:]]*$//')"
  val="$(echo "$val" | sed -e 's/^[[:space:]]*//' -e 's/[[:space:]]*$//')"
  eval "$key=\"\$val\""
done < "$config_file"

# Defaults.
SCRIPT_LABEL="${SCRIPT_LABEL:-Plugin Deploy}"
HEADER_SCRIPT="${HEADER_SCRIPT:-C:/Ignore By Avast/0. PATHED Items/Plugins/deployscripts/myplugin_headers.php}"
TOKEN_FILE="${TOKEN_FILE:-C:/Ignore By Avast/0. PATHED Items/Plugins/deployscripts/github_token.txt}"
GENERATOR_SCRIPT="${GENERATOR_SCRIPT:-C:/Ignore By Avast/0. PATHED Items/Plugins/deployscripts/signed/generate_index.php}"
UUPD_SIGNER_SCRIPT="${UUPD_SIGNER_SCRIPT:-C:/Ignore By Avast/0. PATHED Items/Plugins/deployscripts/signed/uupd-sign-release.php}"

SOURCE_MODE="${SOURCE_MODE:-root}"
PLUGIN_NAME="${PLUGIN_NAME:-${PLUGIN_SLUG:-}}"
PLUGIN_TAGS="${PLUGIN_TAGS:-}"
ZIP_NAME="${ZIP_NAME:-${PLUGIN_SLUG:-plugin}.zip}"
CHANGELOG_FILE="${CHANGELOG_FILE:-changelog.txt}"
STATIC_FILE="${STATIC_FILE:-static.txt}"
DEPLOY_TARGET="${DEPLOY_TARGET:-github}"
UUPD_DIR="${UUPD_DIR:-uupd}"
DRY_RUN="${DRY_RUN:-0}"
REQUIRE_RELEASE_BRANCH="${REQUIRE_RELEASE_BRANCH:-1}"
SKIP_README_GENERATION="${SKIP_README_GENERATION:-0}"
REQUIRE_RSYNC="${REQUIRE_RSYNC:-0}"

# UUPD 2.1 signed updates are opt-in. Unsigned behavior is unchanged by default.
UUPD_SIGNED_UPDATES="${UUPD_SIGNED_UPDATES:-0}"
UUPD_SIGNING_KEY_SOURCE="${UUPD_SIGNING_KEY_SOURCE:-prompt}"
UUPD_SIGNING_PRIVATE_KEY="${UUPD_SIGNING_PRIVATE_KEY:-}"
UUPD_SIGNING_PRIVATE_KEY_FILE="${UUPD_SIGNING_PRIVATE_KEY_FILE:-}"
UUPD_SIGNING_KEY_ID="${UUPD_SIGNING_KEY_ID:-}"

README_CONTRIBUTORS="${README_CONTRIBUTORS:-reallyusefulplugins}"
README_DONATE_LINK="${README_DONATE_LINK:-https://reallyusefulplugins.com/donate}"
README_LICENSE="${README_LICENSE:-GPL-2.0-or-later}"
README_LICENSE_URI="${README_LICENSE_URI:-https://www.gnu.org/licenses/gpl-2.0.html}"

PACKAGE_EXCLUDES="${PACKAGE_EXCLUDES:-.git .github .idea .vscode build node_modules $UUPD_DIR deploy.sh deploy.cfg *.bak *.zip}"
ZIP_FORBIDDEN_PATHS="${ZIP_FORBIDDEN_PATHS:-$UUPD_DIR/}"

echo "[INFO] $SCRIPT_LABEL"

# Validation.
[[ -n "${PLUGIN_SLUG:-}" ]] || { echo "[ERROR] PLUGIN_SLUG is not defined in deploy.cfg"; exit 1; }
[[ -n "${GITHUB_REPO:-}" ]] || { echo "[ERROR] GITHUB_REPO is not defined in deploy.cfg"; exit 1; }
[[ -n "${RELEASE_BRANCH:-}" ]] || { echo "[ERROR] RELEASE_BRANCH is not defined in deploy.cfg"; exit 1; }

repo_root="$script_dir"

if [[ "$SOURCE_MODE" == "root" ]]; then
  plugin_dir="$repo_root"
  plugin_file="$repo_root/${MAIN_PLUGIN_FILE:-$PLUGIN_SLUG.php}"
else
  plugin_dir="$repo_root/$PLUGIN_SLUG"
  plugin_file="$plugin_dir/${MAIN_PLUGIN_FILE:-$PLUGIN_SLUG.php}"
fi

readme_file="$plugin_dir/readme.txt"
temp_readme="$TMPDIR/readme_temp.txt"
static_subfolder="$repo_root/$UUPD_DIR"
zip_file="$repo_root/$ZIP_NAME"

[[ -f "$plugin_file" ]] || { echo "[ERROR] Plugin file not found: $plugin_file"; exit 1; }
[[ -f "$CHANGELOG_FILE" ]] || { echo "[ERROR] Changelog file not found: $CHANGELOG_FILE"; exit 1; }
[[ -f "$STATIC_FILE" ]] || { echo "[ERROR] Static readme file not found: $STATIC_FILE"; exit 1; }
[[ -f "$HEADER_SCRIPT" ]] || { echo "[ERROR] Header script not found: $HEADER_SCRIPT"; exit 1; }
[[ -f "$GENERATOR_SCRIPT" ]] || { echo "[ERROR] Generator script not found: $GENERATOR_SCRIPT"; exit 1; }

if [[ "$UUPD_SIGNED_UPDATES" == "1" ]]; then
  [[ -f "$UUPD_SIGNER_SCRIPT" ]] || { echo "[ERROR] UUPD signer script not found: $UUPD_SIGNER_SCRIPT"; exit 1; }
  case "${UUPD_SIGNING_KEY_SOURCE,,}" in
    prompt|config|file) ;;
    *) echo "[ERROR] UUPD_SIGNING_KEY_SOURCE must be prompt, config, or file."; exit 1 ;;
  esac
  if [[ "${UUPD_SIGNING_KEY_SOURCE,,}" == "config" && -z "$UUPD_SIGNING_PRIVATE_KEY" ]]; then
    echo "[ERROR] UUPD_SIGNING_PRIVATE_KEY is empty but key source is config."
    exit 1
  fi
  if [[ "${UUPD_SIGNING_KEY_SOURCE,,}" == "file" ]]; then
    [[ -n "$UUPD_SIGNING_PRIVATE_KEY_FILE" ]] || { echo "[ERROR] UUPD_SIGNING_PRIVATE_KEY_FILE is empty."; exit 1; }
    [[ -f "$UUPD_SIGNING_PRIVATE_KEY_FILE" ]] || { echo "[ERROR] Signing private key file not found: $UUPD_SIGNING_PRIVATE_KEY_FILE"; exit 1; }
  fi
fi

current_branch="$(git -C "$repo_root" branch --show-current 2>/dev/null || true)"
if [[ "$REQUIRE_RELEASE_BRANCH" == "1" && "$current_branch" != "$RELEASE_BRANCH" ]]; then
  echo "[ERROR] You are on branch '$current_branch', but deploy.cfg requires '$RELEASE_BRANCH'."
  echo "        Run: git checkout $RELEASE_BRANCH"
  exit 1
fi

if [[ "$DRY_RUN" == "1" ]]; then
  echo "[DRY RUN] Build will run, but no commit, push, private copy, or GitHub release will be performed."
fi

# =====================================================
# RUN HEADER SCRIPT
# =====================================================
echo "[INFO] Updating/checking plugin headers..."
php "$HEADER_SCRIPT" "$plugin_file"

extract_header() {
  local header="$1"
  grep -m1 -E "^(${header}:|[[:space:]]*\*[[:space:]]*${header}:)" "$plugin_file" \
    | sed -E "s/.*${header}:[[:space:]]*//; s/\r//; s/[[:space:]]+$//" || true
}

requires_at_least="$(extract_header "Requires at least")"
tested_up_to="$(extract_header "Tested up to")"
requires_php="$(extract_header "Requires PHP")"
version="$(extract_header "Version")"

[[ -n "$version" ]] || { echo "[ERROR] Could not extract Version from $plugin_file"; exit 1; }

echo "[INFO] Version: $version"

# =====================================================
# GENERATE STATIC UUPD METADATA
# =====================================================
github_user="${GITHUB_REPO%%/*}"
repo_name="${GITHUB_REPO#*/}"
cdn_path="https://raw.githubusercontent.com/$github_user/$repo_name/$RELEASE_BRANCH/$UUPD_DIR"

mkdir -p "$static_subfolder"

# Signed mode must hash the finished ZIP, so generate ordinary metadata into a
# temporary staging directory first. Unsigned mode writes index.json exactly as before.
metadata_generation_dir="$static_subfolder"
if [[ "$UUPD_SIGNED_UPDATES" == "1" ]]; then
  metadata_generation_dir="$TMPDIR/uupd-metadata-$PLUGIN_SLUG"
  rm -rf "$metadata_generation_dir"
  mkdir -p "$metadata_generation_dir"
  echo "[INFO] Generating staged UUPD metadata for signing..."
else
  echo "[INFO] Generating $UUPD_DIR/index.json for GitHub delivery..."
fi

php "$GENERATOR_SCRIPT" \
  "$plugin_file" \
  "$CHANGELOG_FILE" \
  "$metadata_generation_dir" \
  "$github_user" \
  "$cdn_path" \
  "$repo_name" \
  "$repo_name" \
  "$STATIC_FILE" \
  "$ZIP_NAME"

[[ -f "$metadata_generation_dir/index.json" ]] || {
  echo "[ERROR] Failed to generate $metadata_generation_dir/index.json"
  exit 1
}

if [[ "$UUPD_SIGNED_UPDATES" == "1" ]]; then
  # info.txt contains no secret material and remains useful in the published UUPD folder.
  [[ -f "$metadata_generation_dir/info.txt" ]] && cp -f "$metadata_generation_dir/info.txt" "$static_subfolder/info.txt"
  echo "[OK] Unsigned metadata staged for signing: $metadata_generation_dir/index.json"
else
  echo "[OK] index.json generated: $static_subfolder/index.json"
fi

# =====================================================
# CREATE README.TXT
# =====================================================
if [[ "$SKIP_README_GENERATION" != "1" ]]; then
  echo "[INFO] Generating readme.txt..."

  {
    echo "=== $PLUGIN_NAME ==="
    echo "Contributors: $README_CONTRIBUTORS"
    echo "Donate link: $README_DONATE_LINK"
    echo "Tags: $PLUGIN_TAGS"
    echo "Requires at least: $requires_at_least"
    echo "Tested up to: $tested_up_to"
    echo "Stable tag: $version"
    echo "Requires PHP: $requires_php"
    echo "License: $README_LICENSE"
    echo "License URI: $README_LICENSE_URI"
    echo
  } > "$temp_readme"

  cat "$STATIC_FILE" >> "$temp_readme"
  echo >> "$temp_readme"
  echo "== Changelog ==" >> "$temp_readme"
  cat "$CHANGELOG_FILE" >> "$temp_readme"

  if [[ -f "$readme_file" ]]; then
    cp -f "$readme_file" "$readme_file.bak"
  fi

  mv -f "$temp_readme" "$readme_file"
  echo "[OK] readme.txt generated: $readme_file"
else
  echo "[INFO] Skipping readme.txt generation."
fi

# =====================================================
# BUILD WORDPRESS PLUGIN ZIP
# =====================================================
echo "[INFO] Building plugin zip..."

package_root="$TMPDIR/package-$PLUGIN_SLUG"
package_dir="$package_root/$PLUGIN_SLUG"

rm -rf "$package_root" "$zip_file"
mkdir -p "$package_dir"

if [[ "$REQUIRE_RSYNC" == "1" ]] && ! command -v rsync >/dev/null 2>&1; then
  echo "[ERROR] rsync is required but was not found."
  exit 1
fi

if command -v rsync >/dev/null 2>&1; then
  rsync_args=(-a "$plugin_dir/" "$package_dir/")

  for item in $PACKAGE_EXCLUDES; do
    rsync_args+=(--exclude="$item")
  done

  rsync "${rsync_args[@]}"
else
  echo "[WARN] rsync not found; falling back to cp. Excludes are less precise."

  cp -R "$plugin_dir/." "$package_dir/"

  for item in $PACKAGE_EXCLUDES; do
    rm -rf "$package_dir/$item"
  done
fi

sevenzip_win="${SEVENZIP_PATH:-/c/Program Files/7-Zip/7z.exe}"

if [[ -x "$sevenzip_win" ]]; then
  pushd "$package_root" >/dev/null
  "$sevenzip_win" a -tzip "$zip_file" "$PLUGIN_SLUG" >/dev/null
  popd >/dev/null
elif command -v zip >/dev/null 2>&1; then
  pushd "$package_root" >/dev/null
  zip -qr "$zip_file" "$PLUGIN_SLUG"
  popd >/dev/null
else
  pushd "$package_root" >/dev/null
  tar -a -c -f "$zip_file" "$PLUGIN_SLUG"
  popd >/dev/null
fi

[[ -f "$zip_file" ]] || {
  echo "[ERROR] Failed to create archive: $zip_file"
  exit 1
}

echo "[OK] Zipped to: $zip_file"

# =====================================================
# ZIP SAFETY CHECKS
# =====================================================
echo "[INFO] Checking zip contents..."

if command -v unzip >/dev/null 2>&1; then
  for item in $ZIP_FORBIDDEN_PATHS; do
    clean_item="${item#/}"

    if [[ "$clean_item" == */ ]]; then
      pattern="/${clean_item}"
    else
      pattern="/${clean_item}$"
    fi

    if unzip -l "$zip_file" | grep -qE "$pattern"; then
      echo "[ERROR] Zip contains forbidden path: $item"
      exit 1
    fi
  done
else
  echo "[WARN] unzip not found; skipping zip content checks."
fi

echo "[OK] Zip checks passed."

# =====================================================
# UUPD 2.1 SIGNED UPDATE METADATA
# =====================================================
if [[ "$UUPD_SIGNED_UPDATES" == "1" ]]; then
  echo "[INFO] Signing UUPD metadata and binding it to the finished ZIP..."

  staged_metadata="$metadata_generation_dir/index.json"
  signed_output="$static_subfolder/index.json"
  signer_args=(
    --metadata="$staged_metadata"
    --package="$zip_file"
    --output="$signed_output"
  )

  if [[ -n "$UUPD_SIGNING_KEY_ID" ]]; then
    signer_args+=(--key-id="$UUPD_SIGNING_KEY_ID")
  fi

  case "${UUPD_SIGNING_KEY_SOURCE,,}" in
    prompt)
      echo "[INFO] Private signing key will be read interactively and will not be written to disk."
      IFS= read -r -s -p "UUPD Ed25519 private key (base64): " uupd_prompt_private_key
      echo
      [[ -n "$uupd_prompt_private_key" ]] || { echo "[ERROR] No signing key entered."; exit 1; }
      printf '%s' "$uupd_prompt_private_key" | php "$UUPD_SIGNER_SCRIPT" "${signer_args[@]}" --private-key-stdin
      unset uupd_prompt_private_key
      ;;
    config)
      # Explicit opt-in: this mode stores the private key in deploy.cfg.
      printf '%s' "$UUPD_SIGNING_PRIVATE_KEY" | php "$UUPD_SIGNER_SCRIPT" "${signer_args[@]}" --private-key-stdin
      ;;
    file)
      php "$UUPD_SIGNER_SCRIPT" "${signer_args[@]}" --private-key-file="$UUPD_SIGNING_PRIVATE_KEY_FILE"
      ;;
  esac

  [[ -f "$signed_output" ]] || { echo "[ERROR] Signed index.json was not created."; exit 1; }
  echo "[OK] Signed UUPD index generated: $signed_output"
fi

# =====================================================
# GIT COMMIT AND PUSH RELEASE BRANCH
# =====================================================
pushd "$repo_root" >/dev/null
git add -A

if git diff --cached --quiet; then
  echo "[INFO] No repo changes to commit."
else
  if [[ "$DRY_RUN" == "1" ]]; then
    echo "[DRY RUN] Would commit and push these files:"
    git diff --cached --name-status
  else
    git commit -m "Version $version Release"
    git push origin "$RELEASE_BRANCH"
    echo "[OK] Git commit and push complete: $RELEASE_BRANCH"
  fi
fi

popd >/dev/null

if [[ "$DRY_RUN" == "1" ]]; then
  echo
  echo "[DRY RUN] Complete. Built zip: $zip_file"
  echo "[DRY RUN] Review $UUPD_DIR/index.json and the zip contents before setting DRY_RUN=0."
  exit 0
fi

# =====================================================
# DEPLOY LOGIC
# =====================================================
if [[ "${DEPLOY_TARGET,,}" == "private" ]]; then
  [[ -n "${DEST_DIR:-}" ]] || {
    echo "[ERROR] DEST_DIR is not set for private deploy."
    exit 1
  }

  mkdir -p "$DEST_DIR"
  cp -f "$zip_file" "$DEST_DIR/"
  echo "[OK] Copied to $DEST_DIR"
else
  echo "[INFO] Deploying to GitHub release..."

  if [[ -z "${GITHUB_TOKEN:-}" && -f "$TOKEN_FILE" ]]; then
    GITHUB_TOKEN="$(tr -d '\r\n' < "$TOKEN_FILE")"
  fi

  [[ -n "${GITHUB_TOKEN:-}" ]] || {
    echo "[ERROR] GITHUB_TOKEN not available."
    exit 1
  }

  release_tag="v$version"
  body_file="$(mktemp)"
  response_file="$TMPDIR/github_release_response.json"

  changelog_body="$(sed ':a;N;$!ba;s/\r//g' "$CHANGELOG_FILE" \
    | sed 's/\\/\\\\/g; s/"/\\"/g; s/$/\\n/' \
    | tr -d '\n')"

  cat > "$body_file" <<JSON
{
  "tag_name": "$release_tag",
  "target_commitish": "$RELEASE_BRANCH",
  "name": "$version",
  "body": "$changelog_body",
  "draft": false,
  "prerelease": false
}
JSON

  status="$(curl -sS -o "$response_file" -w "%{http_code}" \
    -H "Authorization: token $GITHUB_TOKEN" \
    -H "Accept: application/vnd.github+json" \
    "https://api.github.com/repos/$GITHUB_REPO/releases/tags/$release_tag" || true)"

  release_id=""

  if [[ "$status" == "200" ]]; then
    release_id="$(grep -m1 -E '"id":[[:space:]]*[0-9]+' "$response_file" | head -1 | sed -E 's/.*"id":[[:space:]]*([0-9]+).*/\1/')"

    echo "[INFO] Release exists. Updating body (id=$release_id)..."

    curl -sS -X PATCH "https://api.github.com/repos/$GITHUB_REPO/releases/$release_id" \
      -H "Authorization: token $GITHUB_TOKEN" \
      -H "Accept: application/vnd.github+json" \
      -H "Content-Type: application/json" \
      --data-binary "@$body_file" >/dev/null
  else
    echo "[INFO] Creating new release..."

    curl -sS -X POST "https://api.github.com/repos/$GITHUB_REPO/releases" \
      -H "Authorization: token $GITHUB_TOKEN" \
      -H "Accept: application/vnd.github+json" \
      -H "Content-Type: application/json" \
      --data-binary "@$body_file" > "$response_file"

    release_id="$(grep -m1 -E '"id":[[:space:]]*[0-9]+' "$response_file" | head -1 | sed -E 's/.*"id":[[:space:]]*([0-9]+).*/\1/')"
  fi

  if [[ -z "$release_id" ]]; then
    echo "[ERROR] Could not determine release ID. Response was:"
    cat "$response_file" || true
    exit 1
  fi

  echo "[OK] Using Release ID: $release_id"

  asset_name="$(basename "$zip_file")"
  assets_file="$TMPDIR/github_assets_response.json"

  curl -sS \
    -H "Authorization: token $GITHUB_TOKEN" \
    -H "Accept: application/vnd.github+json" \
    "https://api.github.com/repos/$GITHUB_REPO/releases/$release_id/assets" > "$assets_file"

  existing_asset_id="$(grep -B2 -A4 -F '"name": "'"$asset_name"'"' "$assets_file" \
    | grep -m1 -E '"id":[[:space:]]*[0-9]+' \
    | sed -E 's/.*"id":[[:space:]]*([0-9]+).*/\1/' || true)"

  if [[ -n "$existing_asset_id" ]]; then
    echo "[INFO] Removing existing asset: $asset_name ($existing_asset_id)"

    curl -sS -X DELETE "https://api.github.com/repos/$GITHUB_REPO/releases/assets/$existing_asset_id" \
      -H "Authorization: token $GITHUB_TOKEN" \
      -H "Accept: application/vnd.github+json" >/dev/null
  fi

  echo "[INFO] Uploading asset: $asset_name"

  curl -sS -X POST "https://uploads.github.com/repos/$GITHUB_REPO/releases/$release_id/assets?name=$asset_name" \
    -H "Authorization: token $GITHUB_TOKEN" \
    -H "Accept: application/vnd.github+json" \
    -H "Content-Type: application/zip" \
    --data-binary @"$zip_file" >/dev/null

  rm -f "$body_file"
fi

echo
echo "[OK] Deployment complete: $DEPLOY_TARGET"
