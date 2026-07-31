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

namespace local_iomadcustompage;

/**
 * Constants for local_iomadcustompage plugin.
 *
 * @package    local_iomadcustompage
 * @copyright  2024 BitAscii Solutions <bitascii.dev@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class constants {
    /** @var int Top level page depth for hierarchical structure */
    public const TOP_LEVEL_PAGE_DEPTH = 1;

    /** @var int Maximum page depth for hierarchical structure */
    public const PAGE_MAX_DEPTH = 2;

    /** @var int Maximum breadcrumb depth to prevent infinite loops */
    public const MAX_BREADCRUMB_DEPTH = 10;

    /** @var int Maximum name length for pages */
    public const MAX_PAGE_NAME_LENGTH = 255;

    /** @var int Maximum title length for pages */
    public const MAX_PAGE_TITLE_LENGTH = 255;

    /** @var int Default page depth for root pages */
    public const DEFAULT_PAGE_DEPTH = 1;

    /** @var string Default page layout */
    public const DEFAULT_PAGE_LAYOUT = 'report';

    /** @var string Page type for custom pages */
    public const PAGE_TYPE = 'local-iomadcustompage-view';

    /** @var int Container page indicator */
    public const PAGE_IS_CONTAINER = 1;

    /** @var int Non-container page indicator */
    public const PAGE_NOT_CONTAINER = 0;

    /** @var int Show in primary navigation */
    public const SHOW_IN_PRIMARY_NAV = 1;

    /** @var int Hide from primary navigation */
    public const HIDE_FROM_PRIMARY_NAV = 0;

    /** @var int Direction to move page up */
    public const PAGE_UP = 1;

    /** @var int Direction to move page down */
    public const PAGE_DOWN = -1;
}
