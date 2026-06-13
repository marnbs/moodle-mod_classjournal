# Changelog


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