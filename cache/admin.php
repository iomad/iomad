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
 * The administration and management interface for the cache setup and configuration.
 *
 * This file is part of Moodle's cache API, affectionately called MUC.
 *
 * @package    core
 * @category   cache
 * @copyright  2012 Sam Hemelryk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../config.php');
require_once($CFG->dirroot.'/lib/adminlib.php');
require_once($CFG->dirroot.'/cache/locallib.php');
require_once($CFG->dirroot.'/cache/forms.php');

// The first time the user visits this page we are going to reparse the definitions.
// Just ensures that everything is up to date.
// We flag is session so that this only happens once as people are likely to hit
// this page several times if making changes.
if (empty($SESSION->cacheadminreparsedefinitions)) {
    cache_helper::update_definitions();
    $SESSION->cacheadminreparsedefinitions = true;
}

$action = optional_param('action', null, PARAM_ALPHA);

admin_externalpage_setup('cacheconfig');
$context = context_system::instance();

$stores = cache_administration_helper::get_store_instance_summaries();
$plugins = cache_administration_helper::get_store_plugin_summaries();
$definitions = cache_administration_helper::get_definition_summaries();
$defaultmodestores = cache_administration_helper::get_default_mode_stores();
$locks = cache_administration_helper::get_lock_summaries();

$title = new lang_string('cacheadmin', 'cache');
$mform = null;
$notifications = array();
$notifysuccess = true;

