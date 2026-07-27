# Changelog


#### v1.2.0 2026-07-27
- Group aware lessons: a lesson can be restricted to one course group instead
  of all participants. Only members of that group see it, are graded on it,
  and get its calendar event (published as a group event); lessons of other
  groups are ignored when a student's journal total is calculated. New
  `groupid` column on `classjournal_lessons`.
- The activity now declares `FEATURE_GROUPS`, so the common module settings
  offer a group mode. In separate groups mode a teacher without
  `moodle/site:accessallgroups` only sees, grades, and assigns lessons for
  their own groups, and only sees the students sharing a group with them on
  the grades page, in the Excel export, and during import.
- The group selector on the lesson form, and the group column in the lesson
  list, appear only when the course has groups, so journals in courses without
  groups are unchanged. The grade grid header names the group of a restricted
  lesson, and cells for students outside that group are rendered as `n/a`
  instead of grade inputs.
- Grades for a lesson can no longer be written for a student outside its
  group, whether through the grades page, the inline autosave endpoint, the
  Excel import, or `set_grade`. Editing, deleting, reading, and grading a
  lesson of another group is refused for teachers limited to their own groups,
  including direct access by lesson id.
- Web services: `create_lesson` and `update_lesson` accept `groupid` and
  reject groups the caller may not assign; `get_lessons` returns `groupid` and
  hides lessons of groups the caller cannot see; `get_student_grades` reports
  only the lessons that apply to the student, so its total matches the
  Gradebook. Backup, restore (with group id mapping), and Excel export/import
  follow the same rule.
- Added the `modulename_help` description.
- README: fixes and improvements.

#### v1.1.0 2026-07-20
- Web services: added `update_lesson` and `delete_lesson`. `create_lesson` now
  accepts an optional idempotency key (`clientrequestid`); a retried request
  with the same key returns the existing lesson instead of creating a
  duplicate. New nullable `clientrequestid` column with a unique per-journal
  index.
- Declared `FEATURE_MOD_PURPOSE` (collaboration) so the activity shows in the
  right activity-chooser category, and refreshed the monochrome icon /
  monologo SVGs.

#### v1.0.0 2026-07-14
- Redesigned the lesson list: a toolbar with the
  add button on the left and search plus period filters (All / All past /
  Month / Week / Today) on the right, dates shown as `15.07.26 (Wed)`, icon
  actions (grade / edit / delete), a select-all checkbox, and a bulk action
  dropdown with an OK button under the table.
- The grades page got the same toolbar (export/import, search, period
  filters) instead of the old filter form.
- Optional lesson time: a start and end time can be set per lesson, shown in
  the lesson list, the grade grid header, and the student report; calendar
  events use the exact start time and duration. New nullable `starttime` /
  `endtime` columns (seconds from midnight).
- New bulk action: change the date of the selected lessons in one step
  (lesson times are kept).
- Lesson deletion is confirmed in a modal dialog; without JavaScript the old
  confirmation page used.
- Student report: summary cards (journal total with a progress bar, graded
  lesson count) and a table with dates, times, and highlighted grades.
- Period filters are calculated in the user's timezone.
- Web services: `create_lesson` accepts optional `starttime` / `endtime`;
  `get_lessons` returns them.
- Backup now includes the previously missed `scaleid` and `calendarevents`
  fields along with the new time fields.
- Removed the bundled Russian language pack; translations are maintained in
  AMOS.

#### v0.4.0 2026-06-26
- Batch grade saving: the grades page writes all changed cells in one pass and
  syncs the Gradebook once instead of once per cell; unchanged and empty cells
  are skipped.
- Per-lesson grading on points or a Moodle scale. The Gradebook keeps a single
  numeric total; a scale value is converted to its share of the scale.
- Lesson calendar events with a per-journal setting and a global default, so
  the dates can be turned off to avoid clashing with attendance plugins.
- Excel grade export (.xlsx) and Excel/CSV import. Importing grades preserves
  existing comments.
- Sortable lesson list using `table_sql` (sortable columns and paging).
- Grade-grid UX: per-cell AJAX autosave, "fill column" for empty cells, sticky
  header and first column, and colour indication for low/missing grades.
- Converted the add/edit lesson form to a proper `moodleform`.
- Added PHPUnit tests (library, privacy provider) and a test data generator.
- Fixed a stray character in the English language file that broke loading.

#### v0.3.2 2026-06-13
- Implemented the Moodle Backup/Restore API (`backup/moodle2`), so courses
  containing a class journal can be backed up and restored, including lessons
  and (when user data is included) grades. The aggregate Gradebook item is
  recreated on restore. (#3)
- Added the `course_module_viewed` event and a `classjournal_view()` helper so
  activity access is logged and completion-on-view is supported. (#4)
- Added the standard Moodle boilerplate license header and `@package`,
  `@copyright`, and `@license` doc markers to all source files. (#5)
- Fixed completion conditions: enabled view tracking
  (`FEATURE_COMPLETION_TRACKS_VIEWS`) so "Add requirements" now offers a valid
  condition instead of an empty, unsaveable list. (#6)

#### v0.3.1 2026-05-15
- Public release