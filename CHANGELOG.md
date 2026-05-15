# Changelog

## 0.3.0 - 2026-05-15

- Added `build.ps1` for clean ZIP builds with structure validation and optional PHP syntax checks.
- Added configurable final Gradebook maximum per activity.
- Added an option to count empty grades as zero.
- Changed average aggregation to use lesson percentages converted to the configured Gradebook maximum.
- Added UI explanations for aggregation and visible lesson saving behavior.
- Added `classjournal_get_final_grades` and expanded student grade API response with aggregation metadata.
- Restored Russian language strings as valid UTF-8.
- Fixed upgrade steps so they do not call Moodle Gradebook API while Moodle is in upgrade mode.
- Removed aggregation info notices from journal and grading pages.
- Limited grading tables and Gradebook aggregation to enrolled student users, excluding teachers and managers.
- Added normal-page Gradebook item creation for upgraded activities where the aggregate item does not exist yet.

## 0.2.2 - 2026-05-15

- Changed Moodle Gradebook sync to one aggregate grade item per journal instead of one item per lesson.
- Added an upgrade step that removes legacy per-lesson Gradebook items and recreates the aggregate item.
- Expanded private release/build instructions.

## 0.2.1 - 2026-05-15

- Improved lesson filters with explicit labels and clear/apply actions.
- Added bulk lesson deletion with confirmation.

## 0.2.0 - 2026-05-15

- Added Gradebook itemnumber mapping for Moodle 4.x settings compatibility.
- Added bulk lesson creation with weekly repeat interval.
- Added lesson filters and pagination to journal and grade matrix pages.
- Added REST function `classjournal_create_lesson`.
- Fixed nested grade form submission cleaning.
- Expanded public and private documentation.

## 0.1.1 - 2026-05-15

- Fixed grade matrix saving for nested grade/comment fields.
- Added REST lesson creation.

## 0.1.0 - 2026-05-15

- Initial activity module scaffold.
- Added lessons, grade entry, Gradebook sync, REST API, localization, and installable ZIP structure.
