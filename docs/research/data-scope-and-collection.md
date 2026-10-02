> **Research note.** Claims are tagged by evidence level: *verified in code* (read from Moodle or plugin source, with file:line), *not run* (not executed), or *hypothesis*. References to "docs 01 to 04" or "the owner" point to internal planning material that is not part of this repository.

# 05 — Data Scope and Collection Blueprint

Status: draft for review. Owner decisions are marked **[DECIDE]**. Claims marked *(verified)* come from reading the current plugin code; claims marked *(proposed)* are design intent and have not been checked against a real Moodle 5.1 schema.

## 1. Position

Release Gate is an **evidence engine, not an AI product**. Deterministic rules read Moodle configuration and decide the verdict. AI is optional, off by default, and only ever explains a result that rules already produced. If the AI layer were deleted, the product would still be complete.

The enterprise goal sets one hard constraint on everything below: **the buyer's data stays inside the buyer's Moodle, and the product can prove it.**

## 2. How data gets out of `mdl_*`: three options, one decision

| Option | What it is | Verdict |
|---|---|---|
| **A. Native plugin collector** | PHP inside Moodle reads config through Moodle APIs (`get_fast_modinfo`, `completion_info`, DML `$DB`) | **Primary. Raw data never crosses the Moodle boundary.** |
| **B. Moodle REST web services** | External app calls `core_*` functions with a token | **Not for collection.** Needs a long-lived token with broad capabilities, exposes rows over the network, and many config fields (`quiz_slots`, `grade_items.gradepass`, `scorm_scoes.launch`) have no stable endpoint. Every extra hop widens the compliance scope. |
| **C. Report Builder (system reports and custom report sources)** | Moodle's built-in reporting, with its own entities, conditions, audiences and capability checks | **The reference for scope and access control.** Every screen that shows results is a Report Builder system report over our `local_releasegate_*` tables, so who-sees-what, filtering, scheduling and export follow Moodle's own rules and are not re-invented by us. Its entity and column lists also act as our **approved-column allowlist**: if a column is not exposed through a Report Builder entity, a rule does not read it without an explicit review. |

Decision: **C is the access-control and presentation standard, A collects the facts Report Builder cannot express, B carries only derived results** (see section 6).

Why A still exists: Report Builder entities cover core tables well (course, enrolment, users), but several facts the rules need (`quiz_slots`, `scorm_scoes.launch`, the `availability` JSON, completion criteria details) have no entity, so a native collector reads them. It follows the same rules as Report Builder: Moodle APIs first, capability checks on every entry point, no data outside the allowlist.

## 2b. UI stack (decided)

PHP (LAMP) with Mustache templates, AMD/ESM JavaScript and Moodle core Bootstrap theming. No React, no separate MERN front end, no CDN assets. Reasons: it installs and upgrades like any Moodle plugin, enterprise IT already knows how to review and run it, accessibility and theming come from core, and there is no second app to secure. A standalone platform can come later; the plugin is the product until then.

## 2c. Quality bar

The plugin must be complete without AI: every verdict, report, export and audit entry works with AI disabled. AI features are additive and never required for a correct result.

Caveat to own: `mdl_*` table layouts are **not public API** and change between Moodle versions. Today's code reads some tables directly. Rule: prefer Moodle APIs; where raw SQL is unavoidable, isolate it behind a per-version adapter and run CI against every supported Moodle version. **[DECIDE]** the supported set (4.5 LTS, 5.0, 5.1).

## 3. Data classification

### Tier A — course configuration (collected)
Describes how a course is built, not who took it. No personal data.

