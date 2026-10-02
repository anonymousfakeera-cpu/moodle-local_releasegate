> **Research note.** Claims are tagged by evidence level: *verified in code* (read from Moodle or plugin source, with file:line), *not run* (not executed), or *hypothesis*. References to "docs 01 to 04" or "the owner" point to internal planning material that is not part of this repository.

# 06 — Course QA Settings Catalog (what breaks a course in production)

Status: draft. Companion to doc 01 (rule catalogue, section 8.3) and doc 05 (data scope).

## 0. Method and trust level

- **Source of truth:** the Moodle **5.1.5+** source mirror in a local checkout of the Moodle 5.1 stable branch. Table columns, coded values and web-service names below were read from that source with file:line references, by three delegated readers plus my own spot-check of `course`, `course_modules` and `grade_items` in `public/lib/db/install.xml`.
- **Not verified:** nothing here was executed against a live site. "Symptom" columns are my analysis of what the verified semantics imply; treat them as hypotheses until the spike reproduces each one.
- **Core web-service names** were confirmed by grep in `public/lib/db/services.php`.
- **Data tiers** follow doc 05. Config columns are Tier A. Tables holding learner state (`course_modules_completion`, `quiz_attempts`, `h5pactivity_attempts`, `grade_grades`) are Tier C and are only ever read as aggregates.

## 1. Why courses actually break (seven root causes)

1. **Copy, restore, import or API bypasses form validation.** Examples verified in source: quiz "minimum attempts" must not exceed "attempts" only in the edit form (`mod/quiz/mod_form.php:562-566`); self-enrolment expiry threshold must be at least one day only in form validation (`enrol/self/lib.php:1093-1094`). A course that arrives through backup/restore, copy, import or web service can carry values the UI would have refused. This is the strongest argument for scanning at release time.
2. **Silent defaults.** Course completion is off by default (`course.enablecompletion` default 0, install.xml:102); activity completion defaults to none (`course_modules.completion` default 0, :339); `gradepass` defaults to 0 (grade_items, :2017); quiz `attempts` 0 means unlimited (quiz install.xml:21).
3. **Dependencies on things that vanish.** Restrictions, completion criteria and badge criteria point at activity ids, grade item ids and group ids that can be deleted or hidden (`deletioninprogress`, `visible`).
4. **Date drift.** Five independent clocks (course, enrolment, activity, completion criteria, badge expiry) are set by different people at different times.
5. **Cron dependence.** Completion aggregation, badge awards, enrolment expiry notices and quiz overdue handling run only if scheduled tasks run.
6. **Rules that cannot be satisfied.** Pass grade above max, score required from content that never reports a score, "post a reply" on a forum learners cannot post to.
7. **Rules that are satisfied too easily.** Automatic completion on view only, self-completion criterion, unlimited quiz attempts, answers visible after the first attempt. These do not crash the course; they quietly void its purpose, which is what compliance buyers fear most.

## 2. Reference course profiles (assumptions, to be confirmed with real courses)

### Profile A — Corporate leadership training, 14 activities (assumed composition)
1 welcome page · 2 pre-course survey (feedback) · 3-6 four content modules (2 SCORM, 1 H5P interactive video, 1 lesson) · 7-10 four module quizzes · 11 reflection assignment · 12 discussion forum · 13 final assessment (quiz) · 14 post-course feedback. Completion expected: every activity done, final quiz passed. Certificate via course badge (core) or a certificate plugin (non-core; out of scope until chosen). Enrolment: manual or cohort sync, sometimes self-enrol with a key.

### Profile B — University anti-ragging compliance training
Awareness content (pages, H5P/SCORM video) · signed declaration/undertaking · assessment with a fixed pass mark · certificate or badge · bulk enrolment by batch cohort · hard deadline · results reported by department, hostel or year. **The evidence that a named person completed it, on a date, with a pass, must survive audit.** The regulatory content and retention period must be confirmed by the university's compliance office; nothing here is legal advice.

What differs: Profile A tolerates flexibility and retakes; Profile B needs integrity (no answer leakage, limited attempts, non-anonymous attestation, evidence retention, no self-marking).

## 3. Catalogue

Columns: **Setting** (table.column or config) · **Expect** (golden value for the profile) · **Misconfiguration** · **Production symptom** (hypothesis) · **Check via** · **Rule** (`RG-*` exists in doc 01; **NEW** is proposed).

