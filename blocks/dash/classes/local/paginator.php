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
 * Class paginator.
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\local;
use renderer_base;

/**
 * Class paginator.
 *
 * @package block_dash
 */
class paginator {

    /**
     * Template name.
     */
    const TEMPLATE = 'block_dash/paginator';

    /**
     * Per page default.
     */
    const PER_PAGE_DEFAULT = 10;

    /**
     * @var int Pagination per page.
     */
    protected $perpage;

    /**
     * @var int Pagination current page.
     */
    protected $currentpage;

    /**
     * @var callable Provided function to get count of items.
     */
    protected $countfunction;

    /**
     * @var bool If true, a human readable summary will be displayed above paginator (Showing x of x out of x).
     */
    protected $showpagesummary;

    /**
     * paginator constructor.
     * @param callable $countfunction
     * @param int $currentpage
     * @param int $perpage
     * @param bool $showpagesummary If true, a human readable summary will be displayed above paginator (Showing x of x out of x).
     * @throws \coding_exception
     */
    public function __construct(callable $countfunction, $currentpage = 0, $perpage = self::PER_PAGE_DEFAULT,
                                $showpagesummary = true) {
        if (!is_int($perpage)) {
            throw new \coding_exception('Per page value must be an integer.');
        }

        $this->perpage = $perpage;
        $this->currentpage = $currentpage;
        $this->countfunction = $countfunction;
        $this->showpagesummary = $showpagesummary;
    }

    /**
     * Get mustache template.
     *
     * @return string
     */
    public function get_template() {
        return self::TEMPLATE;
    }

    /**
     * Get limit from for query.
     *
     * @return float|int
     */
    public function get_limit_from() {
        return $this->get_current_page() * $this->perpage;
    }

    /**
     * Get per page for query.
     *
     * @return int
     */
    public function get_per_page() {

        return $this->perpage;
    }

    /**
     * Set per page for query.
     *
     * @param int $perpage
     */
    public function set_per_page($perpage) {
        $this->perpage = $perpage;
    }

    /**
     * Get current page for query.
     *
     * @return int
     */
    public function get_current_page() {
        return $this->currentpage;
    }

    /**
     * Set current page for query.
     *
     * @param int $page
     */
    public function set_current_page($page) {
        $this->currentpage = $page;
    }

    /**
     * Get count of all records.
     *
     * @return int
     */
    public function get_record_count() {
        $function = $this->countfunction;
        return $function();
    }

    /**
     * Get number of pages based on per page and count.
     *
     * @return int
     */
    public function get_page_count() {
        $count = $this->get_record_count();
        if ($count > 0) {
            return ceil($count / $this->get_per_page());
        }

        // Default to 0 pages.
        return 0;
    }

    /**
     * Get variables for template.
     *
     * @param renderer_base $output
     * @return array
     * @throws \coding_exception
     */
    public function export_for_template(renderer_base $output) {
        $count = $this->get_page_count();
        $frontdivider = false;
        $backdivider = false;

        $items = [];
        for ($i = 0; $i < $count; $i++) {
            $items[$i] = [
                'index' => $i,
                'page' => $i,
                'label' => $i + 1,
                'active' => $this->get_current_page() == $i,
            ];
        }

        // There's some hard coded values here. Maybe at some point clean up this algorithm.
        // But for now it works just fine.
        if ($this->get_current_page() >= 5) {
            for ($i = 1; $i < $this->get_current_page() - 2; $i++) {
                unset($items[$i]);
            }
            $frontdivider = true;
        }

        if ($this->get_page_count() - 1 >= $this->get_current_page() + 3) {
            $pagecount = $this->get_page_count();
            for ($i = $this->get_current_page() + 3; $i < $pagecount - 2; $i++) {
                unset($items[$i]);
                $backdivider = true;
            }
        }

        if ($frontdivider) {
            $items[1] = [
                'label' => '...',
                'disabled' => true,
            ];
            ksort($items);
        }

        if ($backdivider) {
            end($items);
            $items[key($items) - 1] = [
                'label' => '...',
                'disabled' => true,
            ];
            ksort($items);
        }

        // Add previous and next buttons.
        array_unshift($items, [
            'label' => get_string('previous'),
            'page' => $this->get_current_page() - 1,
            'disabled' => $this->get_current_page() == 0,
        ]);

        // Next button.
        $items[] = [
            'label' => get_string('next'),
            'page' => $this->get_current_page() + 1,
            'disabled' => $this->get_current_page() == $count - 1 || empty($count),
        ];

        $recordcount = $this->get_record_count();
        $limitto = (int)$this->get_per_page() + (int)$this->get_limit_from();
        if ($limitto > $recordcount) {
            $limitto = $recordcount;
        }

        // If there's no records hide the summary.
        if ($recordcount == 0) {
            $this->showpagesummary = false;
        }

        $summary = get_string('pagination_summary', 'block_dash', [
            'total' => $this->get_record_count(),
            'per_page' => $this->get_per_page(),
            'limit_from' => $this->get_limit_from() + 1,
            'limit_to' => $limitto,
        ]);

        return [
            'pages' => $items,
            'show_page_summary' => $this->showpagesummary,
            'summary' => $summary,
        ];
    }
}
