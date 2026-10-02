# ADR 0003: Evidence tagging and data tiers

- Status: accepted
- Date: 2026-10-02

## Evidence tags

Every factual claim in documentation carries one of:

- `[verified in code]`: read from Moodle or plugin source, with a file and line where possible
- `[not run]`: describes intended behaviour that has not been executed
- *hypothesis*: an analysis not yet reproduced

No document may say "tested" for anything that has not been executed. This is checked in review.

## Data tiers

| Tier | Meaning | Rule |
|---|---|---|
| A | Course configuration (no personal data) | Collected |
| B | Aggregate counts | Collected only as counts |
| C | Learner or personal data | Never read by rule code |
| P | Plugin-owned `local_releasegate_*` tables | Pseudonymous references only |

Details: `docs/research/data-scope-and-collection.md`.
