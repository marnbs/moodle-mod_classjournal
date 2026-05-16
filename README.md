# Class journal (`mod_classjournal`)

Class journal is a Moodle activity module for lesson-based grading. Teachers add the activity to a course, create lessons, and enter per-student grades for each lesson. Moodle Gradebook shows one aggregate grade item per journal, calculated as a sum or average depending on activity settings, and the plugin exposes Moodle External Functions for REST integrations.
Originally created for internal use but made public.

[![Moodle Plugin](https://img.shields.io/badge/Moodle-plugin-orange.svg)](https://moodle.org/plugins/mod_classjournal) [![PHP 8.1+](https://img.shields.io/badge/PHP-8.1%2B-777bb4.svg)](https://www.php.net/) [![License GPL v3+](https://img.shields.io/badge/license-GPLv3%2B-blue.svg)](LICENSE) 

[![Latest release](https://img.shields.io/github/v/release/marnbs/moodle-mod_classjournal)](https://github.com/marnbs/moodle-mod_classjournal/releases)

[![Issues](https://img.shields.io/github/issues/marnbs/moodle-mod_classjournal)](https://github.com/marnbs/moodle-mod_classjournal/issues)


## Requirements

- Moodle 4.1+
- PHP 8.1+

## Installation

Install through Moodle administration:

1. Open `Site administration > Plugins > Install plugins`.
2. Upload `classjournal.zip`.
3. Select plugin type `Activity module (mod)` if Moodle asks for it.
4. Continue the standard plugin installation and database upgrade.

Manual installation is also supported: copy the `classjournal` directory to `mod/classjournal`, then run the Moodle upgrade.

## Features

- Create, edit, delete lessons.
- Bulk-create repeated lessons every N weeks.
- Filter lessons by name and date range.
- Paginated lesson list for large journals.
- Delete multiple lessons at once with confirmation.
- Optional grade comments.
- Student view for own grades; optional setting to show all student grades.
- Gradebook sync: one Moodle grade item per journal with sum/average aggregation.
- Configurable final Gradebook maximum, for example fixed 100-point output while lessons use 5, 10, or 100 points.
- Optional handling of empty grades as zero; otherwise empty grades are ignored in totals and averages.
- REST API through Moodle Web Services.


## REST API

Enable Moodle Web Services, create a token for a user with the required capabilities, and call:

```text
/webservice/rest/server.php?wstoken=TOKEN&moodlewsrestformat=json&wsfunction=classjournal_get_lessons&cmid=123
```

Registered functions:

- `classjournal_get_lessons(cmid)`
- `classjournal_get_grades(lessonid)`
- `classjournal_get_final_grades(cmid)`
- `classjournal_create_lesson(cmid, name, description, lessondate, maxgrade)`
- `classjournal_set_grade(lessonid, userid, grade, comment)`
- `classjournal_get_student_grades(cmid, userid)`

Grades are checked against each lesson maximum and synced to Moodle Gradebook. `classjournal_get_student_grades` and `classjournal_get_final_grades` return the calculated final grade, aggregation mode, empty-grade mode, Gradebook maximum, and a human-readable aggregation description.

## Build ZIP

From the plugin directory, build the Moodle upload archive with:

```powershell
.\build.ps1
```

The script creates `classjournal.zip` with one top-level `classjournal/` folder, excludes old ZIP archives, validates required plugin files, checks the ZIP structure, and runs `php -l` when PHP is available in `PATH`.

## Translations

Translations are welcome. Add or update `lang/<languagecode>/classjournal.php`, keep all string keys aligned with `lang/en/classjournal.php`, preserve placeholders such as `{$a}`, and save files as UTF-8.

## API Example

```bash
curl "https://moodle.example.com/webservice/rest/server.php" \
  --data "wstoken=TOKEN" \
  --data "moodlewsrestformat=json" \
  --data "wsfunction=classjournal_set_grade" \
  --data "lessonid=42" \
  --data "userid=17" \
  --data "grade=8.5" \
  --data "comment=Good work"
```

## License

GPL v3 or later, matching Moodle plugin requirements.