if (!empty($action) && confirm_sesskey()) {
    switch ($action) {
        case 'rescandefinitions' : // Rescan definitions.
            cache_config_writer::update_definitions();
            redirect($PAGE->url);
            break;
        case 'addstore' : // Add the requested store.
            $plugin = required_param('plugin', PARAM_PLUGIN);
            if (!$plugins[$plugin]['canaddinstance']) {
                print_error('ex_unmetstorerequirements', 'cache');
            }
            $mform = cache_administration_helper::get_add_store_form($plugin);
            $title = get_string('addstore', 'cache', $plugins[$plugin]['name']);
            if ($mform->is_cancelled()) {
                redirect($PAGE->url);
            } else if ($data = $mform->get_data()) {
                $config = cache_administration_helper::get_store_configuration_from_data($data);
                $writer = cache_config_writer::instance();
                unset($config['lock']);
                foreach ($writer->get_locks() as $lock => $lockconfig) {
                    if ($lock == $data->lock) {
                        $config['lock'] = $data->lock;
                    }
                }
                $writer->add_store_instance($data->name, $data->plugin, $config);
                redirect($PAGE->url, get_string('addstoresuccess', 'cache', $plugins[$plugin]['name']), 5);
            }
            break;
        case 'editstore' : // Edit the requested store.
            $plugin = required_param('plugin', PARAM_PLUGIN);
            $store = required_param('store', PARAM_TEXT);
            $mform = cache_administration_helper::get_edit_store_form($plugin, $store);
            $title = get_string('addstore', 'cache', $plugins[$plugin]['name']);
            if ($mform->is_cancelled()) {
                redirect($PAGE->url);
            } else if ($data = $mform->get_data()) {
                $config = cache_administration_helper::get_store_configuration_from_data($data);
                $writer = cache_config_writer::instance();

                unset($config['lock']);
                foreach ($writer->get_locks() as $lock => $lockconfig) {
                    if ($lock == $data->lock) {
                        $config['lock'] = $data->lock;
                    }
                }
                $writer->edit_store_instance($data->name, $data->plugin, $config);
                redirect($PAGE->url, get_string('editstoresuccess', 'cache', $plugins[$plugin]['name']), 5);
            }
            break;
        case 'deletestore' : // Delete a given store.
            $store = required_param('store', PARAM_TEXT);
            $confirm = optional_param('confirm', false, PARAM_BOOL);

            if (!array_key_exists($store, $stores)) {
                $notifysuccess = false;
                $notifications[] = array(get_string('invalidstore', 'cache'), false);
            } else if ($stores[$store]['mappings'] > 0) {
                $notifysuccess = false;
                $notifications[] = array(get_string('deletestorehasmappings', 'cache'), false);
            }

            if ($notifysuccess) {
                if (!$confirm) {
                    $title = get_string('confirmstoredeletion', 'cache');
                    $params = array('store' => $store, 'confirm' => 1, 'action' => $action, 'sesskey' => sesskey());
                    $url = new moodle_url($PAGE->url, $params);
                    $button = new single_button($url, get_string('deletestore', 'cache'));

                    $PAGE->set_title($title);
                    $PAGE->set_heading($SITE->fullname);
                    echo $OUTPUT->header();
                    echo $OUTPUT->heading($title);
                    $confirmation = get_string('deletestoreconfirmation', 'cache', $stores[$store]['name']);
                    echo $OUTPUT->confirm($confirmation, $button, $PAGE->url);
                    echo $OUTPUT->footer();
                    exit;
                } else {
                    $writer = cache_config_writer::instance();
                    $writer->delete_store_instance($store);
                    redirect($PAGE->url, get_string('deletestoresuccess', 'cache'), 5);
                }
            }
            break;
        case 'editdefinitionmapping' : // Edit definition mappings.
            $definition = required_param('definition', PARAM_SAFEPATH);
            if (!array_key_exists($definition, $definitions)) {
                throw new cache_exception('Invalid cache definition requested');
            }
            $title = get_string('editdefinitionmappings', 'cache', $definition);
            $mform = new cache_definition_mappings_form($PAGE->url, array('definition' => $definition));
            if ($mform->is_cancelled()) {
                redirect($PAGE->url);
            } else if ($data = $mform->get_data()) {
                $writer = cache_config_writer::instance();
                $mappings = array();
                foreach ($data->mappings as $mapping) {
                    if (!empty($mapping)) {
                        $mappings[] = $mapping;
                    }
                }
                $writer->set_definition_mappings($definition, $mappings);
                redirect($PAGE->url);
            }
            break;
        case 'editdefinitionsharing' :
            $definition = required_param('definition', PARAM_SAFEPATH);
            if (!array_key_exists($definition, $definitions)) {
                throw new cache_exception('Invalid cache definition requested');
            }
            $title = get_string('editdefinitionsharing', 'cache', $definition);
            $sharingoptions = $definitions[$definition]['sharingoptions'];
            $customdata = array('definition' => $definition, 'sharingoptions' => $sharingoptions);
            $mform = new cache_definition_sharing_form($PAGE->url, $customdata);
            $mform->set_data(array(
                'sharing' => $definitions[$definition]['selectedsharingoption'],
                'userinputsharingkey' => $definitions[$definition]['userinputsharingkey']
            ));
            if ($mform->is_cancelled()) {
                redirect($PAGE->url);
            } else if ($data = $mform->get_data()) {
                $component = $definitions[$definition]['component'];
                $area = $definitions[$definition]['area'];
                // Purge the stores removing stale data before we alter the sharing option.
                cache_helper::purge_stores_used_by_definition($component, $area);
                $writer = cache_config_writer::instance();
                $sharing = array_sum(array_keys($data->sharing));
                $userinputsharingkey = $data->userinputsharingkey;
                $writer->set_definition_sharing($definition, $sharing, $userinputsharingkey);
                redirect($PAGE->url);
            }
            break;
        case 'editmodemappings': // Edit default mode mappings.
            $mform = new cache_mode_mappings_form(null, $stores);
            $mform->set_data(array(
                'mode_'.cache_store::MODE_APPLICATION => key($defaultmodestores[cache_store::MODE_APPLICATION]),
                'mode_'.cache_store::MODE_SESSION => key($defaultmodestores[cache_store::MODE_SESSION]),
                'mode_'.cache_store::MODE_REQUEST => key($defaultmodestores[cache_store::MODE_REQUEST]),
            ));
            if ($mform->is_cancelled()) {
                redirect($PAGE->url);
            } else if ($data = $mform->get_data()) {
                $mappings = array(
                    cache_store::MODE_APPLICATION => array($data->{'mode_'.cache_store::MODE_APPLICATION}),
                    cache_store::MODE_SESSION => array($data->{'mode_'.cache_store::MODE_SESSION}),
                    cache_store::MODE_REQUEST => array($data->{'mode_'.cache_store::MODE_REQUEST}),
                );
                $writer = cache_config_writer::instance();
                $writer->set_mode_mappings($mappings);
                redirect($PAGE->url);
            }
            break;

        case 'purgedefinition': // Purge a specific definition.
            $definition = required_param('definition', PARAM_SAFEPATH);
            list($component, $area) = explode('/', $definition, 2);
            $factory = cache_factory::instance();
            $definition = $factory->create_definition($component, $area);
            if ($definition->has_required_identifiers()) {
                // We will have to purge the stores used by this definition.
                cache_helper::purge_stores_used_by_definition($component, $area);
            } else {
                // Alrighty we can purge just the data belonging to this definition.
                cache_helper::purge_by_definition($component, $area);
            }
            redirect($PAGE->url, get_string('purgedefinitionsuccess', 'cache'), 5);
            break;

        case 'purgestore':
        case 'purge': // Purge a store cache.
            $store = required_param('store', PARAM_TEXT);
            cache_helper::purge_store($store);
            redirect($PAGE->url, get_string('purgestoresuccess', 'cache'), 5);
            break;

        case 'newlockinstance':
            // Adds a new lock instance.
            $lock = required_param('lock', PARAM_ALPHANUMEXT);
            $mform = cache_administration_helper::get_add_lock_form($lock);
            if ($mform->is_cancelled()) {
                redirect($PAGE->url);
            } else if ($data = $mform->get_data()) {
                $factory = cache_factory::instance();
                $config = $factory->create_config_instance(true);
                $name = $data->name;
                $data = cache_administration_helper::get_lock_configuration_from_data($lock, $data);
                $config->add_lock_instance($name, $lock, $data);
                redirect($PAGE->url, get_string('addlocksuccess', 'cache', $name), 5);
            }
            break;
        case 'deletelock':
            // Deletes a lock instance.
            $lock = required_param('lock', PARAM_ALPHANUMEXT);
            $confirm = optional_param('confirm', false, PARAM_BOOL);
            if (!array_key_exists($lock, $locks)) {
                $notifysuccess = false;
                $notifications[] = array(get_string('invalidlock', 'cache'), false);
            } else if ($locks[$lock]['uses'] > 0) {
                $notifysuccess = false;
                $notifications[] = array(get_string('deletelockhasuses', 'cache'), false);
            }
            if ($notifysuccess) {
                if (!$confirm) {
                    $title = get_string('confirmlockdeletion', 'cache');
                    $params = array('lock' => $lock, 'confirm' => 1, 'action' => $action, 'sesskey' => sesskey());
                    $url = new moodle_url($PAGE->url, $params);
                    $button = new single_button($url, get_string('deletelock', 'cache'));

                    $PAGE->set_title($title);
                    $PAGE->set_heading($SITE->fullname);
                    echo $OUTPUT->header();
                    echo $OUTPUT->heading($title);
                    $confirmation = get_string('deletelockconfirmation', 'cache', $lock);
                    echo $OUTPUT->confirm($confirmation, $button, $PAGE->url);
                    echo $OUTPUT->footer();
                    exit;
                } else {
                    $writer = cache_config_writer::instance();
                    $writer->delete_lock_instance($lock);
                    redirect($PAGE->url, get_string('deletelocksuccess', 'cache'), 5);
                }
            }
            break;
    }
}

