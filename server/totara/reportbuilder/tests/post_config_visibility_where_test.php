<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package totara_reportbuilder
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

/**
 * Covers the post_config_visibility_where in the reportbuilder class.
 *
 * To test, run this from the command line from the $CFG->dirroot.
 * vendor/bin/phpunit --verbose totara_reportbuilder_post_config_visibility_where_testcase totara/reportbuilder/tests/post_config_visibility_where_test.php
 *
 * @group totara_reportbuilder
 */
class totara_reportbuilder_post_config_visibility_where_testcase extends advanced_testcase {
    use totara_reportbuilder\phpunit\report_testing;

    public function test_post_config_visibility_where() {
        $this->setAdminUser();

        $user = $this->getDataGenerator()->create_user();

        // Create report. We use the 'dp_program' report, because we know it must include the visibility required columns.
        // This will likely change in the future as we get rid of the required columns.
        $rid = $this->create_report('dp_program', 'Test ROL programs 1');
        $config = (new rb_config())->set_nocache(true);
        $report = reportbuilder::create($rid, $config);

        // Call post_config_visibility_where and see that there is no problem.
        // Note that we're not really checking what the result of this function call is - that should be done
        // directly on totara_visibility_where. Just make sure that 'base' and 'available' are part of the result.
        list($wheresql, $params) = $report->post_config_visibility_where('program', 'base', $user->id); // No exception.

        $matches = [];

        $this->assertGreaterThan(0, strpos($wheresql, 'base.visible = 1 AND'));
        $this->assertEquals(1, preg_match('/WHERE vh_ctx.contextlevel = :(uq_level_[0-9]+)/', $wheresql, $matches));
        $this->assertEquals(45, $params[$matches[1]]);
        $this->assertGreaterThan(0, strpos($wheresql, 'base.availablefrom = 0 OR base.availablefrom < '));
        $this->assertGreaterThan(0, strpos($wheresql, 'base.availableuntil = 0 OR base.availableuntil > '));

        // Check that certifications gives the same result.
        list($wheresql, $params) = $report->post_config_visibility_where('certification', 'base', $user->id);
        $this->assertGreaterThan(0, strpos($wheresql, 'base.visible = 1 AND'));
        $this->assertEquals(1, preg_match('/WHERE vh_ctx.contextlevel = :(uq_level_[0-9]+)/', $wheresql, $matches));
        $this->assertEquals(45, $params[$matches[1]]);
        $this->assertGreaterThan(0, strpos($wheresql, 'base.availablefrom = 0 OR base.availablefrom < '));
        $this->assertGreaterThan(0, strpos($wheresql, 'base.availableuntil = 0 OR base.availableuntil > '));

        // Check that there is an exception when required joins are not present.
        try {
            $ctxkey = -1;
            foreach ($report->requiredjoins as $key => $item) {
                if ($item->name == 'ctx') {
                    $ctxkey = $key;
                    break;
                }
            }

            $ctxjoin = $report->requiredjoins[$ctxkey];
            unset($report->requiredjoins[$ctxkey]);
            $report->post_config_visibility_where('program', 'base', $user->id);
            $this->fail('Exception not triggered!');
        } catch (Exception $e) {
            $this->assertStringContainsString('Report is missing required columns or required joins to do the visibility check', $e->getMessage());
        }
        // Restore the ctx join and try again.
        array_push($report->requiredjoins, $ctxjoin);
        list($wheresql, $params) = $report->post_config_visibility_where('program', 'base', $user->id); // No exception.
        $this->assertGreaterThan(0, strpos($wheresql, 'base.visible = 1 AND'));
        $this->assertEquals(1, preg_match('/WHERE vh_ctx.contextlevel = :(uq_level_[0-9]+)/', $wheresql, $matches));
        $this->assertEquals(45, $params[$matches[1]]);
        $this->assertGreaterThan(0, strpos($wheresql, 'base.availablefrom = 0 OR base.availablefrom < '));
        $this->assertGreaterThan(0, strpos($wheresql, 'base.availableuntil = 0 OR base.availableuntil > '));
    }

