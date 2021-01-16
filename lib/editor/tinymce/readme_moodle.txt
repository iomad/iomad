Description of TinyMCE library integration in Moodle
=========================================================================================

Copyright: (c) 2004-2012, Moxiecode Systems AB, All rights reserved.
License: GNU LESSER GENERAL PUBLIC LICENSE Version 2.1, February 1999

Moodle maintainer: Petr Skoda (skodak)

=========================================================================================
Upgrade procedure:

1/ extract standard TinyMCE package into lib/editor/tinymce/tiny_mce/x.y.z/
2/ bump up editor version in lib.php to match the directory name x.y.z
3/ bump up main version.php
4/ update ./thirdpartylibs.xml
5/ execute cli/update_lang_files.php and review changes in lang/en/editor_tinymce.php

Changes:
lib/editor/tinymce/tiny_mce/3.5.11/plugins/fullscreen/editor_plugin_src.js:31-33 - Added checks to see if required functions exist.
After these changes you need to use uglifier to compress the js into lib/editor/tinymce/tiny_mce/3.5.11/plugins/fullscreen/editor_plugin.js
(I used "uglifyjs editor_plugin_src.js -c -m -v -o editor_plugin.js")

Copy the moodle skin to the new version:
lib/editor/tinymce/tiny_mce/3.5.10/theme/advanced/skins/moodle/

TODO:
 * create some new automated script that sends other languages from upstream into AMOS
