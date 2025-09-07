# IOMAD

# Microsoft 365 and Microsoft Entra ID Plugins for Moodle

## OpenID Connect Authentication Plugin.

The IOMAD OpenID Connect plugin provides single-sign-on functionality using configurable identity providers that can be configured on a
per-tenant basis. It is based on the auth_oidc plugin, which is part of the suite of Microsoft 365 plugins for Moodle.

Part of the IOMAD suite of plugins, enhancing the core Moodle feature set with multi-tenant functionalities.

IOMAD plugins are interdependent, so all of them need to be installed. IOMAD also requires Moodle core code changes to
support the multi-tenancy functions. Patches for this, and installation instructions, can be found here:
https://github.com/iomad/moodle-core_patch

More information on the IOMAD suite of plugins is available in the description of the main plugin: https://moodle.org/plugins/local_iomad

## Installation

1. Unpack the plugin into /auth/iomadoidc within your Moodle install.
2. From the Moodle Administration block, expand Site Administration and click "Notifications".
3. Follow the on-screen instuctions to install the plugin.
4. To configure the plugin, from the Moodle Administration block, go to Site Administration > Plugins > Authentication > Manage Authentication.
5. Click the icon to enable the plugin, then visit the settings page to configure the plugin. Follow the directions below each setting.

For more documentation, visit https://docs.moodle.org/34/en/Office365

For more information including support and instructions on how to contribute, please see: https://github.com/Microsoft/o365-moodle/blob/master/README.md

## Issues and Contributing
Please post issues for this plugin to: https://github.com/Microsoft/o365-moodle/issues/
Pull requests for this plugin should be submitted against our main repository: https://github.com/Microsoft/o365-moodle 

## License ##

2010+ e-Learn Design Ltd. https://www.e-learndesign.co.uk
IOMAD is a registered trademark in the UK belonging to Derick Turner

## Copyright

&copy; Microsoft, Inc.  Code for this plugin is licensed under the GPLv3 license.

Any Microsoft trademarks and logos included in these plugins are property of Microsoft and should not be reused, redistributed, modified, repurposed, or otherwise altered or used outside of this plugin.
