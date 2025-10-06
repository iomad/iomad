# IOMAD

<p align="center"><a href="https://www.iomad.org" target="_blank" title="IOMAD Website">
  <img src="https://avatars.githubusercontent.com/u/5493428?v=4" alt="The IOMAD Logo">
</a></p>

Part of the IOMAD suite of plugins, enhancing the core Moodle feature set with multi-tenant functionalities.

This is the main class provider plugin that allows all of the other plugins in the IOMAD set to function.

IOMAD plugins are interdependent, so all of them need to be installed. IOMAD also requires Moodle core code changes to support
the multi-tenancy functions. Patches for this, and installation instructions, can be found here: https://github.com/iomad/moodle-core_patch 

The IOMAD suite will add the following to your Moodle site:

- Comprehensive white-labelling capabilities, including themes, URLs, certification, emails, authentication, course sharing, and more
- Per-tenant OIDC and SAML 2 single sign-on settings
- Per-tenant corporate hierarchy functionality, including parent/child settings
- Per-tenant role capabilities for managers and users, including the devolution of administrative functions for tenant self-management of staff
- Flexible license-based enrolment for control over how tenants provide course access to users
- Advanced reporting with a feature-rich set of reports for compliance, course completion, user completion, license usage, and more
- Automated course compliance life-cycles that keep a full history of course completion information for all users
- Face-to-face training events with waiting rooms, and sign-up and approval workflows for both physical and virtual locations
- Learning paths and micro-learning features to allow tenants to control how courses are undertaken
- An ecommerce solution that works with any payment gateway supported by Moodle, and allows for per-tenant payment accounts and pricing structures

IOMAD is developed and maintained by Moodle Partner e-Learn Design Ltd.

Supported databases: MySQL, MariaDB, PostgreSQL, MS SQL Server

Compatibility: Moodle Mobile App, BMA, WooCommerce, optional Moodle plugins (including themes)

## IMPORTANT ##

Care must be taken to ensure the appropriate restriction of viewing with any plugin that interacts with a multi-tenant function. For an
example of potential compromise, within shared courses, IOMAD uses forced separate groups to keep users of tenants apart. If you add
functionality that doesn't work within groups (or allows users to see outside of the group they are locked in), then this will break
the multi-tenancy restrictions of those courses. That's not to say they can't be developed to work as you want them to, but
out-of-the-box isn't guaranteed.

For Moodle Themes specifically, as the per-company CSS will not work automatically, and the per-company logo may not either, depending
on where the theme stores them (i.e. does it use standard core Moodle logo options?), your favourite theme might need a tweak to make it
fully compatible with all the multi-tenant functions. Don’t want to tweak? You’ll simply get one overall theme that every tenant will see.

## Installing via uploaded ZIP file ##

Log in to your Moodle site as an admin and go to _Site administration > Plugins > Install plugins_.
Upload the ZIP file with the plugin code. You should only be prompted to add extra details if your plugin type is not automatically detected.
Check the plugin validation report and finish the installation.

## Installing manually ##

The plugin can be also installed by adding the contents of this directory to

    {your/moodle/dirroot}/local/iomad

Afterwards, log in to your Moodle site as an admin and go to _Site administration > Notifications_ to complete the installation.

Alternatively, you can run

    $ php admin/cli/upgrade.php

to complete the installation from the command line.


## License ##
2010+ e-Learn Design Ltd. https://www.e-learndesign.co.uk
IOMAD is a registered trademark in the UK belonging to Derick Turner 
