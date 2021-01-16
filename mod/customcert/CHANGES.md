# Changelog

All notable changes to this project will be documented in this file. 

Note - All hash comments refer to the issue number. Eg. #169 refers to https://github.com/markn86/moodle-mod_customcert/issues/169.

## [3.4.1] - 2018-05-17
### Added
- GDPR Compliance (#189).

### Fixed
- Race condition on certificate issues in scheduled task (#173).
- Ensure we backup the 'verifyany' setting (#169).
- Fixed encoding content links used by restore (#166).

## [3.3.9] - 2017-11-13
### Added
- Added capability ```mod/customcert:verifyallcertificates``` that provides a user with the ability to verify any certificate
  on the site by simply visiting the ```mod/customcert/verify_certificate.php``` page, rather than having to go to the
  verification link for each certificate.
- Added site setting ```customcert/verifyallcertificates``` which when enabled allows any person (including users not logged in)
  to be able to verify any certificate on the site, rather than having to go to the verification link for each certificate.
  However, this only applies to certificates where ```Allow anyone to verify a certificate``` has been set to ```Yes``` in the
  certificate settings.
- You can now display the grade and date of all grade items, not just the course and course activities.
- Text has been added above the ```My certificates``` list to explain that it contains certificates that have been issued to
  avoid confusion as to why certificates may not be appearing.

### Changed
- The course full name is now used in emails.

### Fixed
- Added missing string used in course reset.

## [3.3.8] - 2017-09-04
### Added
- New digital signature element (uses existing functionality in the TCPDF library).
- Ability to duplicate site templates via the manage templates page.
- Ability to delete issued certificates for individual users on the course report page.

### Changed
- Removed usage of magic getter and abuse of ```$this->element```. The variable ```$this->element``` will still be
  accessible by any third-party element plugins, though this is discouraged and the appropriate ```get_xxx()```
  method should be used instead. Using ```$this->element``` in ```definition_after_data()``` will no longer work.
  Please explicitly set the value of any custom fields you have in the form.

### Fixed
- Added missing ```confirm_sesskey()``` checks.
- Minor bug fixes.

## [3.3.7] - 2017-08-11
### Added
- Added much needed Behat test coverage.

### Changed
- Minor language string changes.
- Made changes to the UI when editing a certificate.
  - Moved the 'Add element' submit button below the list of elements.
  - Added icon next to the 'Delete page' link.
  - Changed the 'Add page' button to a link, added an icon and moved it's location to the right.
  - Do not make all submit buttons primary. MDL-59740 needs to be applied to your Moodle install in order to notice the change.

### Fixed
- Issue where the date an activity was graded was not displaying at all.

## [3.3.6] - 2017-08-05
### Changed
- Renamed the column 'size' in the table 'customcert_elements' to 'fontsize' due to 'size' being a reserved word in Oracle.