    public function test_post_config_visibility_with_required_columns_and_without_required_joins() {
        $this->setAdminUser();
        $user = $this->getDataGenerator()->create_user();

        // List of required columns to update the report
        $requiredcolumns['ctx-id'] = new rb_column(
            'ctx',
            'id',
            '',
            "ctx.id",
            array('joins' => 'ctx')
        );

        $requiredcolumns['visibility-id'] = new rb_column(
            'visibility',
            'id',
            '',
            "base.id"
        );

        $requiredcolumns['visibility-visible'] = new rb_column(
            'visibility',
            'visible',
            '',
            "base.visible"
        );

        $requiredcolumns['visibility-audiencevisible'] = new rb_column(
            'visibility',
            'audiencevisible',
            '',
            "base.audiencevisible"
        );

        $requiredcolumns['visibility-available'] = new rb_column(
            'visibility',
            'available',
            '',
            "base.available"
        );

        $requiredcolumns['visibility-availablefrom'] = new rb_column(
            'visibility',
            'availablefrom',
            '',
            "base.availablefrom"
        );

        $requiredcolumns['visibility-availableuntil'] = new rb_column(
            'visibility',
            'availableuntil',
            '',
            "base.availableuntil"
        );

        // Create the report.
        $rid = $this->create_report('dp_program', 'Test ROL programs 1');
        $config = (new rb_config())->set_nocache(true);
        $report = reportbuilder::create($rid, $config);

        // Unset required joins.
        $report->requiredjoins = [];

        // Add required columns to the report.
        $requiredcolumns_property = new ReflectionProperty(reportbuilder::class, 'requiredcolumns');
        $requiredcolumns_property->setAccessible(true);
        $requiredcolumns_property->setValue($report, $requiredcolumns);

        // Check post config passes with required columns and without required joins.
        list($wheresql, $params) = $report->post_config_visibility_where('program', 'base', $user->id); // No exception.
        $this->assertGreaterThan(0, strpos($wheresql, 'base.visible = 1 AND'));
        $this->assertEquals(1, preg_match('/WHERE vh_ctx.contextlevel = :(uq_level_[0-9]+)/', $wheresql, $matches));
        $this->assertEquals(45, $params[$matches[1]]);
        $this->assertGreaterThan(0, strpos($wheresql, 'base.availablefrom = 0 OR base.availablefrom < '));
        $this->assertGreaterThan(0, strpos($wheresql, 'base.availableuntil = 0 OR base.availableuntil > '));

        // Remove the visibility-visible required column and see that there is an exception.
        $allrequiredcolumns = $report->requiredcolumns;
        try {
            unset($report->requiredcolumns['ctx-id']);
            $report->post_config_visibility_where('program', 'base', $user->id);
            $this->fail('Exception not triggered!');
        } catch (Exception $e) {
            $this->assertStringContainsString('Report is missing required columns or required joins to do the visibility check', $e->getMessage());
        }

        // Restore the required columns and see there is not exceptions.
        $report->requiredcolumns = $allrequiredcolumns;
        list($wheresql, $params) = $report->post_config_visibility_where('program', 'base', $user->id); // No exception.
        $this->assertGreaterThan(0, strpos($wheresql, 'base.visible = 1 AND'));
        $this->assertEquals(1, preg_match('/WHERE vh_ctx.contextlevel = :(uq_level_[0-9]+)/', $wheresql, $matches));
        $this->assertEquals(45, $params[$matches[1]]);
        $this->assertGreaterThan(0, strpos($wheresql, 'base.availablefrom = 0 OR base.availablefrom < '));
        $this->assertGreaterThan(0, strpos($wheresql, 'base.availableuntil = 0 OR base.availableuntil > '));
    }
}