| Moodle table | Columns used | Used by | Status |
|---|---|---|---|
| `course` | `id`, `visible`, `enablecompletion`, `idnumber`, `fullname` | runner, sweep, RG-CRS-001 | *(verified)* |
| `course_modules` + `modules` | `id`, `module`, `instance`, `visible`, `completion`, `deletioninprogress`, `availability` | course_context, RG-RST-001 | *(verified)* |
| `course_completion_criteria` | `course`, `criteriatype`, `moduleinstance` | RG-CMP-001/002/003 | *(verified)* |
| `enrol` | `courseid`, `enrol`, `status` | RG-ENR-001 | *(verified)* |
| `grade_items` | `courseid`, `itemmodule`, `iteminstance`, `gradepass`, `grademax` | RG-GRD-001, RG-QUZ-002 | *(verified)* |
| `quiz`, `quiz_slots` | `quiz.grade`, `quiz_slots.quizid` | RG-QUZ-001/002 | *(verified)* — slot/question layout has changed across 4.x to 5.x, adapter needed |
| `scorm`, `scorm_scoes` | `launch` | RG-SCM-001 | *(verified)* |
| `task_scheduled` | `lastruntime` | cron_health check | *(verified)* |
| `h5pactivity`, `customfield_*`, `competency_*`, `badge` | course-level config only | planned rules | *(proposed, unverified)* |

### Tier B — aggregates (collected only as counts)
Number of enrolments, share of activities with completion enabled. Counts only, never row-level, never per user. *(proposed)*

### Tier C — personal and learner data (**not collected, denylisted**)
`user`, `user_enrolments`, `grade_grades`, `course_modules_completion`, `course_completions`, `quiz_attempts`, `logstore_standard_log`, messages, files, submissions. Rules must not join these tables. Enforce with a CI check that fails the build if a Tier C table name appears in `classes/local/rule/`. *(proposed)* Where a future rule needs learner-level evidence (QA tester proof), it reads only designated QA accounts and stores a hash plus a timestamp.

### Tier P — plugin-owned tables
| Table | Holds | Personal data |
|---|---|---|
| `local_releasegate_run` | verdict, coverage, ruleset version, config fingerprint, `actorref` | pseudonymous ref only *(verified)* |
| `local_releasegate_result` | rule id, severity, status, message, evidence | evidence must be config facts, no names or emails *(proposed check)* |
| `local_releasegate_audit` | hash-chained actions, `actorref` | pseudonymous ref only *(verified)* |

## 4. Compliance scope: what an enterprise security review will ask

| Question | Answer to build toward | State today |
|---|---|---|
| Does learner data leave our Moodle? | No. Tier C is never read. | Holds in current rules; CI guard missing |
| What can the plugin write? | Only its own `local_releasegate_*` tables. Courses are read-only. | Holds *(verified)* |
| Who can see what? | Moodle capabilities, `viewlearnerdetail` separate, requester != approver | 2 capabilities exist; fuller set missing |
| Is the audit trail tamper-evident? | Hash-chained, append-only, verifiable by a CLI command | Chain exists *(verified)*; verify command and legal hold missing |
| Privacy / DSAR | Privacy API provider, pseudonymous refs, retention setting | Provider exists; retention and erase flow missing |
| Is AI involved? | Off by default. If on: bring-your-own endpoint, rule output only, never PII | Not built |
| Third parties / subprocessors? | None by default; paid cloud services are opt-in and listed | n/a |
| Code provenance | GPLv3+ source, SBOM, signed releases | Not built |

Rule for all marketing and docs: we provide **evidence, not certification, and not legal advice**. Do not claim DPDP, GDPR or SOC 2 compliance. Claim "supports the buyer's control evidence".

## 5. What the collector reads per run

1. Resolve the course and take a **config fingerprint** (SHA-256 over Tier A facts) so the same configuration always yields the same verdict.
2. Build `course_context` once (modules, completion, criteria), then run each rule against it. No rule opens its own connection or runs its own broad query.
3. Write run, results and audit in one transaction. Rule failure becomes `ERROR`, never a silent pass.
4. Coverage and verdict rules stay exactly as in docs 02 and 03.

## 6. What may leave Moodle (only if the buyer enables a paid service)

Allowed: verdict, rule ids, severities, counts, fingerprint, ruleset version, site id. Never: course text, user data, file content, free-text evidence. Delivery by signed outbound call from Moodle to the service, so the buyer opens no inbound port and issues no REST token. The REST API is therefore not needed for the core product.

## 7. Gaps between this blueprint and the code today

