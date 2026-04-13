# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.0] - 2024-05-20

### Added
- Layer 1: Automatic textdomain reload using the `wpml_locale` filter to resolve the correct locale dynamically.
- Layer 2: Integration with WPML String Translation as a fallback (`apply_filters('wpml_translate_single_string', ...)`).
- Layer 3: Hardcoded emergency translations for ~30 languages of the most critical messages.
- Automatic support for any current or future language without needing to update the code.

### Changed
- Complete refactoring to a static class `GF_WPML_Validation_Fix`.
- Performance optimization with internal caching to avoid multiple WPML calls per request.
- Changed filter priorities to ensure execution after GFML.

### Fixed
- Issue where default Gravity Forms validation messages ("This field is required", etc.) were displayed in English in secondary languages.
- Compatibility with locales that do not have an official `.mo` file in Gravity Forms.

## [1.0.0] - 2023-11-15

### Added
- Initial version of the script to force the translation of validation messages.
