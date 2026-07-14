# Security Policy

Thank you for helping keep **`mod_classjournal`** and its users safe. This
plugin handles student grade data and exposes Moodle Web Service functions, so
security reports are taken seriously.

## Supported Versions

Security fixes are provided for the latest stable release. Older releases are
not patched — please upgrade before reporting an issue against them.

| Version | Supported          |
| ------- | ------------------ |
| 1.0.x   | :white_check_mark: |
| < 1.0   | :x:                |

Because this is a Moodle activity module, "supported" also assumes a supported
Moodle environment: **Moodle 4.1+** and **PHP 8.1+**. Issues that only reproduce
on end-of-life Moodle or PHP versions are generally out of scope.

## Reporting a Vulnerability

**Please do not open a public issue, pull request, or discussion for security
problems.** Public disclosure before a fix is available puts every Moodle site
running this plugin at risk.

Report privately through one of these channels:

1. **GitHub Private Vulnerability Reporting** (preferred) — go to the
   [Security tab](https://github.com/marnbs/moodle-mod_classjournal/security)
   and choose **"Report a vulnerability"**. This opens a private advisory
   visible only to you and the maintainers.
2. **Email** — send details to `rbk112v@gmail.com`.

To help triage quickly, please include:

- The plugin version and Moodle/PHP versions affected.
- A description of the vulnerability and its impact.
- Step-by-step reproduction instructions or a proof of concept.
- The affected file(s) or web service function, if known
  (e.g. `classjournal_set_grade`, `ajax.php`, `export.php`).
- Any suggested remediation, if you have one.

## What to Expect

- **Acknowledgement:** within **5 business days** of your report.
- **Initial assessment:** a severity evaluation and next steps within
  **7 business days**.
- **Fix & release:** timelines depend on severity and complexity; I aim to
  ship a patch for confirmed high-severity issues as quickly as is reasonably
  possible.
- **Coordinated disclosure:** we ask that you keep the report confidential until
  a fixed release is published. I'm happy to coordinate a disclosure date with
  you.

## Scope

### In scope

Vulnerabilities in the plugin's own code, including but not limited to:

- SQL injection, XSS, or CSRF in `view.php`, `index.php`, `ajax.php`,
  `import.php`, `export.php`, `grades.php`, or form handling in `mod_form.php`.
- Broken access control - missing or incorrect **capability checks** or
  `require_login` / context checks that let a user read or modify grades they
  should not.
- Web Service / REST API issues in the external functions
  (`classjournal_get_grades`, `classjournal_set_grade`,
  `classjournal_get_final_grades`, etc.), including grade tampering, leaking
  another user's grades, or bypassing per-lesson maximum validation.
- Insecure handling of grade data during Gradebook sync, import, or export.
- Information disclosure of student grades or comments to unauthorized users.

### Out of scope

- Vulnerabilities in **Moodle core** or in other plugins — report those through
  the [official Moodle security process](https://moodledev.io/general/development/policies/security).
- Misconfiguration of the hosting site, web server, or Moodle Web Services /
  token management by the administrator (e.g. handing a token to an
  under-privileged review, or over-provisioned capabilities assigned by an
  admin).
- Social engineering, physical attacks, or issues requiring an already
  compromised admin account.
- Missing security hardening that has no demonstrable impact (theoretical
  reports without a working scenario).

## A Note on Web Service Tokens

This plugin's REST API relies on standard Moodle Web Service tokens and
capability checks. Tokens are as powerful as the capabilities of the user they
belong to — treat them like passwords, scope them to the minimum required
capabilities, and rotate them if leaked. Token leakage caused by site
configuration is an administrator responsibility, not a plugin vulnerability,
but if you find a way for the plugin to **expose** a token or to act **beyond**
the token owner's capabilities, that is very much in scope — please report it.

## Recognition

I'm grateful to everyone who reports responsibly. With your permission, we're
happy to credit you in the release notes or `CHANGELOG.md` for a valid report.
Let me know how you'd like to be credited (or if you prefer to remain anonymous).

---

*This project is licensed under GPL v3.0. Security reports and fixes are handled
under the same license.*