1. Add the Tier C denylist CI check.
2. Add a per-version DB adapter and a CI matrix on the supported Moodle versions.
3. Fix `moodle_exception('locktimeout','error')`: the string does not exist (runner:49, audit:61).
4. Stop leaking raw exception messages to the UI.
5. Add audit verify command, retention setting and erase flow.
6. Resolve doc/code drift: docs list ~90 rules and 5 CLI exit codes (0/10/20/30/40); code has 10 rules and 4 exit codes (0/1/2/3).

## 8. Decisions needed from the owner

- **[DECIDE]** Supported Moodle versions and database engines.
- **[DECIDE]** First compliance pack target (DPDP or GDPR) and who reviews it with counsel.
- **[DECIDE]** Product name: "Moodle" in the name blocks launch under the trademark policy.

## 9. Reuse map: Moodle subsystems enterprises already run and audit

Principle: for each concern below, use the Moodle core mechanism before writing our own. Confidence is stated per row; items marked *spike* must be proven on a real 5.1 site before we rely on them.

| Concern | Reuse this | What we do | Confidence |
|---|---|---|---|
| Who can do what | Roles, contexts and capabilities (`has_capability`, `require_capability`) | Define capabilities at **course** context for per-course screens, set `riskbitmask` honestly (admins audit by risk), never gate by role names | High |
| Result screens | Report Builder **system reports** | Report-level access is built in. **Row-level scoping is not automatic:** a user allowed to view a system report sees whatever the report query returns, so the query itself must restrict rows to courses where the viewer holds the capability | Medium, *spike* |
| Audit and logging | Events API and the log store | Emit every gate action as a Moodle event so enterprise log forwarding and SIEM pick it up. Keep our hash chain as an additional layer, not a replacement | High |
| Tamper evidence | Hash chain plus external anchoring | A DB admin can rewrite a local chain. Enterprise auditors know this, so anchor chain heads outside the DB (RFC 3161 timestamps, or periodic export). Without that, say "tamper-evident against application users", not "tamper-proof" | High |
| Privacy and retention | Privacy API and the data-privacy tool's retention | Full provider (metadata, export, delete) and a retention setting that uses core's mechanism | High |
| Site health | Check API (security and performance reports) | `cron_health` exists; add a check for stale scans and failed runs so admins see us where they already look | High |
| Background work | Task API (adhoc and scheduled), Lock API, MUC | Already in use | High |
| Settings | Admin settings API and config locking in `config.php` | Enterprises force settings in `config.php`; our settings must honour locked values | Medium |
| Navigation and extension | Hooks API (newer Moodle) vs legacy `extend_navigation_course` callbacks | Current code uses legacy-style callbacks. Check deprecation status against 4.5, 5.0 and 5.1 and move to hooks if needed | Low, *spike* |
| Code quality gate | moodle-plugin-ci, Moodle coding style (phpcs), PHPUnit, Behat | Required before any customer sees it; also what Moodle Partners expect | High |
| Supply chain and security | SBOM (CycloneDX or SPDX), OWASP ASVS as the checklist | Needed for procurement questionnaires | Medium |

What not to reuse blindly: Moodle's own web service layer as a collector (see section 2), and any claim that "using Report Builder makes us compliant". It gives consistent access control, not compliance.

## 11. Access model and trust experience

Goal: an enterprise admin can answer, in minutes and without our help, **"who can see what, who actually saw what, and why"**, and can change it for any person or group instantly, using tools they already know. We build no permission system of our own.

### 11.1 Policy: Moodle roles are the only switch
- Every action is a separate capability, so access can be given or taken away one action at a time: view verdict, view rule results, view evidence detail, run a scan, export, view audit, waive a rule, approve a release, manage settings.
- Admins assign these through the standard **Define roles** screen to any role, any archetype, at system, category or course level. Cohorts are covered by core's cohort-role assignment and cohort enrolment sync, so "everyone in cohort X can view results in category Y" needs no code from us.
- Inside a course, group-based visibility reuses core group mode and the "access all groups" capability.
- Change takes effect from the next request in normal cases *(medium confidence, verify in the spike)*.
- **Safe default:** a fresh install grants nothing beyond what administrators and managers get from archetype defaults. Learner and teacher roles see nothing until an admin allows it. Waiving a rule and approving a release stay separate capabilities and the requester can never be the approver.

