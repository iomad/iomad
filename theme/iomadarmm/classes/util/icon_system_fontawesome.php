<?php 

namespace theme_iomadarmm\util;

use renderer_base;
use pix_icon;

defined('MOODLE_INTERNAL') || die();

/**
 * Class allowing different systems for mapping and rendering icons.
 *
 * Possible icon styles are:
 *   1. standard - image tags are generated which point to pix icons stored in a plugin pix folder.
 *   2. fontawesome - font awesome markup is generated with the name of the icon mapped from the moodle icon name.
 *   3. inline - inline tags are used for svg and png so no separate page requests are made (at the expense of page size).
 *
 * @package    theme_THEMENAME
 * @category   output
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class icon_system_fontawesome extends \core\output\icon_system_fontawesome {

    public function get_core_icon_map() {
        $icons = parent::get_core_icon_map();

        $remappedIcons = [
            'core:a/add_file' => 'fa-file',
            'core:a/create_folder' => 'fa-folder'
        ];

        $icons = array_merge($icons, $remappedIcons);

        return $icons;
    }
    /**
     * Renders the pix icon using the icon system
     *
     * @param renderer_base $output
     * @param pix_icon $icon
     * @return mixed
     */
    public function render_pix_icon(renderer_base $output, pix_icon $icon) {
        $subtype = 'pix_icon_fontawesome';
        $subpix = new $subtype($icon);

        $data = $subpix->export_for_template($output);

        if (!$subpix->is_mapped()) {
            $data['unmappedIcon'] = $icon->export_for_template($output);
        }

        if($icon->pix == "i/navigationitem") {
            return;
        }
        
        return $output->render_from_template('core/pix_icon_fontawesome', $data);
    }
}