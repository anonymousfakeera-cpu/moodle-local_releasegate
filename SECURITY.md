# Security policy

## Status

This project is **alpha (0.1.0)** and has **not been run on a Moodle site yet**. There are no
supported releases and no security guarantees. Do not install it on a production site.

## Reporting a vulnerability

Please do not open a public issue for a security problem. Use GitHub's
"Report a vulnerability" (private vulnerability reporting) on this repository once it is
enabled, or contact the maintainer through the GitHub profile.

Please include the Moodle version, database, plugin version, steps to reproduce and the
impact. **Do not include personal data, real course content or credentials.**

## Scope notes for reviewers

- The plugin is designed to read course configuration only and to write only its own
  `local_releasegate_*` tables. See `docs/security-and-data.md` for what is read, what is written and
  what is never read.
- Access control uses Moodle capabilities; there is no separate permission system.
- No response-time commitment is made at this stage.
