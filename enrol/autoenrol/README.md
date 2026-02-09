# AutoEnrol Enrolment Method

AutoEnrol adds functionality to automatically enrol users onto a course, either as they 
log in to your Moodle site or as they access a course. This plugin was at first intended 
for use on courses which you want all users to be able to access but can also be configured
for more advanced purposes as reserved access courses. Using the new user filtering you can
think at Autoenrol as a Swiss Army knife for Moodle enrolments.

When added to a course this enrolment plugin can enrol users onto a course automatically,
either as they log into your Moodle site or as they click on the course. It was originally
intended for use on site-wide courses such as "Moodle Help" or "Learner Voice". 

In addition the plugin has advanced functionality to support automatically grouping and
filtering users based upon their attributes. Depending on how your user accounts are set
up this may help you to give access to certain user groups.

Configuration of the plugin is managed by two capabilities which allow administrators to 
easily control who has access to the plugin.

The AutoEnrol filter use moodle standard availability interface so it adds great flexibility 
in user filtering.
The moodle profile availability core plugin unfortunately does not include language and 
authentication method.  If you need them, you can install additional availability plugins 
like [Restriction by language](https://moodle.org/plugins/availability_language) and 
[Restriction by authentication](https://github.com/bobopinna/moodle-availability_auth).

## [Changelog](CHANGES.md)

## Install

1. Copy the plugin directory "autoenrol" into moodle\enrol\. 
2. Check admin notifications to install.
3. Visit the "Site Administration > Plugins > Enrolments" page.
4. Click the eye symbol next to "Auto Enrol" to enable the plugin. 

## How to use AutoEnrol

Check Moodle documentation:
https://docs.moodle.org/en/Enrolment_methods#Managing_enrolment_methods.

## Maintainer

The module is being maintained by Roberto Pinna with Angelo Calò (testing).

The original module was developed by Mark Ward and it can be found on 
[his GitHub](https://github.com/markward/enrol_autoenrol). 

## Thanks to

With thanks to various friends for contributing, especially Matthew Cannings. 

Thanks also to users who have taken the time to share feedback and improve the module.

## Technical Support

Issue tracker can be found on [GitHub](https://github.com/bobopinna/moodle-enrol_autoenrol/issues).
Please try to give as much detail about your problem as possible and I'll do what I can to help.

## License

Released Under the GNU General Public Licence http://www.gnu.org/copyleft/gpl.html
