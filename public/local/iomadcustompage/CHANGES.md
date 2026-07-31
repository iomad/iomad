# Changelog

## [1.0.1] - 2026-05-01

### Fixed
- Primary navigation now appears on admin/edit pages via `before_http_headers` hook
- Fixed `CONTEXT_CUSTOMPAGE` undefined constant error in namespaced context class
- Fixed `$PAGE->context` ordering in `view.php` to prevent filter warnings
- Fixed missing hook registrations in `db/hooks.php`
- Removed deprecated legacy callback causing Moodle 4.4+ warnings

## [1.0.0] - 2026-03-27

Initial stable release targeting Moodle 4.5+.

### Added
- Hierarchical custom page management with container and content page types
- IOMAD Custom context (CONTEXT_CUSTOMPAGE) for full Moodle block support
- 5 audience types for access control: allusers, guests, admins, systemrole, manual
- Primary navigation integration via custom menu items
- Vancode-based sortthread ordering for efficient hierarchical sorting
- Report Builder integration with pages_list and page_access_list system reports
- 5 AJAX web services for page and audience management
- 7 event classes for page and audience lifecycle tracking
- Inline editing for page names, titles, and audience headings
- Privacy API implementation (GDPR compliant)
- Moodle 4.3+ hooks support with legacy callback fallback
- Caching infrastructure for page data and audiences
- PHPUnit test suite and Behat acceptance tests
