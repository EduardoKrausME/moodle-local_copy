# Changelog

This changelog documents only the two most recent published releases of `local_copy`, as verified on October 24, 2026. Each entry is based on the published Git tag compared with its immediately preceding release tag. The existing `CHANGES-1.2.0.md` is retained separately.

Changes committed after `1.2.10`, including the October 20, 2026 `Mustache` and `Build` commits, are not included in the released-version entries below.

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
