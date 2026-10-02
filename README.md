# Release Gate (`local_releasegate`)

[![Moodle CI](https://github.com/anonymousfakeera-cpu/moodle-local_releasegate/actions/workflows/moodle-ci.yml/badge.svg)](https://github.com/anonymousfakeera-cpu/moodle-local_releasegate/actions/workflows/moodle-ci.yml)

Purpose: stage-to-live readiness gate for Moodle courses. It reads course configuration,
applies deterministic rules, and records a verdict (READY / CONDITIONAL / BLOCKED /
INSUFFICIENT DATA). AI is not part of the product and everything works with AI absent.

Audience: Moodle administrators, plugin developers and security reviewers.

This file is a map, not the full design. The design documents live one level up in the
repository (`../01-...` to `../07-...`). Do not duplicate them here.

## Status

- **CI green** (2026-10-02): all 8 matrix jobs (Moodle 4.5/5.0/5.1/main × PHP 8.1–8.4)
  pass PHPUnit, PHP lint, Code Checker, and validate. 22 tests including access-scope suite.
- Written: capabilities, events, Report Builder entities and system reports, course page,
  site overview, access review, trust sheet, audit report and chain verification.
- Not run on a live site: no site deployment yet. All site-level behaviour is tagged
  `[verified in code]` (read from source) or `[verified by CI]` (observed in GitHub Actions).

## Requirements

- Supported Moodle branches (owner decision): 4.5 LTS, 5.0, 5.1 and 5.3 LTS (scheduled for
  release on 5 October 2026). `version.php` declares `$plugin->requires = 2024100700`
  (Moodle 4.5.0) `[verified in code]`. Development reference is the 5.1.5 source mirror;
  no branch has been run `[not run]`.
- PHP: the only version valid for all four branches is 8.3 (4.5: 8.1 to 8.3, 5.0 and 5.1:
  8.2 to 8.4, 5.3: 8.3 and 8.4). Code is written for the PHP 8.1 floor `[verified in code:
  no PHP 8.2+ only syntax found by search]`.
- Database: MySQL first. MariaDB and PostgreSQL are prepared in the CI file but not enabled
  `[not run]`.
- Report Builder must be enabled (it is part of standard Moodle).
- No PHP extension beyond core Moodle requirements `[not run]`.

## Install

1. Copy the plugin to `<moodledir>/local/releasegate`.
2. Visit Site administration > Notifications, or run the CLI upgrade `[not run]`.
3. The upgrade creates `local_releasegate_run`, `local_releasegate_result`, `local_releasegate_audit` and installs the
   capabilities from `db/access.php` `[verified in code]`.

## Upgrade

- Bump `$plugin->version` in `version.php` and run the Moodle upgrade `[not run]`.
- Capability changes take effect on the next request after upgrade `[not run]`.

## Uninstall

- Remove via Site administration > Plugins > Plugins overview `[not run]`.
- This drops the three `local_releasegate_*` tables and the plugin capabilities. No core table is
  written by the plugin `[verified in code]`.

## Where things are

| Concern | Location |
|---|---|
| Capabilities | `db/access.php` |
| Tables | `db/install.xml` |
| Course page | `course.php`, `templates/course_page.mustache` |
| Site overview | `index.php`, `classes/reportbuilder/local/systemreports/site_overview.php` |
| Access review | `accessreview.php`, `.../systemreports/access_review.php` |
| Trust sheet | `trust.php`, `templates/trust_sheet.mustache` |
| Audit + verify | `audit.php`, `.../systemreports/audit_log.php` |
| Access-log events | `classes/event/` |
| Rule engine | `classes/local/` |

## Documentation

- [Admin guide](docs/admin-guide.md) — capability matrix, how to grant access, report locations.
- [Security and data](docs/security-and-data.md) — tables and columns, events, limitations.
- [Verification](docs/verification.md) — every manual checklist, all marked "Not run".
- [Changelog](CHANGELOG.md).

## Known limitations

- Nothing has been run or tested on a Moodle site `[not run]`.
- `results_exported` and `settings_changed` event classes exist but are not triggered yet
  `[verified in code]`. Downloads are handled by core `/reportbuilder/download.php`, which
  offers no plugin hook; there is no settings form yet.
- The site overview builds an `IN (...)` list from `get_user_capability_course()`. A user who
  holds the capability in thousands of courses produces a very large query `[verified in code]`.
- Scan and verify run over GET with a session key (`sesskey`), not POST `[verified in code]`.
- The hash chain is tamper-evident against application users only; a database administrator
  can rewrite it. External anchoring is not built `[verified in code]`.
- The privacy provider declares the audit table only; retention and erase flow are not built
  `[verified in code]`.
- The audit verify action reports only "intact" or "not verified"; it never exposes hashes
  `[verified in code]`.

## License

GPL v3 or later.

## Documentation map

| Document | Purpose |
|---|---|
| [docs/admin-guide.md](docs/admin-guide.md) | Capability matrix, who can see what, report locations |
| [docs/security-and-data.md](docs/security-and-data.md) | What is read, what is written, events, known limitations |
| [docs/verification.md](docs/verification.md) | Manual checklists, all "Not run" until executed |
| [docs/adr/](docs/adr/) | Architecture decision records (collection and access model, compatibility, evidence tagging) |
| [docs/research/](docs/research/) | Research notes: data scope and collection, and the catalogue of Moodle course settings that break courses in production (with file:line evidence) |
| [CONTRIBUTING.md](CONTRIBUTING.md), [SECURITY.md](SECURITY.md), [CITATION.cff](CITATION.cff) | Contribution rules, vulnerability reporting, how to cite |

## Lineage

This plugin is the native successor to the Python prototype
[moodle-course-release-qa](https://github.com/anonymousfakeera-cpu/moodle-course-release-qa),
which used simulated QA data and a read-only Moodle token. The plugin replaces simulated data
with deterministic checks that run inside Moodle.

## License

GNU GPL v3 or later (see [LICENSE](LICENSE)). Moodle plugins must be GPL-compatible; the
prototype repository is MIT licensed and separate.
