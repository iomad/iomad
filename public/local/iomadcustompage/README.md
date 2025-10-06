# IOMAD

<p align="center"><a href="https://www.iomad.org" target="_blank" title="IOMAD Website">
  <img src="https://avatars.githubusercontent.com/u/5493428?v=4" alt="The IOMAD Logo">
</a></p>

The local IOMAD custom pages plugin is a fork of the local_custompage plugin developed by BitAscii Solutions with additional
functionality to limit the audience by company.

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

    {your/moodle/dirroot}/mod/iomadcertificate

Afterwards, log in to your Moodle site as an admin and go to _Site administration > Notifications_ to complete the installation.

Alternatively, you can run

    $ php admin/cli/upgrade.php

to complete the installation from the command line.

## License ##

2010+ e-Learn Design Ltd. https://www.e-learndesign.co.uk
IOMAD is a registered trademark in the UK belonging to Derick Turner

2024 BitAscii Solutions <bitascii.dev@gamil.com>

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program.  If not, see <https://www.gnu.org/licenses/>.