### 3.1 Course (`course`)
| Setting | Expect | Misconfiguration | Symptom | Check via | Rule |
|---|---|---|---|---|---|
| `enablecompletion` (0/1) | 1 | 0 while activities track completion | Completion ticks and progress never appear; no course completion | `core_course_get_courses` / DB | RG-CRS-001 |
| `visible` | 1 at go-live | 0 | Learners cannot see the course | DB | RG-CRS-002 |
| `startdate`, `enddate` (0 = none) | start ≤ now, end > start | start in future, end in past, end < start | Dashboard shows wrong status; relative dates and "past" filtering wrong | DB | RG-CRS-003 |
| `idnumber` | unique, per policy | missing or duplicate | Reporting and sync (SIS, cohort) cannot match | DB | RG-CRS-004 |
| `groupmode`, `groupmodeforce`, `defaultgroupingid` | per design (B: often visible groups by batch) | forced group mode with no groups | Learners see empty or wrong participant lists | DB | **NEW** RG-GRP-001 |
| `lang` | blank or intended | forced to a language pack that is missing | Interface and activity text fall back unpredictably | DB | RG-CRS-008 |
| `relativedatesmode` | 0 unless deliberately used | 1 with absolute due dates set | Dates shown to each learner differ from design | DB | **NEW** RG-DAT-005 |
| `showgrades`, `showreports` | per design | 0 when learners must see results | Learners cannot see their own grades or reports | DB | **NEW** RG-CRS-009 |
| `showcompletionconditions` | 1 | 0 | Learners cannot see what completes an activity | DB | **NEW** RG-CRS-010 |
| `completionnotify` | 1 for B | 0 | No course-completion message sent | DB | **NEW** RG-MSG-001 |
| `enableaitools` | per policy (B: 0) | 1 where policy forbids | AI tools exposed in a regulated course | DB | **NEW** RG-AIT-001 |

### 3.2 Enrolment (`enrol`, `user_enrolments`)
| Setting | Expect | Misconfiguration | Symptom | Check via | Rule |
|---|---|---|---|---|---|
| `enrol.status` (0 active) | at least one active method | all disabled | Nobody can enter | DB | RG-ENR-001 |
| `enrol.enrolstartdate`, `enrolenddate` | window covers the training period | start in future / end in past (self-enrol gate, `enrol/self/lib.php:314-319`) | "Enrolment not possible" at launch | DB | RG-ENR-002 |
| `enrol.customint3` (self: max enrolled) | ≥ cohort size, or 0 | cap below the number of people | Late learners blocked in a mass roll-out | DB + `cohort_members` count | **NEW** RG-ENR-004 |
| `enrol.customint5` (self: cohort-only) | intended cohort | wrong or empty cohort | Intended people cannot self-enrol | DB | **NEW** RG-ENR-005 |
| `enrol.customint2` (self: inactivity period) | 0 for B, deliberate for A | set (`enrol/self/lib.php:660` moves `timeend`) | Slow learners lose access and may be unenrolled | DB | **NEW** RG-ENR-006 |
| `enrol.enrolperiod` | ≥ time needed to finish | shorter than the course or the deadline | Access ends before completion is possible | DB | **NEW** RG-DAT-003 |
| `enrol.expirynotify`, `expirythreshold` | notify with threshold ≥ 1 day | threshold below 1 day or notify off | Reminders never sent or invalid (form-only check, see 1.1) | DB | **NEW** RG-ENR-007 |
| guest enrolment active | off for B | on | Unidentified users can view or attempt | DB | RG-ENR-003 |
| `user_enrolments.status` (0 active, 1 suspended), `timeend` (default 2147483647 = forever) | active, end after deadline | suspended or ended early | Learner locked out | aggregate counts only | RG-SEN family |

