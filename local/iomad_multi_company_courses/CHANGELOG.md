# Changelog

All notable changes to the IOMAD Multi-Company Courses plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.3.1] - 2025-09-24

### Changed
- **Course Sorting**: Replaced creation date with modification date (newest first) which is more reliable.

## [1.3.0] - 2025-09-24

### Added
- **Show All Courses**: Course selector shows ALL available courses regardless of current company assignments (perfect for bulk operations).
- **Smart Automatic Limitation**: Automatically limits course display to improve performance with large catalogs while preserving full functionality.
- **Course Sorting**: Sort courses by name (A–Z) or creation date (newest first) with persistent user preferences.
- **Enhanced Course Display**: Course lists show creation dates alongside course names for easier identification.
- **FontAwesome Icons**: Consistent iconography throughout the interface using FontAwesome.
- **Large Course Selector**: Enhanced course selector with increased height for better usability.
- **Show All Courses**: Course selector can optionally show ALL available courses regardless of current company assignments — useful for bulk assignment workflows.
- **IOMAD 4.4**: Compatibility updates for IOMAD 4.4.

### Fixed
- **Course Assignment Logic**: Fixed department/company assignment logic to match default IOMAD behavior exactly.
- **Database Consistency**: Proper handling of both `company_course` and `company_shared_courses` tables to ensure assignments appear where expected.
- **Form Structure**: Removed problematic HTML closing tags that were breaking form submission in some setups.
- **Duplicate Elements**: Eliminated duplicate hidden fields and redundant validation checks that caused unexpected behavior.
- **Browser Warnings**: Addressed "Do you want to leave this page?" warnings triggered by sort changes or form edits in modern browsers.
- **Code Syntax**: Fixed various PHP syntax errors and cleaned up duplicate code blocks.

### Technical Details
- Uses existing Moodle/IOMAD database tables for seamless integration.
- Implements proper capability checks and security measures.
- Follows Moodle coding standards and best practices.
- Standard "too many results" behavior is used when the course catalog is large (users can search to narrow results).
- Course assignments now appear correctly in both IOMAD course settings and standard course assignment forms.

## [1.2.0] - 2025-09-24

### Added
- **Larger Course Selector**: Course selection list is now twice as high (20 rows instead of 10) for better usability

### Changed
- **Default Sort Order**: Courses now sort by creation date (newest first) by default instead of alphabetical
- **Sort Interface**: Replaced auto-submit dropdown with explicit "Change sort order" button to eliminate browser warnings

### Fixed
- **Browser Warning**: Eliminated "Do you want to leave this page?" warning when changing sort order
- **User Experience**: More predictable and professional form behavior

## [1.1.4] - 2025-09-23

### Fixed
- **Browser Warning**: Fixed browser "unsaved changes" warning when changing sort order
- **User Experience**: Sort dropdown now changes smoothly without browser confirmation dialogs

## [1.1.3] - 2025-09-23

### Fixed
- **Critical Bug**: Fixed "implode(): Argument #1 ($array) must be of type array, string given" error
- **Undefined Variable**: Fixed undefined variable `$companylist` error when searching companies
- **Form Structure**: Fixed missing company list creation logic in form definition
- **Code Structure**: Fixed syntax errors and duplicate code sections

## [1.1.2] - 2025-09-23

### Changed
- **FontAwesome Icon**: Updated to use `fa-cubes` icon representing multiple companies/entities
- **Arrow Direction**: Changed arrow direction from right to left to match standard IOMAD course assignment button
- **Icon Consistency**: Uses standard FontAwesome icons for better consistency and maintenance

### Fixed
- **Code Syntax**: Fixed duplicate method declarations and syntax errors

### Added
- Initial release of the IOMAD Multi-Company Courses plugin
- Pattern-based company search functionality using company codes
- Bulk course assignment to multiple companies

## [1.1.1] - 2025-09-23

### Fixed
- **Sorting Persistence**: Fixed issue where changing sort order would reset the form and lose company search results
- **Auto-Submit Sorting**: Replaced separate "Change sorting" button with automatic form submission when sort option changes

## [1.1.2] - 2025-09-23

### Changed
- **Custom Icon**: Replaced single company icon with custom multi-company icon showing three buildings
- **Arrow Direction**: Changed arrow direction from right to left to match standard IOMAD course assignment button
- **Icon Style**: Updated to use fa-sitemap FontAwesome icon representing multiple connected entities

### Fixed
- **Code Syntax**: Fixed duplicate method declarations and syntax errors

- **State Preservation**: Form now properly maintains company search results and sort preferences across interactions

### Improved
- Better user experience with seamless sorting that doesn't require additional button clicks
- Form state is fully preserved when changing sort options

## [1.1.0] - 2025-09-23

### Added
- **Enhanced Course Display**: Course creation date now shown alongside course names
- **Flexible Sorting Options**: Added ability to sort courses by name (A-Z) or creation date (newest first)
- **Sort Persistence**: Sort preference is maintained across form interactions
- **Enhanced Course Selector**: Custom course selector with extended functionality

### Improved
- Better user experience with more informative course listings
- Improved course management with chronological sorting option
- Case-insensitive search with partial matching
- Integration with IOMAD menu system under Course Admin tab
- Comprehensive error handling and validation
- Security features including capability checks and CSRF protection
- Privacy compliance with GDPR null provider
- Support for shared courses and department-specific assignments
- Clear user feedback with success/error notifications
- Documentation and installation scripts

### Features
- Search companies by entering patterns in their company codes
- Assign multiple courses to multiple matching companies in one operation
- Only includes active companies (non-suspended, non-terminated)
- Shows matching companies before assignment for confirmation
- Detailed success feedback showing number of courses and companies affected
- Proper integration with existing IOMAD course assignment logic

### Technical Details
- Uses existing IOMAD database tables (no new tables required)
- Follows Moodle coding standards and best practices
- Implements proper capability system for access control
- Includes privacy provider for GDPR compliance
- Clean uninstallation with no database residue

### Requirements
- Moodle 4.1 or higher
- IOMAD local plugin
- IOMAD Company Admin block
- Companies must have company codes populated for search functionality