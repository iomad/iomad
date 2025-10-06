# IOMAD

<p align="center"><a href="https://www.iomad.org" target="_blank" title="IOMAD Website">
  <img src="https://avatars.githubusercontent.com/u/5493428?v=4" alt="The IOMAD Logo">
</a></p>

The local IOMAD report emails plugin provides information on scheduled and sent emails which use the IOMAD email templates.

Part of the IOMAD suite of plugins, enhancing the core Moodle feature set with multi-tenant functionalities.

IOMAD plugins are interdependent, so all of them need to be installed. IOMAD also requires Moodle core code changes to
support the multi-tenancy functions. Patches for this, and installation instructions, can be found here:
https://github.com/iomad/moodle-core_patch

More information on the IOMAD suite of plugins is available in the description of the main plugin: https://moodle.org/plugins/local_iomad

## Installing via uploaded ZIP file ##

Log in to your Moodle site as an admin and go to _Site administration > Plugins > Install plugins_.
Upload the ZIP file with the plugin code. You should only be prompted to add extra details if your plugin type is not automatically detected.
Check the plugin validation report and finish the installation.

## Installing manually ##

The plugin can be also installed by adding the contents of this directory to

    {your/moodle/dirroot}/local/report_emails

Afterwards, log in to your Moodle site as an admin and go to _Site administration > Notifications_ to complete the installation.

Alternatively, you can run

    $ php admin/cli/upgrade.php

to complete the installation from the command line.

## License ##

2010+ e-Learn Design Ltd. https://www.e-learndesign.co.uk
IOMAD is a registered trademark in the UK belonging to Derick Turner 