### 3.3 Structure, visibility, restrictions (`course_sections`, `course_modules`)
| Setting | Expect | Misconfiguration | Symptom | Check via | Rule |
|---|---|---|---|---|---|
| `course_modules.visible`, `visibleoncoursepage` | 1 | hidden activity inside a required path | Learner cannot finish | `core_course_get_contents` | RG-CRS-006 |
| `deletioninprogress` | 0 | 1 | Activity vanishes but criteria still reference it | DB | RG-CMP-003 |
| `availability` JSON (cm and section): types `completion`, `date`, `grade`, `group`, `grouping`, `profile` | targets exist, are reachable, no cycles | condition points to deleted/hidden activity, missing grade item or group; circular chain; date window closed | Activity permanently locked for everyone | DB parse | RG-RST-001..007 |
| `availability` `completion` with `cm` = -1 ("previous activity") | previous activity has completion | previous activity has none or is a label | Unlock never fires | DB parse | RG-RST-002 |
| `availability` `profile` condition (fields: firstname, lastname, email, city, country, idnumber, institution, department, phone1, phone2, address, or a custom field) | intended and populated | condition on a profile field most learners leave empty | Most learners locked out | DB parse | **NEW** RG-RST-009 |
| `course_modules.groupmode`, `groupingid` | per design | grouping with no members | Activity empty or hidden for learners | DB | RG-GRP-001 |
| `availability` JSON with no role condition | n/a | team expects role-based gating; **Moodle 5.1 has no role condition** (`public/availability/condition` has completion, date, grade, group, grouping, profile only) | Assumed restriction does not exist | n/a | documentation note |

### 3.4 Activity completion (`course_modules`)
Verified coding: `completion` 0 none, 1 manual, 2 automatic; `completionview` 0/1; `completionpassgrade` 0/1; `completiongradeitemnumber` null or item number; `completionexpected` timestamp, display only (`lib/completionlib.php`, install.xml:339-343). Completion state: 0 incomplete, 1 complete, 2 pass, 3 fail, 4 fail on hidden grade (`completionlib.php:73-94`).

| Misconfiguration | Symptom | Rule |
|---|---|---|
| `completion` = 0 on a quiz, SCORM, H5P, assignment or lesson | Activity can never count toward completion | RG-CMP-007 |
| `completion` = 2 with no rule selected | Never completes | RG-CMP-009 |
| `completion` = 1 (manual tick) on a graded assessment | Learner ticks it without passing | RG-CMP-010 |
| `completion` = 2 with only `completionview` = 1 on an assessment | **Completes on view, no attempt needed** | **NEW** RG-CMP-016 |
| `completionpassgrade` = 1 but `gradepass` = 0 | "Pass" is meaningless; everyone passes | RG-CMP-006 |
| "Receive a grade" but activity grade = 0 | Never completes | RG-CMP-005 |
| `completionexpected` in the past | Everyone shows overdue | RG-CMP-014 |
| Hidden or locked grade item behind pass-based completion (state 4) | Learner passes but shows incomplete | RG-GRD-008 |

### 3.5 Course completion (`course_completion_criteria`, `course_completion_aggr_methd`)
Verified: criteria types 1 self, 2 date, 3 unenrol, 4 activity, 5 duration, 6 grade, 7 role, 8 course (`completion/criteria/completion_criteria.php`); aggregation method 1 all, 2 any (the code accepts only these two, `completion/completion_aggregation.php:92-102`).

| Misconfiguration | Symptom | Rule |
|---|---|---|
| Completion enabled, no criteria | Nobody can complete | RG-CMP-001 |
| Activity criterion on activity with `completion` = 0 | Unreachable | RG-CMP-002 |
| Criterion references deleted activity | Unreachable | RG-CMP-003 |
| Tracked activity not part of criteria | Course completes without it | RG-CMP-008 |
| Date criterion (type 2) already past | Everyone completes immediately | RG-CMP-011 |
| Grade criterion (type 6) above attainable | Unreachable | RG-CMP-012 |
| Aggregation ANY where ALL is intended | Completes after one activity | RG-CMP-013 |
| **Self-completion criterion (type 1) present** | **Learner marks themselves complete without assessment** | **NEW** RG-CMP-015 |
| Role criterion (type 7) or unenrol criterion (type 3) used by mistake | Completion triggered by a teacher action or by leaving | **NEW** RG-CMP-017 |
| Duration criterion (type 5, days after enrolment) shorter than the content | Auto-completes without engagement | RG-CMP-011 (extend) |