### 11.2 "Why can X see A?" (explainability, reuse core)
- Core already has a **Check permissions** page that shows, for a user and a context, which role assignment grants a capability. Our screens link straight to it. We do not rebuild it.
- Add an **Access review** system report: for each Release Gate capability, which roles hold it and which users and cohorts effectively have it, per category or course. Enterprises run this quarterly for access recertification, so it must export cleanly.

### 11.3 "Who actually saw what?" (access log)
- Viewing a verdict, opening evidence detail, exporting and changing a setting each emit a Moodle event with actor, course and object, so it flows into the buyer's existing log store and SIEM. This is separate from the hash-chained gate audit.
- The audit tells what the system decided; the access log tells who looked. Enterprises need both.

### 11.4 Data going in and going out
- **In:** read-only on Tier A only (section 3). **Out:** only through exports and the optional outbound service (section 6). Both are separate capabilities, both logged, both limited to the approved columns.
- A CI test fails the build if any code path writes to a table outside `local_releasegate_*`. This turns "we are read-only" from a promise into a checked property.

### 11.5 Install without headache (targets, not yet measured)
1. No extra service, no web service token, no inbound port, no extra cron entry. It runs on Moodle's own cron.
2. Scans are queued per course with low concurrency and a configurable schedule, so load is predictable and can be capped.
3. A built-in **Trust sheet** page shows what is read (Tier A), what is never read (Tier C), who can see results by default, and which events are logged. A security reviewer can screenshot it.
4. `gate.php --dry-run` lists what a scan would read and writes nothing, so IT can test before enabling schedules.
5. Clean uninstall removes plugin tables, and the Privacy API provider covers export and erase.

### 11.6 Spike scope (first thing to test on a real 5.1 stage site)
1. Create two roles and a cohort; give role A view on course 1 only, role B view on course 2 only. Confirm a user sees exactly their own course's results in every report, export and link, including direct URLs and report IDs.
2. Change a role's capability and confirm the effect on the next request.
3. Confirm the Check permissions link and Access review report give the correct explanation for both users.
4. Confirm each view and export produced an event.

## 10. Where this document deliberately disagrees with the working assumptions

1. **"Report Builder enforces scope strictly."** At report level yes, at row level no (see section 9). This is the most likely place for a real access-control bug, so it is the first spike.
2. **"Legacy stack means enterprise grade."** The stack choice (LAMP, Mustache, AMD) removes risk, but enterprise grade is earned by accessibility, upgrade safety, tests and documented controls, which are the gaps listed in section 7.
3. **"The audit chain is the compliance story."** It is one control among several, and weaker than it sounds until anchored externally.

## 12. Compatibility policy (owner decision, 2 October 2026)

| Item | Decision | Source |
|---|---|---|
| Moodle branches | 4.5 LTS, 5.0, 5.1, and 5.3 LTS | owner; 5.3 release scheduled 5 Oct 2026 (web search, not confirmed on the project's own release page) |
| Minimum | `$plugin->requires = 2024100700` (4.5.0) | 4.5 branch `version.php` base; 5.1 base is 2025100600 (source mirror) |
| PHP | Write to the 8.1 floor (4.5). 8.3 is the one version valid on all four branches | PHP ranges: 4.5 is 8.1 to 8.3, 5.0 and 5.1 are 8.2 to 8.4, 5.3 is 8.3 and 8.4 (web search) |
| Database | MySQL first. MariaDB and PostgreSQL ready as disabled CI jobs, enabled later | owner |
| Cross-version rule | Use only APIs present on every branch. Verified for `core\lang_string` and `core\exception\required_capability_exception` on the 4.5 branch. Anything else needs a check against the 4.5 branch before use | this document |

Consequences: the per-version adapter in section 2 now has four targets; the "spike" (section 11.6) runs first on 5.1 (the stage site) and then through CI on the other branches. The old `requires` of Moodle 4.0 was wrong for the code as written and has been raised.
