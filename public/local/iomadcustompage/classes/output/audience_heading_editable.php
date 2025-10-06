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

declare(strict_types=1);

namespace local_iomadcustompage\output;

use coding_exception;
use core\invalid_persistent_exception;
use core\output\inplace_editable;
use core_external;
use core_external\restricted_context_exception;
use invalid_parameter_exception;
use local_iomadcustompage\local\audiences\base;
use local_iomadcustompage\local\models\audience;
use local_iomadcustompage\page_access_exception;
use local_iomadcustompage\permission;

defined('MOODLE_INTERNAL') || die;

global $CFG;
require_once("{$CFG->libdir}/external/externallib.php");

/**
 * Audience heading editable component
 *
 * @package     local_iomadcustompage
 * @copyright   2024 BitAscii Solutions <bitascii.dev@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class audience_heading_editable extends inplace_editable {
  /**
   * Class constructor
   *
   * @param int $audienceid
   * @param audience|null $audience
   * @throws coding_exception
   */
    public function __construct(int $audienceid, ?audience $audience = null) {
        if ($audience === null) {
            $audience = new audience($audienceid);
        }

        $page = $audience->get_page();
        $editable = permission::can_edit_page($page);

        $audienceinstance = base::instance(0, $audience->to_record());

        // Use audience defined title if custom heading not set.
        if ('' !== $value = (string) $audience->get('heading')) {
            $displayvalue = $audience->get_formatted_heading($page->get_context());
        } else {
            $displayvalue = $value = $audienceinstance->get_name();
        }

        parent::__construct(
            'local_iomadcustompage',
            'audienceheading',
            $audience->get('id'),
            $editable,
            $displayvalue,
            $value,
            get_string('renameaudience', 'core_reportbuilder', $audienceinstance->get_name())
        );
    }

  /**
   * Update audience persistent and return self, called from inplace_editable callback
   *
   * @param int $audienceid
   * @param string $value
   * @return self
   * @throws invalid_persistent_exception
   * @throws invalid_parameter_exception
   * @throws page_access_exception
   * @throws coding_exception
   * @throws restricted_context_exception
   */
    public static function update(int $audienceid, string $value): self {
        $audience = new audience($audienceid);

        $page = $audience->get_page();

        core_external::validate_context($page->get_context());
        permission::require_can_edit_page($page);

        $value = clean_param($value, PARAM_TEXT);
        $audience
            ->set('heading', $value)
            ->update();

        return new self(0, $audience);
    }
}