### 3.6 Quiz (`quiz`, `quiz_slots`, access rules)
| Setting | Expect | Misconfiguration | Symptom | Check via | Rule |
|---|---|---|---|---|---|
| `sumgrades` vs sum of `quiz_slots.maxmark` | equal | differ | Wrong grades until regrade | `mod_quiz_get_quizzes_by_courses` + DB | RG-GRD-002 |
| `quiz_slots` rows | ≥ 1 | none | Empty quiz | DB | RG-GRD-003 |
| `grade` vs `grade_items.gradepass` | `gradepass` between min and `grade` | pass above max | Nobody passes | DB | RG-GRD-001 |
| `attempts` (0 = unlimited) | B: small fixed number; A: per design | 0 on a pass-or-fail compliance quiz | Learners retry until they pass; weakens evidence | DB | **NEW** RG-GRD-016 |
| `completionminattempts` vs `attempts` | min ≤ attempts | min > attempts (form-only check) | Completion unreachable | DB | **NEW** RG-GRD-018 |
| `completionattemptsexhausted` with `attempts` = 0 | not combined | combined | Never completes | DB | RG-GRD-009 |
| `grademethod` (1 highest, 2 average, 3 first, 4 last) | per policy | wrong method for the policy | Recorded grade does not match the intended attempt | DB | **NEW** RG-GRD-017 |
| `timelimit` + `overduehandling` (`autoabandon` default / `autosubmit` / `graceperiod`) | `autosubmit` | `autoabandon` | Timed-out attempt is lost, not graded | DB | RG-GRD-010 |
| `timeopen`, `timeclose` | window inside course dates | close before open or already closed | Quiz unavailable | DB | RG-GRD-011 |
| `reviewattempt`, `reviewrightanswer`, `reviewcorrectness`, etc. (bit flags: during 0x10000, immediately 0x01000, later while open 0x00100, after close 0x00010) | B: no right answers before close | right answers visible immediately | **Answers can be shared**; integrity lost | DB | **NEW** RG-GRD-015 |
| `password`, `subnet`, `browsersecurity`, SEB (`quizaccess_seb_quizsettings.requiresafeexambrowser`) | as announced | enabled but not communicated, or SEB required without keys | Learners locked out at exam time | DB | RG-GRD-012 |
| `delay1`, `delay2` | small or 0 | long forced delay | Learner cannot retry in time | DB | **NEW** RG-GRD-019 |
| Question versions in use | ready | draft | Unreviewed question served | DB | RG-GRD-005 |

### 3.7 SCORM (`scorm`, `scorm_scoes`)
| Setting | Misconfiguration | Symptom | Rule |
|---|---|---|---|
| `scorm_scoes.launch` | no launchable SCO | Blank player | RG-SCM-001 |
| `completionstatusrequired` (bit 2 passed, 4 completed), `completionscorerequired`, `completionstatusallscos` | requires "passed" or a score from a package that reports neither | Never completes | RG-SCM-002 |
| `maxgrade` = 0 with score-based completion | Never completes | RG-SCM-003 |
| `popup` = 1 | Blocked by popup blockers and mobile | RG-SCM-004 |
| `maxattempt` (0 = no limit), `forcenewattempt`, `lastattemptlock`, `whatgrade`, `grademethod` | Conflicting attempt policy | Learner locked or graded on the wrong attempt | RG-SCM-005 |
| `forcecompleted` | Only applies to SCORM 1.2 (`lang/en/scorm.php:156-158`) | Assumed to work on 2004 packages | **NEW** RG-SCM-006 |
| `timeopen`, `timeclose` vs course dates | Window outside the course | **NEW** RG-DAT-004 |
| `skipview`, `hidebrowse`, `hidetoc`, `auto`, `nav` | Hidden structure or navigation that the package needs | Learner stuck | **NEW** RG-SCM-007 |

### 3.8 H5P (`h5pactivity`)
| Setting | Misconfiguration | Symptom | Rule |
|---|---|---|---|
| `enabletracking` = 0 with completion or grade dependent on attempts | No attempt data recorded | RG-H5P-003 |
| `grade` = 0 with grade-based completion | Never completes | RG-H5P-004 |
| `grademethod` = 0 (manual) | **No automatic grade**, so grade-based completion never fires (`classes/local/grader.php:148`) | **NEW** RG-H5P-006 |
| No custom completion rules exist for H5P (`FEATURE_COMPLETION_HAS_RULES` not declared, `lib.php:60`) | Team expects "complete on success"; only view or grade can drive completion | documentation note |
| `reviewmode` | Learners cannot review their attempts | informational |

