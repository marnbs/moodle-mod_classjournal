# Class journal (`mod_classjournal`)

Class journal is a Moodle activity module for lesson-based grading. Teachers add the activity to a course, create lessons, and enter per-student grades for each lesson. Moodle Gradebook shows one aggregate grade item per journal, calculated as a sum or average depending on activity settings, and the plugin exposes Moodle External Functions for REST integrations.

[![Moodle Plugin](https://img.shields.io/badge/Moodle-plugin-orange.svg)](https://moodle.org/plugins/mod_classjournal) [![PHP 8.1+](https://img.shields.io/badge/PHP-8.1%2B-777bb4.svg)](https://www.php.net/) [![License GPL v3+](https://img.shields.io/badge/license-GPLv3%2B-blue.svg)](LICENSE) [![Latest release](https://img.shields.io/github/v/release/marnbs/moodle-mod_classjournal)](https://github.com/marnbs/moodle-mod_classjournal/releases) [![Issues](https://img.shields.io/github/issues/marnbs/moodle-mod_classjournal)](https://github.com/marnbs/moodle-mod_classjournal/issues)



## Requirements

- Moodle 4.1+
- PHP 8.1+


## Features

### Lessons

- Create, edit, delete lessons, with an optional start and end time per lesson.
- Bulk-create repeated lessons every N weeks.
- Change the date of several lessons at once, or delete them in one confirmed step.
- Filter lessons by name and period (all, past, month, week, today), with a paginated list for large journals.
- Publish each lesson date to the course calendar, switchable per journal and site-wide.

### Grading

- Grade each lesson on points or on a Moodle scale, with optional per-grade comments.
- Grade grid with colour indication, AJAX autosave, and a per-column fill button.
- Export the grid to Excel and re-import it to update grades in bulk.
- Gradebook sync: one Moodle grade item per journal with sum/average aggregation.
- Configurable final Gradebook maximum, for example fixed 100-point output while lessons use 5, 10, or 100 points.
- Optional handling of empty grades as zero; otherwise empty grades are ignored in totals and averages.

### Visibility

- Group aware lessons: restrict a lesson to one course group, so only its members see it, are graded on it, and get its calendar event; lessons of other groups do not affect a student's total.
- Standard Moodle group modes: in separate groups mode a teacher works only with their own groups.
- Student view for own grades; optional setting to show all student grades.

### Integration

- REST API through Moodle Web Services.
- Backup and restore, including lesson group assignments.
- Privacy API support for the grades and comments the plugin stores.

## Groups

Lessons can target a single course group. The group selector appears in the lesson form only when the course has groups; a journal in a course without groups looks and behaves exactly as before.

A lesson set to a group is visible only to that group's members, its calendar event is a group event rather than a course event, and students outside the group are neither graded on it nor affected by it in their journal total. Setting the activity to **Separate groups** additionally limits teachers without `moodle/site:accessallgroups` to the lessons and students of their own groups.


## Capabilities

| Capability | Default roles | Grants |
| --- | --- | --- |
| `mod/classjournal:addinstance` | Editing teacher, Manager | Add the activity to a course |
| `mod/classjournal:view` | Student, Non-editing teacher, Editing teacher, Manager | Open the journal and see own grades |
| `mod/classjournal:manage` | Editing teacher, Manager | Create, edit, and delete lessons |
| `mod/classjournal:grade` | Editing teacher, Manager | Enter grades, import and export the grid |
| `mod/classjournal:viewallgrades` | Non-editing teacher, Editing teacher, Manager | See every student's grades |


## REST API

Enable Moodle Web Services, create a token for a user with the required capabilities, and call:

```text
/webservice/rest/server.php?wstoken=TOKEN&moodlewsrestformat=json&wsfunction=classjournal_get_lessons&cmid=123
```

Registered functions, with the capability each one requires:

| Function | Parameters | Capability |
| --- | --- | --- |
| `classjournal_get_lessons` | `cmid` | `view` |
| `classjournal_get_grades` | `lessonid` | `viewallgrades` |
| `classjournal_get_final_grades` | `cmid` | `viewallgrades` |
| `classjournal_get_student_grades` | `cmid`, `userid` | `view` |
| `classjournal_create_lesson` | `cmid`, `name`, `description`, `lessondate`, `maxgrade`, `starttime`, `endtime`, `clientrequestid`, `groupid` | `manage` |
| `classjournal_update_lesson` | `lessonid`, plus any of `name`, `description`, `lessondate`, `maxgrade`, `starttime`, `endtime`, `groupid` | `manage` |
| `classjournal_delete_lesson` | `lessonid` | `manage` |
| `classjournal_set_grade` | `lessonid`, `userid`, `grade`, `comment` | `grade` |

Notes:

- `starttime` and `endtime` are seconds from midnight; omit both for a lesson without a time.
- `groupid` restricts the lesson to one course group, `0` means all participants. Callers may only use groups they are allowed to see.
- `clientrequestid` is an idempotency key: repeating a `classjournal_create_lesson` call with the same key returns the existing lesson instead of creating a duplicate.
- Every parameter of `classjournal_update_lesson` except `lessonid` is optional and leaves the stored value unchanged when omitted.
- `classjournal_get_lessons` and `classjournal_get_student_grades` return only the lessons the target user may see, so a student's total matches the Gradebook.

Grades are checked against each lesson maximum and synced to Moodle Gradebook. `classjournal_get_student_grades` and `classjournal_get_final_grades` return the calculated final grade, aggregation mode, empty-grade mode, Gradebook maximum, and a human-readable aggregation description.

### Examples

Grade one student on a lesson:

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

Create a lesson for one group only, running from 16:00 to 17:30:

```bash
curl "https://moodle.example.com/webservice/rest/server.php" \
  --data "wstoken=TOKEN" \
  --data "moodlewsrestformat=json" \
  --data "wsfunction=classjournal_create_lesson" \
  --data "cmid=123" \
  --data "name=Lab session" \
  --data "lessondate=1774000000" \
  --data "maxgrade=10" \
  --data "starttime=57600" \
  --data "endtime=63000" \
  --data "groupid=7"
```

## Build ZIP

After modifying plugin yourself, you can build the Moodle upload archive with:

```powershell
.\build.ps1
```

The script creates `classjournal.zip`, excludes old ZIP archives, validates required plugin files, checks the ZIP structure, and runs `php -l` when PHP is available.

## Translations

This plugin is translated using the Moodle AMOS tool. If you want to contribute to the translation, please visit the [Moodle AMOS](https://lang.moodle.org/) page.

## License

GPL v3.0
