# IOMAD

<p align="center"><a href="https://www.iomad.org" target="_blank" title="IOMAD Website">
  <img src="https://avatars.githubusercontent.com/u/5493428?v=4" alt="The IOMAD Logo">
</a></p>

The IOMAD training event activity allows for users to book onto a face to face training event which can be held either at a physical or virtual location. Training locations
as defined and then used for the activity, which can be self sign-up, require one or more managers approval or signed up by a manager. The activity has an optional waiting room,
reminders and can be set so that only one activvity can be selected by a user within a course.

This plugin is part of the IOMAD suite of plugins. It must be installed with all other plugins from the suite and the core code patch must also be applied in order for these to work.  

## Installing via uploaded ZIP file ##

Log in to your Moodle site as an admin and go to _Site administration > Plugins > Install plugins_.
Upload the ZIP file with the plugin code. You should only be prompted to add extra details if your plugin type is not automatically detected.
Check the plugin validation report and finish the installation.

## Installing manually ##

The plugin can be also installed by adding the contents of this directory to

    {your/moodle/dirroot}/mod/trainingevent

Afterwards, log in to your Moodle site as an admin and go to _Site administration > Notifications_ to complete the installation.

Alternatively, you can run

    $ php admin/cli/upgrade.php

to complete the installation from the command line.

## License ##
2010+ e-Learn Design Ltd. https://www.e-learndesign.co.uk
IOMAD is a registered trademark in the UK belonging to Derick Turner 
