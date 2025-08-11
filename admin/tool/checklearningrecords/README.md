# IOMAD

<p align="center"><a href="https://www.iomad.org" target="_blank" title="IOMAD Website">
  <img src="https://avatars.githubusercontent.com/u/5493428?v=4" alt="The IOMAD Logo">
</a></p>

The Admin tool Check learning records plugin allows site administrators to check and fix all of the stored records used in the IOMAD reports.

This plugin is part of the IOMAD suite of plugins. It must be installed with all other plugins from the suite and the core code patch must also be applied in order for these to work.  

## Installing via uploaded ZIP file ##

Log in to your Moodle site as an admin and go to _Site administration > Plugins > Install plugins_.
Upload the ZIP file with the plugin code. You should only be prompted to add extra details if your plugin type is not automatically detected.
Check the plugin validation report and finish the installation.

## Installing manually ##

The plugin can be also installed by adding the contents of this directory to

    {your/moodle/dirroot}/admin/tool/checklearningrecords

Afterwards, log in to your Moodle site as an admin and go to _Site administration > Notifications_ to complete the installation.

Alternatively, you can run

    $ php admin/cli/upgrade.php

to complete the installation from the command line.

## License ##
2010+ e-Learn Design Ltd. https://www.e-learndesign.co.uk
IOMAD is a registered trademark in the UK belonging to Derick Turner 
