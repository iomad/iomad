# Availability language

Restrict module and section access based on user language.

## Idea

Language filters are great, but sometimes they can make your resources and activities very complex. 
This availability condition makes it easy to show an English resource only to English users and an
activity in French only to French speaking students.

This plugin only pops up when

1. There is more than 1 language installed in the system (obvious, we need a least 2 languages installed
   to restrict)
2. When the language of the course is NOT forced. (Course - Edit settings - Appearance - Force language).
   When a course has a forced language, everything will be shown in this language and we are certain no
   user will ever arrive with another language enabled. On that moment there is no need to show the
   restriction, as it would only create the illusion that people could be restricted.

## Conditional availability conditions

Check the global documentation about conditional availability conditions:
   https://docs.moodle.org/en/Conditional_activities_settings

## Warning

* This plugin is 100% open source and has NOT been tested in Moodle Workplace, Totara, or any other proprietary software system. As long as the latter do not reward plugin developers, you can use this plugin only in 100% open source environments.
* The Moodle Mobile app relies on the user profile language and/or course language to show or hide a resource: the language selected in the app does NOT prevail.

## Requirements

This plugin requires Moodle 3.9+

## Installation

Install the plugin like any other plugin to folder /availability/condition/language
See http://docs.moodle.org/en/Installing_plugins for details on installing Moodle plugins

## Initial Configuration

This plugin does not need configuration after installation.

## Theme support

This plugin is developed and tested on Moodle Core's Boost theme and Boost child themes, including Moodle Core's Classic theme.

## Plugin repositories

This plugin will be published and regularly updated on Github: https://github.com/ewallah/moodle-availability_language

## Bug and problem reports / Support requests

This plugin is carefully developed and thoroughly tested, but bugs and problems can always appear.
Please report bugs and problems on Github: https://github.com/ewallah/moodle-availability_language/issues
We will do our best to solve your problems, but please note that due to limited resources we can't always provide per-case support.

## Feature proposals

Please issue feature proposals on Github: https://github.com/ewallah/moodle-availability_language/issues
Please create pull requests on Github: https://github.com/ewallah/moodle-availability_language/pulls
We are always interested to read about your feature proposals or even get a pull request from you, but please accept that we can handle your issues only as feature proposals and not as feature requests.

## Moodle release support

This plugin is maintained for the latest major releases of Moodle.

## Status

[![Build Status](https://github.com/ewallah/moodle-availability_language/workflows/Tests/badge.svg)](https://github.com/ewallah/moodle-availability_language/actions)
[![Coverage Status](https://coveralls.io/repos/github/ewallah/moodle-availability_language/badge.svg?branch=main)](https://coveralls.io/github/ewallah/moodle-availability_language?branch=main)
![Mutation score](https://badgen.net/badge/Mutation%20Score%20Indicator/96?color=orange)

## Copyright

eWallah.net

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program.  If not, see <https://www.gnu.org/licenses/>.
