<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Settings
 *
 * This file contains settings used by tool_mobile
 *
 * @package    tool_mobile
 * @copyright  2016 Juan Leyva
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {

    $ADMIN->add('root', new admin_category('mobileapp', new lang_string('mobileapp', 'tool_mobile')), 'development');

    $temp = new admin_settingpage('mobilesettings', new lang_string('mobilesettings', 'tool_mobile'), 'moodle/site:config', false);

    // We should wait to the installation to finish since we depend on some configuration values that are set once
    // the admin user profile is configured.
    if (!during_initial_install()) {
        $enablemobiledocurl = new moodle_url(get_docs_url('Enable_mobile_web_services'));
        $enablemobiledoclink = html_writer::link($enablemobiledocurl, new lang_string('documentation'));
        $default = is_https() ? 1 : 0;
        $temp->add(new admin_setting_enablemobileservice('enablemobilewebservice',
                new lang_string('enablemobilewebservice', 'admin'),
                new lang_string('configenablemobilewebservice', 'admin', $enablemobiledoclink), $default));
    }

    $ADMIN->add('mobileapp', $temp);

    // Show only mobile settings if the mobile service is enabled.
    if (!empty($CFG->enablemobilewebservice)) {
        // Type of login.
        $temp = new admin_settingpage('mobileauthentication', new lang_string('mobileauthentication', 'tool_mobile'));
        $options = array(
            tool_mobile\api::LOGIN_VIA_APP => new lang_string('loginintheapp', 'tool_mobile'),
            tool_mobile\api::LOGIN_VIA_BROWSER => new lang_string('logininthebrowser', 'tool_mobile'),
            tool_mobile\api::LOGIN_VIA_EMBEDDED_BROWSER => new lang_string('loginintheembeddedbrowser', 'tool_mobile'),
        );
        $temp->add(new admin_setting_configselect('tool_mobile/typeoflogin',
                    new lang_string('typeoflogin', 'tool_mobile'),
                    new lang_string('typeoflogin_desc', 'tool_mobile'), 1, $options));

        $temp->add(new admin_setting_configtext('tool_mobile/forcedurlscheme',
                    new lang_string('forcedurlscheme_key', 'tool_mobile'),
                    new lang_string('forcedurlscheme', 'tool_mobile'), '', PARAM_NOTAGS));

        $ADMIN->add('mobileapp', $temp);

        // Appearance related settings.
        $temp = new admin_settingpage('mobileappearance', new lang_string('mobileappearance', 'tool_mobile'));

        $temp->add(new admin_setting_configtext('mobilecssurl', new lang_string('mobilecssurl', 'tool_mobile'),
                    new lang_string('configmobilecssurl', 'tool_mobile'), '', PARAM_URL));

        $temp->add(new admin_setting_heading('tool_mobile/smartappbanners',
                    new lang_string('smartappbanners', 'tool_mobile'), ''));

        $temp->add(new admin_setting_configcheckbox('tool_mobile/enablesmartappbanners',
                    new lang_string('enablesmartappbanners', 'tool_mobile'),
                    new lang_string('enablesmartappbanners_desc', 'tool_mobile'), 0));

        $temp->add(new admin_setting_configtext('tool_mobile/iosappid', new lang_string('iosappid', 'tool_mobile'),
                    new lang_string('iosappid_desc', 'tool_mobile'), '633359593', PARAM_ALPHANUM));

        $temp->add(new admin_setting_configtext('tool_mobile/androidappid', new lang_string('androidappid', 'tool_mobile'),
                    new lang_string('androidappid_desc', 'tool_mobile'), 'com.moodle.moodlemobile', PARAM_NOTAGS));

        $temp->add(new admin_setting_configtext('tool_mobile/setuplink', new lang_string('setuplink', 'tool_mobile'),
            new lang_string('setuplink_desc', 'tool_mobile'), 'https://download.moodle.org/mobile', PARAM_URL));

        $ADMIN->add('mobileapp', $temp);

        // Features related settings.
        $temp = new admin_settingpage('mobilefeatures', new lang_string('mobilefeatures', 'tool_mobile'));

        $temp->add(new admin_setting_heading('tool_mobile/logout',
                    new lang_string('logout'), ''));

        $temp->add(new admin_setting_configcheckbox('tool_mobile/forcelogout',
                    new lang_string('forcelogout', 'tool_mobile'),
                    new lang_string('forcelogout_desc', 'tool_mobile'), 0));

        $temp->add(new admin_setting_heading('tool_mobile/features',
                    new lang_string('mobilefeatures', 'tool_mobile'), ''));

        $options = tool_mobile\api::get_features_list();
        $temp->add(new admin_setting_configmultiselect('tool_mobile/disabledfeatures',
                    new lang_string('disabledfeatures', 'tool_mobile'),
                    new lang_string('disabledfeatures_desc', 'tool_mobile'), array(), $options));

        $temp->add(new admin_setting_configtextarea('tool_mobile/custommenuitems',
                    new lang_string('custommenuitems', 'tool_mobile'),
                    new lang_string('custommenuitems_desc', 'tool_mobile'), '', PARAM_RAW, '50', '10'));

        $temp->add(new admin_setting_heading('tool_mobile/language',
                    new lang_string('language'), ''));

        $temp->add(new admin_setting_configtextarea('tool_mobile/customlangstrings',
                    new lang_string('customlangstrings', 'tool_mobile'),
                    new lang_string('customlangstrings_desc', 'tool_mobile'), '', PARAM_RAW, '50', '10'));

        $ADMIN->add('mobileapp', $temp);
    }
}
