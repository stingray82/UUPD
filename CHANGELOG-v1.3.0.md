[1.3.0] – 2025-07-27
--------------------

### Added

-   **Prerelease Support**

    -   New config option: `'allow_prerelease' => true` — opt-in support for
        prerelease versions such as `1.2.0-beta`, `1.3.0-rc.1`, etc.

    -   Regex-based detection of SemVer prerelease formats.

    -   Filter: `uupd/allow_prerelease` — allows dynamic prerelease logic by
        slug.

-   **Scoped Filter System**

    -   All key filters now support **per-slug overrides**, e.g.:

        ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ php
        apply_filters( "uupd/server_url/{$slug}", $url, $slug );
        ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

        This allows **isolation of updater behavior per plugin/theme**, avoiding
        global collisions in multi-updater environments.

-   **New Filters**

    -   `uupd/filter_config` — Modify entire config array before registration.

    -   `uupd/server_url` — Alter or inject the base updater server URL.

    -   `uupd/server_url/{$slug}` — Slug-scoped override of the above.

    -   `uupd/remote_url` — Fully override the constructed metadata fetch URL.

    -   `uupd/metadata_result` — Modify or sanitize decoded metadata before
        caching.

    -   `uupd/allow_prerelease` — Enable prerelease by slug, constant, or
        condition.

    -   `uupd/log` — Hook into updater debug logs.

    -   `uupd_metadata_fetch_failed` — Triggered on metadata fetch failures.

    -   `uupd_fetch_remote_error_ttl` — Control transient expiry on error
        fallback.

    -   `uupd/before_fetch_remote` — Triggered before any metadata fetch starts.

 

### Improved

 

-   **Safe Fallback Resolution**

    -   Filters now prefer slug-scoped return values and fallback gracefully to
        global values.

    -   Example:

        ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ php
        $scoped = apply_filters("uupd/server_url/{$slug}", null, $slug);
        return $scoped ?? $url;
        ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

 

-   **Stable Beta Handling**

    -   All prerelease detection is now handled by the core config logic
        (constructor), ensuring consistent plugin vs. theme behavior.

     

-   **Developer Ergonomics**

    -   Logging and debug output now mention the filter or hook being applied.

    -   Priorities can now be overridden externally by changing when
        `register()` is called.

     

-   **Improved Compatibility**

    -   Compatible with legacy `UUPD_Updater_V1` configs and existing
        integration patterns.

    -   Works with rescoped forks (e.g. `RUP\Updater\Updater_V1`) using the same
        interface.

     

### Stability & Security

-   Input sanitization for slugs in filters and actions.

-   Filter values default to conservative fallbacks if overridden incorrectly.

-   Transient caching for failed requests prevents spamming the remote server.

 

### Documentation & Examples

-   **All integration guides updated:**

    -   Plugin and theme integration now show:

        -   Adjustable hook priorities

        -   Per-plugin overrides

        -   Admin toggles for prerelease control

         

-   **New files added:**

    -   `UUPD-Filters.md` includes scoped filters, override design, and filter
        safety.

    -   Example snippet file for overriding updater URL by suffix like
        `-rupninja`.

     

### 🧠 Developer Notes

-   This is a **backward-compatible** but **feature-rich** release for advanced
    users managing multiple updaters in parallel.

-   Designed for tools like **Ninja Updater**, forked plugin managers, and
    agency frameworks where update rules need to be **isolated**.

-   You can now use `plugins_loaded` or `after_setup_theme` with a **lower
    priority** (e.g. `5`) to allow external filters to override updater behavior
    **before registration**. *Ideal for pre-deploy testing with custom update
    severs*
