# ADR 0001: Native collector, Report Builder as the access-control and presentation standard

- Status: accepted (design); implementation `[not run]`
- Date: 2026-10-02

## Context

The plugin must read course configuration out of Moodle and show results to people with
different roles, in an enterprise setting where "who can see what, and why" is the main review
question. Options for getting data out: Moodle web services (REST), Report Builder, or code
running inside Moodle.

## Decision

1. **Collection** runs inside Moodle (native PHP), using Moodle APIs first and reading only the
   approved configuration tables. Raw data does not cross the Moodle boundary.
2. **Presentation and access control** use Report Builder system reports, so access, filtering,
   scheduling and export follow Moodle's own mechanisms.
3. **Web services** are not used to collect data. Several facts the rules need (for example
   `quiz_slots`, `scorm_scoes.launch`, availability JSON) have no stable endpoint
   `[verified in code]`, and a token with broad capability would widen the review scope.

## Consequences

- Report-level access is built in; **row-level scoping is not automatic** and must be written in
  each report query `[verified in code]` (see `course_results.php`, `site_overview.php`).
- Raw SQL against `mdl_*` tables is not public API; it needs per-version care and CI on every
  supported branch.
- No external service, token or inbound port is needed for the core product.

Details and evidence: `docs/research/data-scope-and-collection.md`.