### 3.9 Assignment (`assign`)
| Setting | Misconfiguration | Symptom | Rule |
|---|---|---|---|
| `cutoffdate` < `duedate` | Late window closes before due date | RG-ACT-001 |
| `grade` = 0 with grade-based completion | Never completes | RG-ACT-002 |
| `completionsubmit` = 1 with `submissiondrafts` = 1 | Learner saves a draft and believes they submitted | RG-ACT-004 |
| `maxattempts` (-1 unlimited), `attemptreopenmethod` | Unlimited reopen on a compliance task | **NEW** RG-ACT-005 |
| `requiresubmissionstatement` = 0 on a declaration task | No attestation text accepted by the learner | **NEW** RG-POL-004 |
| `duedate` / `cutoffdate` after `course.enddate` | Dates contradict | **NEW** RG-DAT-002 |

### 3.10 Lesson, forum, feedback, choice
| Activity | Misconfiguration | Symptom | Rule |
|---|---|---|---|
| Forum `type` announcement with `completionposts/replies/discussions` | Learners cannot post | RG-ACT-003 |
| Forum `cutoffdate`, `lockdiscussionafter`, `blockafter` earlier than course end | Completion posts blocked | **NEW** RG-DAT-004 |
| Lesson `completionendreached`, `completiontimespent` with `deadline`/`available` | Window shorter than required time | **NEW** RG-ACT-006 |
| Lesson `maxattempts`, `retake`, `timelimit` | Conflicting limits | **NEW** RG-ACT-007 |
| Feedback `anonymous` = 1 on an attestation or declaration | **Cannot prove who attested** | **NEW** RG-POL-001 |
| Feedback `multiple_submit`, `timeclose` before course end | Duplicate or late-blocked responses | **NEW** RG-DAT-004 |
| Choice `publish` / `showresults` showing named responses; `allowupdate` = 1 on attestation | Privacy leak or changeable attestation | **NEW** RG-POL-002 |

### 3.11 Gradebook (`grade_categories`, `grade_items`)
Verified: `gradepass` default 0 (:2017), `hidden`/`locked` (1 or date, :2025-2026), `needsupdate` (:2028), `droplow`/`keephigh` (:1985-1986), `aggregateonlygraded` (:1987).
Misconfigurations map to RG-GRD-001, 006, 007, 008, 013, 014 in doc 01. **NEW:** RG-GRD-020 `calculation` formula referencing a deleted item id.

### 3.12 Certificate and badges (`badge`, `badge_criteria`, `badge_issued`)
| Setting | Misconfiguration | Symptom | Rule |
|---|---|---|---|
| `badge.status` (0 inactive, 1 active, 2 inactive+locked, 3 active+locked, 4 archived) | No active badge | No certificate | RG-CRT-001 |
| `badge_criteria` type 4 course / 1 activity referencing missing items | Never awarded | **NEW** RG-CRT-003 |
| `badge.expiredate` / `expireperiod` shorter than the compliance cycle | Certificate expires early | **NEW** RG-DAT-006 |
| `badge.notification`, `messagesubject` | Learner not told | **NEW** RG-MSG-001 |
| Badge award depends on cron (`nextcron`) | Delayed or no award when cron is stale | RG-SIT-004 |

### 3.13 Attestation and policy (`tool_policy_*`)
Verified: policy tables have **no course link**; they apply site-wide (`admin/tool/policy/db/install.xml`). Fields: `status` 0 draft / 1 active / 2 archived, `optional` 0 compulsory / 1 optional, `audience` 0 all / 1 logged-in / 2 guests, acceptance `status` 1 accepted / 0 declined / NULL pending.
| Misconfiguration | Symptom | Rule |
|---|---|---|
| Per-course undertaking modelled with `tool_policy` | Policy is site-wide, not per course | design note |
| Policy still draft (0) or archived (2) | Learners are never asked | **NEW** RG-POL-003 |
| `optional` = 1 on a must-accept policy | Learners can skip it | **NEW** RG-POL-003 |
| `audience` = guests only | Logged-in learners never asked | **NEW** RG-POL-003 |

