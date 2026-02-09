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
 * Unit test for query building.
 *
 * @package    block_dash
 * @copyright  2020 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash;

use block_dash\local\dash_framework\query_builder\builder;
use block_dash\local\dash_framework\query_builder\exception\invalid_operator_exception;
use block_dash\local\dash_framework\query_builder\exception\invalid_where_clause_exception;
use block_dash\local\dash_framework\query_builder\where;

/**
 * Unit test for query building.
 *
 * @group block_dash
 * @group bdecent
 * @group query_builder_test
 */
final class framework_query_builder_test extends \advanced_testcase {

    /**
     * Test for where() to ensure that the where conditions are correctly applied.
     *
     * @covers ::where
     * @return void
     */
    public function test_where(): void {
        $this->resetAfterTest();

        $category = $this->getDataGenerator()->create_category();

        $course1 = $this->getDataGenerator()->create_course([
            'shortname' => 'test1',
            'fullname' => 'Testing course 1',
            'category' => $category->id,
        ]);
        $course2 = $this->getDataGenerator()->create_course([
            'shortname' => 'test2',
            'fullname' => 'Testing course 2',
        ]);
        $course3 = $this->getDataGenerator()->create_course([
            'shortname' => 'test3',
            'fullname' => 'Testing course 3',
            'category' => $category->id,
        ]);

        // Test OPERATOR_EQUAL.
        $builder = new builder();
        $builder
            ->select('c.fullname', 'c_fullname')
            ->from('course', 'c')
            ->where('c.id', [$course1->id]);
        $result = array_values($builder->query());
        $this->assertCount(1, $result);
        $this->assertEquals('Testing course 1', $result[0]->c_fullname);

        // Test OPERATOR_IN.
        $builder = new builder();
        $builder
            ->select('c.fullname', 'c_fullname')
            ->select('c.category', 'c_category')
            ->from('course', 'c')
            ->where('c.id', [$course1->id, $course3->id], where::OPERATOR_IN);
        $result = array_values($builder->query());
        $this->assertCount(2, $result);
        $this->assertEquals($category->id, $result[0]->c_category);
        $this->assertEquals($category->id, $result[1]->c_category);

        $this->expectException(invalid_where_clause_exception::class);
        $builder->where('c.id', []);
        $builder->query();
    }

    /**
     * Test for where_in_query() to ensure that the where query.
     *
     * @covers ::where_in_query
     * @return void
     */
    public function test_where_in_query(): void {
        $this->resetAfterTest();

        $course1 = $this->getDataGenerator()->create_course([
            'shortname' => 'test1',
            'fullname' => 'Testing course 1',
            'startdate' => 946684800,
        ]);
        $course2 = $this->getDataGenerator()->create_course([
            'shortname' => 'test2',
            'fullname' => 'Testing course 2',
            'startdate' => 946684800,
        ]);
        $course3 = $this->getDataGenerator()->create_course([
            'shortname' => 'test3',
            'fullname' => 'Testing course 3',
            'startdate' => time(),
        ]);

        $builder = new builder();
        $builder
            ->select('c.fullname', 'c_fullname')
            ->from('course', 'c')
            ->where_in_query('c.id', 'SELECT c2.id FROM {course} c2
                WHERE c2.startdate > 0 AND c2.startdate <= :y2k', ['y2k' => 946684800]);

        $result = array_values($builder->query());
        $this->assertCount(2, $result);
        $this->assertEquals('Testing course 1', $result[0]->c_fullname);
        $this->assertEquals('Testing course 2', $result[1]->c_fullname);

        $this->expectException(invalid_operator_exception::class);
        $builder = new builder();
        $builder->select('c.id', 'c_id')->from('course', 'c')->where('c.id', [1], 'missing');
        $builder->query();
    }

    /**
     * Test for limits() to ensure that the dtatatables limits function.
     *
     * @covers ::limits
     * @return void
     */
    public function test_limits(): void {
        $this->resetAfterTest();

        $users = [];
        for ($i = 0; $i < 10; $i++) {
            $users[] = $this->getDataGenerator()->create_user(['middlename' => 'John']);
        }

        $builder = new builder();
        $builder
            ->select('u.id', 'u_id')
            ->from('user', 'u')
            ->limitfrom(5)
            ->limitnum(2)
            ->where('u.middlename', ['John']);

        $results = array_values($builder->query());
        $this->assertCount(2, $results);
        $this->assertEquals($users[5]->id, $results[0]->u_id);
        $this->assertEquals($users[6]->id, $results[1]->u_id);
    }

    /**
     * Test for orderby() to confirm the order by of datasource works.
     *
     * @covers ::orderby
     * @return void
     */
    public function test_orderby(): void {
        $this->resetAfterTest();

        $courses = [];
        for ($i = 0; $i < 10; $i++) {
            $courses[] = $this->getDataGenerator()->create_course();
        }

        $builder = new builder();
        $builder
            ->select('c.id', 'c_id')
            ->from('course', 'c')
            ->where('c.format', ['site'], where::OPERATOR_NOT_EQUAL);
        $builder->orderby('c.id', 'DESC');
        $results = array_values($builder->query());

        $this->assertEquals($courses[9]->id, $results[0]->c_id);

        $builder->orderby('c.id', 'ASC');
        $results = array_values($builder->query());

        $this->assertEquals($courses[0]->id, $results[0]->c_id);

        $this->expectException(\coding_exception::class);
        $builder->orderby('c.id', 'wrong');
    }

    /**
     * Test for joins() to ensure that the table joins works.
     *
     * @covers ::joins
     * @return void
     */
    public function test_joins(): void {
        $this->resetAfterTest();

        $category = $this->getDataGenerator()->create_category();

        $courses = [];
        for ($i = 0; $i < 10; $i++) {
            $courses[] = $this->getDataGenerator()->create_course(['category' => $category->id]);
        }

        $builder = new builder();
        $builder
            ->select('c.id', 'c_id')
            ->select('cc.name', 'cc_name')
            ->from('course', 'c')
            ->join('course_categories', 'cc', 'id', 'c.category')
            ->join_condition('cc', 'cc.parent = 0');

        $results = array_values($builder->query());
        $this->assertEquals($category->name, $results[0]->cc_name);

        $this->expectException(\coding_exception::class);
        $builder->join_condition('missing', '');
    }
}
