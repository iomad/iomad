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
 * Unit test for filtering.
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash;

use block_dash\local\data_grid\filter\choice_filter;
use block_dash\local\data_grid\filter\filter;
use block_dash\local\data_grid\filter\filter_collection;
use block_dash\local\data_grid\filter\filter_collection_interface;
use block_dash\local\data_grid\filter\filter_interface;

/**
 * Unit test for filtering.
 *
 * @group block_dash
 * @group bdecent
 * @group filter_test
 */
final class filter_test extends \advanced_testcase {

    /**
     * @var filter_collection_interface
     */
    private $filtercollection;

    /**
     * @var \stdClass
     */
    private $user;

    /**
     * This method is called before each test.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();

        global $USER;

        $this->user = $USER;

        $this->filtercollection = new filter_collection('testing', \context_system::instance());
        $this->filtercollection->add_filter(new filter('filter1', 'table.fieldname'));
        $this->filtercollection->init();
    }

    /**
     * Test for general_stuff() to ensure that the basic data testing are working.
     *
     * @covers ::general_stuff
     * @return void
     */
    public function test_general_stuff(): void {
        $this->assertEquals('testing', $this->filtercollection->get_unique_identifier());
        $this->assertCount(1, $this->filtercollection->get_filters());
        $this->assertTrue($this->filtercollection->has_filter('filter1'));
        $this->assertFalse($this->filtercollection->has_filter('missing'));
    }

    /**
     * Test for remove_filter() to ensure that the removing filters works fine.
     *
     * @covers ::remove_filter
     * @return void
     */
    public function test_remove_filter(): void {
        $filter = $this->filtercollection->get_filter('filter1');
        $this->filtercollection->remove_filter($filter);

        $this->assertFalse($this->filtercollection->has_filters());
        $this->assertFalse($this->filtercollection->remove_filter($filter),
            'Ensure false is returend when filter was already removed.');
    }

    /**
     * Test for applying_filter() to ensure that the filters are working properely.
     *
     * @covers ::applying_filter
     * @return void
     */
    public function test_applying_filter(): void {
        $this->assertCount(0, $this->filtercollection->get_applied_filters());
        $this->assertCount(0, $this->filtercollection->get_filters_with_values());

        $this->assertTrue($this->filtercollection->apply_filter('filter1', 123));
        $this->assertFalse($this->filtercollection->apply_filter('filter1', ''));
        $this->assertFalse($this->filtercollection->apply_filter('missing', 123));

        $this->assertCount(1, $this->filtercollection->get_applied_filters());

        $this->assertCount(1, $this->filtercollection->get_filters_with_values());
    }

    /**
     * Test for filter_sql_and_params_collection() to ensure that the filter returns the sql and params collections.
     *
     * @covers ::filter_sql_and_params_collection
     * @return void
     */
    public function test_filter_sql_and_params_collection(): void {
        $this->assertTrue($this->filtercollection->apply_filter('filter1', 123));

        list($sql, $params) = $this->filtercollection->get_sql_and_params();
        $this->assertEquals('table.fieldname = :param1', $sql[0], 'Ensure SQL is generated.');
        $this->assertEquals($params, ['param1' => 123], 'Ensure params are returned.');
    }

    /**
     * Test for required_filters() to ensure that the fields are correctly loaded for attributes.
     *
     * @covers ::required_filters
     * @return void
     */
    public function test_required_filters(): void {
        $this->assertFalse($this->filtercollection->has_required_filters());
        $this->assertCount(0, $this->filtercollection->get_required_filters());

        $filter = new filter('filter2', 'table.fieldname2');
        $filter->set_required(filter_interface::REQUIRED);

        $this->filtercollection->add_filter($filter);
        $this->assertTrue($this->filtercollection->has_required_filters());
        $this->assertCount(1, $this->filtercollection->get_required_filters());
    }

    /**
     * Test for caching() to confirm the filter data are cached.
     *
     * @covers ::caching
     * @return void
     */
    public function test_caching(): void {
        $this->filtercollection->apply_filter('filter1', 234);
        $this->filtercollection->cache($this->user);

        $this->assertEquals(234, $this->filtercollection->get_cache($this->user)['filter1']);

        $this->filtercollection->delete_cache($this->user);

        $this->assertEmpty($this->filtercollection->get_cache($this->user));
    }
}
