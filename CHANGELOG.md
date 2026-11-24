# Changelog

Published release entries are based on verified GitHub tags and publication dates. Pending changes are identified separately. The existing CHANGES-1.2.0.md is retained separately.

## Unreleased (planned 1.2.12)

- Check whether the optional groups setting exists before accessing it during activity restoration.
- Preserve the existing recovery and cleanup flow when optional settings or controller cleanup fail, with developer-level diagnostics through Moodle's debugging API.
- Removed blanket coding-standard suppressions from these exception handlers.
- Build: 2026112401.

**Changes since last published release:** [1.2.11...master](https://github.com/EduardoKrausME/moodle-local_copy/compare/1.2.11...master)

## [1.2.11] - 2026-11-24

- Removed empty Mustache example contexts from the clipboard and paste dialog templates and refreshed the compiled AMD modules.
- Added a security reporting policy and serialized the release workflow.

**Full comparison:** [1.2.10...1.2.11](https://github.com/EduardoKrausME/moodle-local_copy/compare/1.2.10...1.2.11)

## [1.2.10] - 2026-10-15

### Changed
- Updated the plugin image asset `pix/icon.png`.
- Updated the plugin version and release metadata for `1.2.10` in `version.php`.

No other source files changed between these published tags.

**Full comparison:** [1.2.9...1.2.10](https://github.com/EduardoKrausME/moodle-local_copy/compare/1.2.9...1.2.10)

## [1.2.9] - 2026-10-15

### Fixed
- Added an explicit `use core\hook\output\before_standard_head_html_generation;` import in `classes/core_hook_output.php`, resolving the output-hook class reference in that namespace.

### Release metadata
- Updated the plugin version and release metadata for `1.2.9` in `version.php`.
- Normalized spacing of the existing `$plugin->requires` and `$plugin->maturity` assignments in the historical version change.

**Full comparison:** [1.2.8...1.2.9](https://github.com/EduardoKrausME/moodle-local_copy/compare/1.2.8...1.2.9)
