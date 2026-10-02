# Changelog

All notable changes to `local_releasegate` are recorded here.

Format: [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).
Versioning: [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

Audience: developers and reviewers. Every item is tagged `[verified in code]` (present in
source) or `[not run]` (not executed). Nothing here has been tested on a Moodle site.

## [Unreleased]

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
- Course page no longer selects `*` from `local_rg_run`; only allowlisted columns are read
  `[verified in code]`.

### Known limitations

- `results_exported` and `settings_changed` are defined but not triggered `[verified in code]`.
- Scan and verify use GET with `sesskey` `[verified in code]`.
- Large `IN (...)` list for users with the capability in very many courses `[verified in code]`.
- No external anchoring of the audit chain; no retention/erase flow `[verified in code]`.
- Nothing has been executed or tested `[not run]`.
