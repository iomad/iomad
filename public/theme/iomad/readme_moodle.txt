Description of Twitter bootstrap import into Moodle

Twitter bootstrap
-----------------

Sass:
This theme uses Bootstrap frontend toolkit.
The Bootstrap repository is available on:

https://github.com/twbs/bootstrap

To update to the latest release of twitter bootstrap:

* download bootstrap source files to your home folder
* remove folder theme/iomad/scss/bootstrap
* copy the scss files from ~/bootstrap/scss to theme/iomad/scss/bootstrap
* update ./thirdpartylibs.xml
* follow the instructions in admin/tool/component_library/readme_moodle.txt to update the Bootstrap documentation there.

Javascript:

* remove folder theme/iomad/amd/src/bootstrap
* copy the js files from ~/bootstrap/js/src to theme/iomad/amd/src/bootstrap (including the subfolder)
* copy index.js from ~/bootstrap/js to theme/iomad/amd/src
* edit theme/iomad/amd/src/index.js and update import path (src -> bootstrap)
* Moodle core includes the popper.js library, make sure each of the new Bootstrap js files
includes the 'core/popper2' library instead of 'popperjs'. For current version these files were: tooltip.js and dropdown.js
* Fix all IOMADrap JS files import paths to use the correct AMD module names. For example, change:
    import Manipulator from '../dom/manipulator.js'
        to:
    import Manipulator from '../dom/manipulator'
* update ./thirdpartylibs.xml
* run "grunt ignorefiles" to prevent linting errors appearing from the new Bootstrap js files.
* in folder theme/iomad run "grunt amd" to compile the bootstrap JS
* in folder theme/iomad run "grunt css" to compile scss
