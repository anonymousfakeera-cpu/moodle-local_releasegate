# Release Gate — Admin Guide

Purpose: how to grant access, where each screen lives, and how scanning runs.

Audience: Moodle site administrators and managers.

Every factual claim is tagged `[verified in code]` (read from source) or `[not run]` (not
executed). Nothing here has been tested on a Moodle site.

## Capability matrix

Source: `db/access.php` `[verified in code]`. Default roles are the archetype defaults; a
fresh install grants nothing beyond these.

| Capability | Context | Risk | Default roles | What it allows |
|---|---|---|---|---|
| `local/releasegate:view` | Course | none | manager, editingteacher | See the course gate page and its verdict |
| `local/releasegate:viewresults` | Course | none | manager, editingteacher | See the rule results report |
| `local/releasegate:viewevidence` | Course | none | manager | Open the evidence detail panel |
| `local/releasegate:run` | Course | none | manager | Run a scan from the course page |
| `local/releasegate:export` | Course | none | manager | Download a results report |
| `local/releasegate:viewaudit` | System | none | manager | See the audit log and verify the chain |
| `local/releasegate:managesettings` | Course | Config (`RISK_CONFIG`) | manager | Change plugin settings |
| `local/releasegate:waive` | Course | none | manager | Waive a rule |
| `local/releasegate:approve` | Course | none | manager | Approve a course release |
| `local/releasegate:viewaccessreview` | System | none | manager | See the access review report |
| `local/releasegate:viewaccessusers` | System | Personal (`RISK_PERSONAL`) | manager | See effective user counts in access review |

Notes:
- Waiving and approving are separate capabilities; the requester can never be the approver
  is a product rule, not enforced by a capability `[verified in code]`.
- `managesettings` and `waive`/`approve` do not have a screen yet `[verified in code]`.

## How to grant access

Everything uses standard Moodle roles and capabilities. There is no second permission system.

1. **By role** — Site administration > Users > Permissions > Define roles. Add the capability
   to any role. This is the recommended route `[not run]`.
2. **By category or course** — on the relevant context, use Permissions or a role override to
   allow or prohibit a capability for a role. Capabilities are defined for course context, so
   they can be set at category and course level `[verified in code]`.
3. **By cohort** — assign the role (or a copy of it) to the cohort at the category or course
   context. Core cohort role assignment handles the propagation; the plugin adds nothing
   `[not run]`.

To audit who holds what, open **Access review** (below) and use the "Check permissions" link
on each row, which goes to core `/admin/roles/check.php?contextid=...` `[verified in code]`.

## Where each screen lives

| Screen | URL | Capability |
|---|---|---|
| Course gate | `/local/releasegate/course.php?id=<courseid>` | `view` |
| Site overview | `/local/releasegate/index.php` | `view` in at least one course |
| Access review | `/local/releasegate/accessreview.php` | `viewaccessreview` |
| Trust sheet | `/local/releasegate/trust.php` | `viewaccessreview` |
| Audit log + verify | `/local/releasegate/audit.php` | `viewaudit` |

The site overview lists only courses where the viewer holds `view` `[verified in code]`. The
access review lists capability assignments; user counts are hidden unless the viewer holds
`viewaccessusers` `[verified in code]`.

## Scanning

- The nightly sweep task `local_releasegate\task\sweep` runs at 02:17 site time
  `[verified in code]`. Change it in Site administration > Server > Scheduled tasks `[not run]`.
- A user with `run` can scan one course from the course page `[verified in code]`.
- A scan writes one `local_rg_run` row, one `local_rg_result` row per rule, and one
  hash-chained `local_rg_audit` row, in a transaction `[verified in code]`.
- Only one scan per course runs at a time (lock); a second caller fails with a lock error
  `[verified in code]`.
- The gate health check appears in Site administration > Reports > Status (`cron_health`)
  `[verified in code]`.

## Troubleshooting

| Symptom | Likely cause | What to check |
|---|---|---|
| No "Release gate" course link | Missing `view` in that course | Define roles / overrides |
| Course page shows "No scan has been run" | No run row yet | Run scan, or check the nightly task |
| Results report missing | Missing `viewresults` | Capability in the course |
| Evidence panel missing | Missing `viewevidence` (manager-only) | Capability in the course |
| Site overview is empty | Viewer has `view` in no course | Access review |
| Download button absent | Missing `export` | Capability in the course |
| Audit verify says not verified | Chain changed outside the app | Investigate who can write the DB |
| Cron check reports stale | Scheduled tasks not running | Site cron |

## Known limitations

- No settings form exists yet, so `managesettings` and `settings_changed` are unused
  `[verified in code]`.
- Scan and verify are GET actions with a session key, not POST `[verified in code]`.
- The audit chain is tamper-evident against application users only, not a database
  administrator `[verified in code]`.
- Nothing has been executed or tested `[not run]`.
