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
 *  page.php description here.
 *
 * @package     local_iomadcustompage
 * @copyright   2024 BitAscii Solutions <bitascii.dev@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_iomadcustompage\local\iomadcustompage;

use coding_exception;
use invalid_parameter_exception;
use local_iomadcustompage\local\models\page as pagepersistent;
use local_iomadcustompage\output\page_contents;
use local_iomadcustompage\output\page_deatils;
use local_iomadcustompage\local\helpers\page as page_helper;
use renderer_base;
use stdClass;

/**
 * iomadcustompage class to representing a page
 */
class page {
    /** @var page $pagepersistent Page persistent */
    private $pagepersistent;
    /**
     * Returns persistent class used when initialising this page
     *
     * @return page
     */
    public function __construct(pagepersistent $page) {
        $this->pagepersistent = $page;
    }

    /**
     * page persistent getter
     * @return pagepersistent
     */
    final public function get_pagepersistent(): pagepersistent {
        return $this->pagepersistent;
    }

    /**
     * page details output
     * @return bool|string
     */
    public function details_output() {
        global $PAGE;

        /** @var \local_iomadcustompage\output\renderer $renderer */
        $renderer = $PAGE->get_renderer('local_iomadcustompage');
        $pagedetails = new page_deatils($this->get_pagepersistent());

        return $renderer->render($pagedetails);
    }

    /**
     * page content output
     * @return bool|string
     * @throws \coding_exception
     */
    public function content_output() {
        global $PAGE;
        $renderer = $PAGE->get_renderer('local_iomadcustompage');
        $pagecontents = new page_contents($this->get_pagepersistent());
        return $renderer->render($pagecontents);
    }

    /**
     * delete a page
     * @return bool
     * @throws \coding_exception
     * @throws \invalid_parameter_exception
     */
    public function delete() {
        // We need to delete the audiences of this page.
        // We need to clear up the context of this page with blocks.
        // All the above-mentioned will be handled by page model before_delete hook.
        return page_helper::delete_page((int)$this->pagepersistent->get('id'));
    }
}
