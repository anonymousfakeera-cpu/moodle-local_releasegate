# ADR 0002: Compatibility policy

- Status: accepted; CI `[not run]`
- Date: 2026-10-02

| Item | Decision |
|---|---|
| Moodle branches | 4.5 LTS, 5.0, 5.1, 5.3 LTS (5.3 scheduled for release on 2026-10-05) |
| Minimum | `$plugin->requires = 2024100700` (Moodle 4.5.0) |
| PHP | PHP 8.1 syntax floor (4.5). 8.3 is the only version valid on all four branches |
| Database | MySQL first; MariaDB and PostgreSQL prepared as disabled CI jobs |
| API rule | Use only APIs present on every supported branch; check against the 4.5 branch first |

Evidence: the 4.5 branch `lib/classes/lang_string.php` is `namespace core;` and
`lib/classes/exception/required_capability_exception.php` is `namespace core\exception;`
`[verified in code]`. PHP ranges and the 5.3 date come from Moodle documentation and
announcements `[verified from public documentation, not from the Moodle release page itself]`.