### 3.14 Retention and privacy (`tool_dataprivacy_*`)
Verified: `purpose.retentionperiod` is an ISO 8601 interval; expired contexts are deleted by the scheduled task `delete_expired_contexts` (`admin/tool/dataprivacy/db/tasks.php`). **A retention period shorter than the regulatory evidence period will delete compliance proof.** **NEW** RG-RET-001 (compare against a declared required retention), RG-RET-002 (`loglifetime`).

### 3.15 Messaging and reminders
Verified providers: `coursecompleted`, `enrolcoursewelcomemessage`, `expiry_notification` (self-enrol), badge notices. Scheduled tasks: self-enrol `sync_enrolments` and `send_expiry_notifications` every 10 minutes (`enrol/self/db/tasks.php`). If notification providers are disabled for users or cron is stale, deadline reminders never arrive. **NEW** RG-MSG-001, RG-MSG-002.

### 3.16 Site (see doc 01 RG-SIT-001..010)
Maintenance mode, site-wide completion off, restrictions disabled, stale or failing tasks, debug on, caches off, outgoing mail suppressed or SMTP missing. All map to existing rules.

## 4. Date-alignment matrix (new rule family RG-DAT)

One check, many clocks. Every pair must be consistent:
`course.startdate/enddate` · `enrol.enrolstartdate/enrolenddate/enrolperiod` · activity windows (`quiz.timeopen/timeclose`, `assign.allowsubmissionsfromdate/duedate/cutoffdate`, `scorm.timeopen/timeclose`, `lesson.available/deadline`, `forum.cutoffdate`, `feedback/choice.timeopen/timeclose`) · `course_completion_criteria.timeend` and `enrolperiod` · `availability` date conditions · `course_modules.completionexpected` · `badge.expiredate`.
Proposed IDs: RG-DAT-001 course vs enrolment · -002 activity vs course end · -003 enrol period vs content length · -004 activity windows vs required completion · -005 relative-dates mode vs absolute dates · -006 badge expiry vs compliance cycle.

## 5. Web-service surface (read-only endpoints worth using)

Verified from source (`db/services.php`): `mod_quiz_get_quizzes_by_courses`, `mod_quiz_get_quiz_access_information`, `mod_quiz_get_overrides`, `mod_scorm_get_scorms_by_courses`, `mod_scorm_get_scorm_scoes`, `mod_scorm_get_scorm_access_information`, `mod_h5pactivity_get_h5pactivities_by_courses`, `mod_h5pactivity_get_h5pactivity_access_information`, `mod_assign_get_assignments`, `mod_lesson_get_lessons_by_courses`, `mod_feedback_get_feedbacks_by_courses`, `mod_choice_get_choices_by_courses`, `mod_forum_get_forums_by_courses`, `mod_page_get_pages_by_courses`, `mod_resource_get_resources_by_courses`, `mod_url_get_urls_by_courses`, `mod_folder_get_folders_by_courses`, `mod_book_get_books_by_courses`, `mod_label_get_labels_by_courses`.
Core functions, confirmed in `public/lib/db/services.php`: `core_course_get_courses` (:663), `core_course_get_contents` (:548), `core_course_get_course_module` (:557), `core_completion_get_course_completion_status` (:460), `core_enrol_get_enrolled_users` (:859), `core_group_get_course_groups` (:1214).
Coverage gap: **no listed function exposes `course_completion_criteria`, `availability` JSON, `quiz_slots`, or `scorm_scoes.launch` directly**, which confirms the doc 05 decision to read these inside Moodle (native collector), not over web services.

## 6. Counts and next steps

- Existing doc 01 rules touched: about 60 of the 88.
- New rules proposed here: RG-CRS-009/010, RG-GRP-001, RG-ENR-004..007, RG-RST-009, RG-CMP-015..017, RG-GRD-015..020, RG-SCM-006/007, RG-H5P-006, RG-ACT-005..007, RG-POL-001..004, RG-RET-001/002, RG-MSG-001/002, RG-AIT-001, RG-CRT-003, RG-DAT-001..006 (about 40).
- Proposed order: (1) the "too easy to satisfy" integrity rules for Profile B (RG-CMP-015/016, RG-GRD-015/016, RG-POL-001/002), (2) the date matrix, (3) unsatisfiable-completion rules, (4) the rest.
- Validate with the spike: build both profiles as real courses on the stage site, break each setting on purpose, and confirm the symptom column.
