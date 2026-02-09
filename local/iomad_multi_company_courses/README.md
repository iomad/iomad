# IOMAD Multi-Company Courses Plugin

This plugin extends IOMAD functionality by allowing administrators to assign courses to multiple companies at once based on company code patterns.

## Features

- **Pattern-based Company Search**: Search for companies using patterns in their company codes
- **Bulk Course Assignment**: Assign multiple courses to multiple companies in one operation
- **Enhanced Course Display**: Shows course creation date alongside course names
- **Smart Default Sorting**: Courses sorted by creation date (newest first) by default for better usability
- **Flexible Sorting**: Sort courses alphabetically by name or by creation date with explicit button
- **Large Course Selector**: Spacious course selection list (20 rows) for easier browsing
- **Distinctive Icon**: Uses FontAwesome `fa-cubes` icon with left-pointing arrow to represent multiple companies
- **Case-insensitive Search**: Find companies regardless of case
- **Safety Filters**: Only includes active (non-suspended, non-terminated) companies
- **Clear Feedback**: Shows matching companies before assignment and confirms successful operations
- **Error Handling**: Comprehensive error handling with detailed feedback
- **Security**: Proper capability checks and session key validation
- **Security**: Proper capability checks and session key validation

## Installation

1. Download or clone this plugin to your Moodle installation
2. Place the plugin files in `local/iomad_multi_company_courses/`
3. Visit the Moodle admin notifications page to complete the installation
4. The plugin will automatically integrate with the IOMAD menu system

## Requirements

- Moodle 4.1 or higher
- IOMAD local plugin
- IOMAD Company Admin block
- Companies must have company codes populated for search functionality

## Usage

1. Navigate to the IOMAD Company Admin interface
2. Go to the "Course Admin" tab
3. Click on "Assign courses to multiple companies"
4. Enter a pattern to search for in company codes (e.g., "Platin" to find codes like "000376:Platin:v3:DE_du+EN:")
5. Click "Search companies" to find matching companies
6. Review the list of matching companies
7. Select the courses you want to assign
8. Click "Assign selected courses to all matching companies"

## Capabilities

The plugin defines two capabilities:

- `local/iomad_multi_company_courses:assign` - Allows assigning courses to multiple companies
- `local/iomad_multi_company_courses:view` - Allows viewing the multi-company assignment interface

By default, these capabilities are granted to users with the manager archetype and inherit permissions from the existing IOMAD company course capabilities.

## Technical Details

### Database Tables Used

- `company` - For searching companies by code pattern
- `company_course` - For storing course assignments
- `company_shared_courses` - For handling shared course assignments
- `iomad_courses` - For checking if courses are shared

### Key Classes

- `\local_iomad_multi_company_courses\forms\multi_company_courses_form` - Main form class
- Uses existing IOMAD classes like `company`, `potential_company_course_selector`

### Security

- Capability checks ensure only authorized users can access the functionality
- Session key validation prevents CSRF attacks
- Input validation and sanitization for all user inputs
- Error handling prevents information disclosure

## Uninstallation

To uninstall the plugin:

1. Go to Site Administration > Plugins > Plugins overview
2. Find "IOMAD Multi-Company Courses" in the list
3. Click "Uninstall"
4. Follow the prompts to complete uninstallation

The plugin does not create any database tables, so uninstallation is clean and safe.

## Support

This plugin is provided as-is. For issues or feature requests, please contact the plugin maintainer.

## License

This plugin is licensed under the GNU GPL v3 or later, the same as Moodle.

## Changelog

### Version 1.0.0 (2025-09-23)
- Initial release
- Pattern-based company search functionality
- Bulk course assignment to multiple companies
- Integration with IOMAD menu system
- Comprehensive error handling and validation