// Add cache store warnings to the list of notifications.
// Obviously as these are warnings they are show as failures.
foreach (cache_helper::warnings($stores) as $warning) {
    $notifications[] = array($warning, false);
}

$PAGE->set_title($title);
$PAGE->set_heading($SITE->fullname);
/* @var core_cache_renderer $renderer */
$renderer = $PAGE->get_renderer('core_cache');

echo $renderer->header();
echo $renderer->heading($title);
echo $renderer->notifications($notifications);

if ($mform instanceof moodleform) {
    $mform->display();
} else {
    echo $renderer->store_plugin_summaries($plugins);
    echo $renderer->store_instance_summariers($stores, $plugins);
    echo $renderer->definition_summaries($definitions, $context);
    echo $renderer->lock_summaries($locks);

    $applicationstore = join(', ', $defaultmodestores[cache_store::MODE_APPLICATION]);
    $sessionstore = join(', ', $defaultmodestores[cache_store::MODE_SESSION]);
    $requeststore = join(', ', $defaultmodestores[cache_store::MODE_REQUEST]);
    $editurl = new moodle_url('/cache/admin.php', array('action' => 'editmodemappings', 'sesskey' => sesskey()));
    echo $renderer->mode_mappings($applicationstore, $sessionstore, $requeststore, $editurl);
}

echo $renderer->footer();
