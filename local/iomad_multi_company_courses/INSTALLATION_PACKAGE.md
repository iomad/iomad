# IOMAD Multi-Company Courses Plugin - Installation Package

## Overview

This is a complete, independent Moodle plugin that extends IOMAD functionality by allowing administrators to assign courses to multiple companies at once based on company code patterns.

## Package Contents

```
local/iomad_multi_company_courses/
├── version.php                                    # Plugin version and dependencies
├── index.php                                      # Main interface page
├── README.md                                      # Comprehensive documentation
├── CHANGELOG.md                                   # Version history
├── install.sh                                     # Installation helper script
├── verify_plugin.php                             # Plugin structure verification
├── classes/
│   ├── forms
        └── multi_company_courses_form.php            # Main form class
│   └── privacy/
│       └── provider.php                          # GDPR compliance
├── db/
│   ├── access.php                                 # Capability definitions
│   ├── install.xml                               # Database schema (empty)
│   └── iomadmenu.php                             # IOMAD menu integration
└── lang/
    └── en/
        └── local_iomad_multi_company_courses.php  # English language strings
```

## Key Features

✅ **Pattern-based Company Search**: Search for companies using patterns in their company codes  
✅ **Bulk Course Assignment**: Assign multiple courses to multiple companies in one operation  
✅ **Case-insensitive Search**: Find companies regardless of case  
✅ **Safety Filters**: Only includes active (non-suspended, non-terminated) companies  
✅ **Clear Feedback**: Shows matching companies before assignment and confirms successful operations  
✅ **Error Handling**: Comprehensive error handling with detailed feedback  
✅ **Security**: Proper capability checks and session key validation  
✅ **GDPR Compliant**: Includes privacy provider for data protection compliance  
✅ **Clean Installation/Uninstallation**: No database tables created, clean removal  

## Installation Instructions

1. **Copy Plugin Files**: Place the entire `local/iomad_multi_company_courses/` directory in your Moodle installation
2. **Install Plugin**: Visit Site Administration > Notifications as an administrator
3. **Complete Installation**: Follow the prompts to complete the plugin installation
4. **Access Feature**: Navigate to IOMAD Company Admin > Course Admin > "Assign courses to multiple companies"

## Requirements

- Moodle 4.1 or higher
- IOMAD local plugin installed and configured
- IOMAD Company Admin block installed
- Companies must have company codes populated for search functionality
- Users need `local/iomad_multi_company_courses:assign` capability

## Usage Example

1. Enter "Platin" in the company code search field
2. System finds all companies with codes like "000376:Platin:v3:DE_du+EN:"
3. Select courses to assign from the available courses list
4. Click "Assign selected courses to all matching companies"
5. System confirms successful assignment with detailed feedback

## Technical Details

- **No Database Changes**: Uses existing IOMAD tables only
- **Secure**: Implements proper capability checks and CSRF protection
- **Extensible**: Clean code structure allows for future enhancements
- **Standards Compliant**: Follows Moodle coding standards and best practices
- **Maintainable**: Well-documented code with clear separation of concerns

## Support and Maintenance

This plugin is designed to be:
- **Self-contained**: No external dependencies beyond IOMAD
- **Maintainable**: Clean, well-documented code
- **Upgradeable**: Version management system in place
- **Removable**: Clean uninstallation with no residual data

## Version Information

- **Version**: 1.0.0
- **Release Date**: 2024-12-26
- **Maturity**: Stable
- **License**: GNU GPL v3 or later

This plugin provides a professional, production-ready solution for bulk course assignment in IOMAD environments.