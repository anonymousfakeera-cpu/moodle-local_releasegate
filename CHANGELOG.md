# Changelog

All notable changes to `local_releasegate` are recorded here.

Format: [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).
Versioning: [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

Audience: developers and reviewers. Every item is tagged `[verified in code]` (present in
source), `[verified by CI]` (observed in a GitHub Actions run, with date) or `[not run]`
(not executed). Nothing has been run on a real Moodle site.

## [Unreleased]

### Verified by CI (run 3, 2026-10-02, MySQL — all 8 jobs passed)

- All 8 matrix jobs (Moodle 4.5/5.0/5.1/main × PHP 8.1–8.4) passed PHPUnit
  (`22 tests` on access-scope suite + original gate tests) `[verified by CI 2026-10-02]`.
- Report Builder column callback TypeError fix confirmed on MySQL `[verified by CI 2026-10-02]`.
- `database::generate_param_name()` IN-clause fix for site_overview confirmed `[verified by CI 2026-10-02]`.

### Verified by CI (first run, 2026-10-02, MySQL)

- Moodle 4.5 with PHP 8.1 and 8.3: install, PHP lint, PHPDoc, Mustache lint, Grunt, upgrade
  savepoints and PHPUnit (`OK (15 tests, 44 assertions)`) passed `[verified by CI]`.
- The same run failed: Moodle Code Checker (lang string order), `validate` (table prefix),
  PHPUnit on Moodle 5.0 and 5.1 (test helper `status()` overrides a final PHPUnit method), and
  all jobs for `MOODLE_503_STABLE` (the upstream branch does not exist yet)
  `[verified by CI]`. The fixes are listed under Changed. Whether they pass is `[not run]`
  until the next CI run.

### Changed

- Database tables renamed from `local_rg_*` to `local_releasegate_*`, as required by
  `moodle-plugin-ci validate` `[verified by CI]`. No site has ever installed the old names.
- Language strings sorted alphabetically (`strcmp` order), as required by the Moodle code
  checker `[verified by CI]`.
- Test helper `status()` renamed to `rule_status()` to avoid overriding a final PHPUnit
  method `[verified by CI]`.
- CI matrix: Moodle 5.3 jobs now use `main` and are experimental, because
  `MOODLE_503_STABLE` does not exist upstream yet `[verified by CI]`.

### Fixed

- Four Report Builder column callbacks (`site_overview`, `access_review`, `audit`, `role_capability`)
  type-hinted `?int`, but MySQL returns strings and Report Builder calls with strict types, so
  every row would have thrown a `TypeError`. Found by the access-scope tests in CI
  `[verified by CI 2026-10-02]`; the hints are removed and values cast inside
  `[verified by CI 2026-10-02]`.
- `site_overview` built its course restriction with `$DB->get_in_or_equal()`, whose parameter
  names Report Builder rejects (`Invalid parameter names`), so the site overview would have
  thrown for any user with an allowed course. Found by the new access-scope tests in CI
  `[verified by CI 2026-10-02]`; now uses `database::generate_param_name()`
  `[verified by CI 2026-10-02]`.
- Access-scope tests reset the Report Builder instance cache between reads (it is keyed by
  report id and user, not by parameters) and expect the fail-closed exception when the report
  is created `[verified by CI 2026-10-02]`.

### Added

- Capabilities in `db/access.php`: `view`, `viewresults`, `viewevidence`, `run`, `export`,
  `viewaudit`, `managesettings`, `waive`, `approve`, `viewaccessreview`, `viewaccessusers`
  `[verified in code]`.
- Access-log events in `classes/event/`: `gate_viewed`, `evidence_viewed`,
  `results_exported`, `settings_changed` `[verified in code]`.
- Report Builder entities: `run`, `result`, `role_capability`, `audit`, each exposing an
  explicit column allowlist `[verified in code]`.
- System reports: `course_results`, `site_overview`, `access_review`, `audit_log`
  `[verified in code]`.
- Course page `course.php` with Mustache template `templates/course_page.mustache`
  `[verified in code]`.
- Site overview page `index.php` `[verified in code]`.
- Access review page `accessreview.php` `[verified in code]`.
- Trust sheet page `trust.php` and template `templates/trust_sheet.mustache`
  `[verified in code]`.
- Audit page `audit.php` with a "Verify chain" action `[verified in code]`.
- Documentation: `README.md`, `CHANGELOG.md`, `docs/admin-guide.md`,
  `docs/security-and-data.md`, `docs/verification.md` `[verified in code]`.

### Changed

- `version.php`: `$plugin->version` raised to `2026100200` `[verified in code]`.
- `viewaudit` moved to `CONTEXT_SYSTEM` (the audit chain is site-wide) `[verified in code]`.
- Site overview uses a `LEFT JOIN` so never-scanned courses appear with a "Not scanned"
  verdict `[verified in code]`.
- Rule-result messages for `ERROR` rows are replaced with a generic localised string in the
  UI; raw exception text is never shown `[verified in code]`.

### Fixed

- Site overview now adds `1 = 0` when the viewer has no allowed courses, instead of adding no
  condition `[verified in code]`.
- Course page no longer selects `*` from `local_releasegate_run`; only allowlisted columns are read
  `[verified in code]`.

### Known limitations

- `results_exported` and `settings_changed` are defined but not triggered `[verified in code]`.
- Scan and verify use GET with `sesskey` `[verified in code]`.
- Large `IN (...)` list for users with the capability in very many courses `[verified in code]`.
- No external anchoring of the audit chain; no retention/erase flow `[verified in code]`.
- Nothing has been executed or tested `[not run]`.
