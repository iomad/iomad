# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [2.0.5.2] - 2025.04.17

### Changed

- Updated the copyright notices to remove Moodle, and use filter_multilang2 instead. See https://moodle.org/mod/forum/discuss.php?d=467516 for the rationale.
- Ignore phpcs codesniff that check for standard Moodle copyright boilerplate.

## [2.0.5.1] - 2024.12.12

### Fixed

- [#56] Updated Basque translations for latest changes (I forgot to do it in 2.0.5)

## [2.0.5] - 2024.11.27

### Added

- [#42] [#25] Added a setting to configure the parent languages behaviour (see README.md for additional details on the setting).

## [2.0.4] - 2024.10.18

### Fixed

- [#54] Fixed exceptions thrown when using Moodle 4.1 in some sites (thanks to Michael Milette!)

## [2.0.3] - 2024.10.12

### Fixed
- `moodle-plugin-ci validate` now passes successfully.

## [2.0.2] - 2024-10-08

### Added
- [#49] Add support to Moodle 4.5
- Added Moodle 4.4 and 4.5 to the CI test matrix

## [2.0.1] - 2024-01-30

### Added
- This CHANGELOG.
- Moodle 4.3 to the CI test matrix.

### Fixed
- [#45] Exception: Return value must be of type string, null returned

## [2.0.0] - 2022-12-17

### Added
- Early checks for "text" that cannot be filtered or is not worth filtering (e.g., non-strings, or empty strings).
- [#35] Addded GitHub Actions and information in README.md, contributed by Luca Bösch.

### Changed
- [#32] **[BREAKING CHANGE]** Updated the unit test machinery to be compatible with Moodle 3.11 and later. The new machinery is incompatible with Moodle 3.10 and later, making this a breaking change.

### Fixed
- Fixed some Moodle Code Checker and Documentation Checker warnings.

### Security
- [#39] MDL-77525 apply multilang2 before text formatting and cleaning

[UNRELEASED]: https://github.com/iarenaza/moodle-filter_multilang2/compare/2.0.1...HEAD
[2.0.1]: https://github.com/iarenaza/moodle-filter_multilang2/compare/2.0.0...2.0.1
[2.0.0]: https://github.com/iarenaza/moodle-filter_multilang2/compare/1.1.2...2.0.0
[1.1.2]: https://github.com/iarenaza/moodle-filter_multilang2/compare/1.1.1...1.1.2
[1.1.1]: https://github.com/iarenaza/moodle-filter_multilang2/compare/1.0.1...1.1.1
[1.0.5]: https://github.com/iarenaza/moodle-filter_multilang2/compare/1.0.4...1.0.5
[1.0.4]: https://github.com/iarenaza/moodle-filter_multilang2/compare/1.0.3...1.0.4
[1.0.3]: https://github.com/iarenaza/moodle-filter_multilang2/compare/1.0.2...1.0.3
[1.0.2]: https://github.com/iarenaza/moodle-filter_multilang2/compare/1.0.1...1.0.2
[1.0.1]: https://github.com/iarenaza/moodle-filter_multilang2/compare/1.0...1.0.1
[1.0]: https://github.com/iarenaza/moodle-filter_multilang2/releases/tag/1.0
[#45]: https://github.com/iarenaza/moodle-filter_multilang2/pull/45
[#39]: https://github.com/iarenaza/moodle-filter_multilang2/pull/39
[#35]: https://github.com/iarenaza/moodle-filter_multilang2/issues/35
[#32]: https://github.com/iarenaza/moodle-filter_multilang2/issues/32
