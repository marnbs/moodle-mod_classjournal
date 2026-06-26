# Changelog


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