# Release Gate — Verification Checklist

Purpose: one place for every manual verification step from every milestone, to be run on a
Moodle 5.1 stage site.

Audience: the owner and any tester.

Status legend: every row is **Not run**. This repository has no PHP and no Moodle install, so
nothing here has been executed. Change a row to Pass or Fail only after running it on a stage
site.

## Step 0 — fixes

| # | Step | Expected result | Status |
|---|---|---|---|
| 0.1 | Open Define roles, inspect `viewevidence` | Manager archetype only | Not run |
| 0.2 | Inspect `viewresults` | editingteacher and manager allowed | Not run |
| 0.3 | Check `version.php` | `$plugin->version` = `2026100200` | Not run |
| 0.4 | Open Settings > Plugins overview | Plugin listed as upgraded, no error | Not run |

## M1 — capabilities, events, strings

| # | Step | Expected result | Status |
|---|---|---|---|
| 1.1 | Upgrade the plugin | All 11 capabilities appear in Define roles | Not run |
| 1.2 | Inspect capability risks | `managesettings` = Config; `viewaccessusers` = Personal; rest none | Not run |
| 1.3 | Check learner/teacher defaults | No Release Gate capability by default for learner | Not run |
| 1.4 | View a course gate as manager | `gate_viewed` event appears in the log store | Not run |

## M2 — entities

| # | Step | Expected result | Status |
|---|---|---|---|
| 2.1 | Add the course results report to a custom report source | Run/result columns available, limited to the allowlist | Not run |
| 2.2 | Inspect result rule column | Shows localised rule title, not a bare id | Not run |

## M3 — course results report

| # | Step | Expected result | Status |
|---|---|---|---|
| 3.1 | Open the course gate after a scan | Rule results render as a system report with filters severity/status/area | Not run |
| 3.2 | Remove `export` from a role | Download button disappears | Not run |
| 3.3 | Request the report with another course's `runid` | No rows from the other course | Not run |
| 3.4 | Course with no scan | Empty-state notice shows | Not run |

## M4 — site overview

| # | Step | Expected result | Status |
|---|---|---|---|
| 4.1 | Teacher with `view` in course A only, open `/local/releasegate/index.php` | Only course A listed | Not run |
| 4.2 | Request another course's data directly | No rows | Not run |
| 4.3 | User with no `view` anywhere | Permission error, no report | Not run |
| 4.4 | Course never scanned | Row shows "Not scanned" and still links to the course gate | Not run |
| 4.5 | Filter by verdict "Not scanned" | Never-scanned courses returned | Not run |
| 4.6 | Filter by category | Rows limited to that category | Not run |

## M5 — course page

| # | Step | Expected result | Status |
|---|---|---|---|
| 5.1 | Open the gate with a scan | Banner shows verdict, coverage, ruleset version, fingerprint | Not run |
| 5.2 | Open the gate without a scan | Notice plus (if allowed) the Run scan button | Not run |
| 5.3 | As editingteacher | Results report visible, evidence panel absent | Not run |
| 5.4 | As manager, click "Show evidence" | Panel opens; `evidence_viewed` event appears | Not run |
| 5.5 | Force an ERROR result (if reproducible) | Message shows the generic string, not exception text | Not run |
| 5.6 | Click "Run scan now" | New run stored, page reloads | Not run |

## M6 — access review

| # | Step | Expected result | Status |
|---|---|---|---|
| 6.1 | Open `/local/releasegate/accessreview.php` as manager | Rows per capability and context with permission | Not run |
| 6.2 | Without `viewaccessusers` | "Effective users" column hidden | Not run |
| 6.3 | With `viewaccessusers` | Effective user counts shown | Not run |
| 6.4 | Click "Check permissions" | Core `admin/roles/check.php` opens for that context | Not run |
| 6.5 | Filter by capability, context level, permission | Rows filtered accordingly | Not run |

## M7 — trust sheet

| # | Step | Expected result | Status |
|---|---|---|---|
| 7.1 | Open `/local/releasegate/trust.php` | Shows read tables, denylist, default access, events | Not run |
| 7.2 | Compare the read table against this doc and the rules | Lists match | Not run |
| 7.3 | Confirms no claim of testing or certification | Page states facts only | Not run |

## M8 — audit

| # | Step | Expected result | Status |
|---|---|---|---|
| 8.1 | Open `/local/releasegate/audit.php` as manager | Audit rows with localised action, course, time | Not run |
| 8.2 | Inspect columns | No `prevhash`, `hash` or `actorref` shown | Not run |
| 8.3 | Click "Verify chain" on an untouched site | "Audit chain is intact" | Not run |
| 8.4 | Manually alter one audit row in the DB, then verify | "Could not be verified" message, no raw error | Not run |
| 8.5 | Without `viewaudit` | Permission error | Not run |

## Cross-cutting

| # | Step | Expected result | Status |
|---|---|---|---|
| X.1 | Confirm no file outside `releasegate/` changed | `git status` clean outside the plugin | Not run |
| X.2 | Run `php -l` and phpcs on a machine with PHP | No syntax or style errors | Not run |
| X.3 | Confirm no table outside `local_rg_*` is written during a scan | DB audit shows no other writes | Not run |
