# Rule coverage matrix (batch 1, CI pending)

Source: `classes/local/rule/` and `classes/local/engine/registry.php` for implemented;
root catalog `06-Course-QA-Settings-Catalog.md` sections 3.1-3.16 and section 4 for specified-but-not-built.
Unknown areas are marked "not yet researched"; no rules are invented here.

| Area | Implemented rule ids | Specified but not built (catalog) | Research needed | Notes |
|---|---|---|---|---|
| course settings | RG-CRS-001 | RG-CRS-002, RG-CRS-003, RG-CRS-004, RG-CRS-008, RG-CRS-009, RG-CRS-010, RG-GRP-001, RG-DAT-005, RG-MSG-001, RG-AIT-001 | no | catalog 3.1 |
| enrolment and access | RG-ENR-001, RG-ENR-006 | RG-ENR-002, RG-ENR-003, RG-ENR-004, RG-ENR-005, RG-ENR-007, RG-DAT-003, RG-SEN family | no | catalog 3.2; ENR-006 batch 1 |
| groups | none | RG-GRP-001 | yes | catalog 3.1, 3.3; grouping with no members not yet researched |
| sections and visibility | none | RG-CRS-006 | yes | catalog 3.3; visible/visibleoncoursepage not yet built |
| restrictions/availability | RG-RST-001 | RG-RST-002..007, RG-RST-009 | yes | catalog 3.3; JSON parse, cycles, missing targets need research (batch 4) |
| activity completion | RG-CMP-016 | RG-CMP-005, RG-CMP-007, RG-CMP-009, RG-CMP-010, RG-CMP-014, RG-GRD-008 | no | catalog 3.4; CMP-016 batch 1 |
| course completion | RG-CMP-001, RG-CMP-002, RG-CMP-003, RG-CMP-015 | RG-CMP-008, RG-CMP-011, RG-CMP-012, RG-CMP-013, RG-CMP-017 | no | catalog 3.5; CMP-015 batch 1, CMP-006 covered via grades |
| gradebook | RG-GRD-001 | RG-GRD-006, RG-GRD-007, RG-GRD-008, RG-GRD-013, RG-GRD-014, RG-GRD-020 | yes | catalog 3.11; pass-grade part built |
| reports and visibility to learners | none | RG-CRS-009, RG-CRS-010 | yes | catalog 3.1; showgrades/showreports/showcompletionconditions not yet researched |
| quiz | RG-QUZ-001, RG-QUZ-002, RG-GRD-015, RG-GRD-016, RG-GRD-018 | RG-GRD-002, RG-GRD-003, RG-GRD-005, RG-GRD-009, RG-GRD-010, RG-GRD-011, RG-GRD-012, RG-GRD-017, RG-GRD-019 | no | catalog 3.6; batch 1 adds 015/016/018 |
| scorm | RG-SCM-001 | RG-SCM-002, RG-SCM-003, RG-SCM-004, RG-SCM-005, RG-SCM-006, RG-SCM-007, RG-DAT-004 | no | catalog 3.7 |
| h5pactivity | RG-H5P-006 | RG-H5P-003, RG-H5P-004 | no | catalog 3.8; H5P has no custom completion rules, only view/grade |
| assign | (covered via RG-CMP-016) | RG-ACT-001, RG-ACT-002, RG-ACT-004, RG-ACT-005 | yes | catalog 3.9; view-only part built, rest needs research (batch 6) |
| lesson | (covered via RG-CMP-016) | RG-ACT-006, RG-ACT-007 | yes | catalog 3.10; view-only part built, rest needs research |
| feedback | none | RG-POL-001, RG-DAT-004 | yes | catalog 3.10; anonymous attestation not yet researched |
| workshop | none | not yet researched | yes | not yet researched; catalog marks workshop unresearched |
| glossary | none | not yet researched | yes | not yet researched; catalog marks glossary unresearched |
| forum | none | RG-ACT-003, RG-DAT-004 | yes | catalog 3.10; announcement post limits need research |
| choice | none | RG-POL-002, RG-DAT-004 | yes | catalog 3.10; publish/showresults/allowupdate need research |
| page | (covered via RG-CMP-001/002/003) | none listed | yes | catalog uses page as generic activity example only |
| file | none | not yet researched | yes | not yet researched; resources batch 7 |
| folder | none | not yet researched | yes | not yet researched; resources batch 7 |
| url | none | not yet researched | yes | not yet researched; resources batch 7 |
| label | none | not yet researched | yes | not yet researched; resources batch 7 |
| book | none | not yet researched | yes | not yet researched; resources batch 7 |
| supervideo (third-party, optional) | none | not yet researched | yes | not core; rule only if plugin installed, otherwise N/A |
| badges and certificates | none | RG-CRT-001, RG-CRT-003, RG-DAT-006, RG-MSG-001, RG-SIT-004 | yes | catalog 3.12 |
| messaging | none | RG-MSG-001, RG-MSG-002 | yes | catalog 3.15 |
| site | none (cron check only) | RG-SIT-001..010 | yes | catalog 3.16 |
