<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Carl Anderson <carl.anderson@totaralearning.com>
 * @package totara_reportbuilder
 */

defined('MOODLE_INTERNAL') || die();

use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * Tests the totara update report title mutation
 */
class totara_reportbuilder_webapi_resolver_mutation_update_report_title_testcase extends advanced_testcase {

    use webapi_phpunit_helper;

    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();

        global $CFG;
        require_once($CFG->dirroot . '/totara/reportbuilder/lib.php');
    }

    /**
     * Create new reportbuilder report.
     *
     * @param string $source
     * @param string $fullname
     * @return int report id
     */
    private function create_report($source, $fullname) {
        /** @var \totara_reportbuilder\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_reportbuilder');
        return $generator->create_default_custom_report([
            'fullname' => $fullname,
            'shortname' => \reportbuilder::create_shortname($fullname),
            'source' => $source
        ]);
    }

    public function test_resolve_nologgedin() {
        $r1 = $this->create_report('user', 'Test Report 1');

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Course or activity not accessible. (You are not logged in)');

        $this->resolve_graphql_mutation(
            'totara_reportbuilder_update_report_title',
            ['reportid' => $r1, 'title' => 'Test Report 2']
        );
    }

    public function test_resolve_guestuser() {
        $this->setGuestUser();

        $r1 = $this->create_report('user', 'Test Report 1');

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('No permission to edit reports');

        $this->resolve_graphql_mutation(
            'totara_reportbuilder_update_report_title',
            ['reportid' => $r1, 'title' => 'Test Report 2']
        );
    }

    public function test_resolve_normaluser() {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $r1 = $this->create_report('user', 'Test Report 1');

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('No permission to edit reports');

        $this->resolve_graphql_mutation(
            'totara_reportbuilder_update_report_title',
            ['reportid' => $r1, 'title' => 'Test Report 2']
        );
    }

    public function test_resolve_adminuser() {
        global $DB;

        $this->setAdminUser();

        $r1 = $this->create_report('user', 'Test Report 1');
        $r2 = $this->create_report('user', 'Test Report 2');

        // Check the returned result is the same
        $result = $this->resolve_graphql_mutation(
            'totara_reportbuilder_update_report_title',
            ['reportid' => $r1, 'title' => 'Test Report 3']
        );
        self::assertEquals('Test Report 3', $result);

        // Check the DB is updated
        $item = $DB->get_record('report_builder', ['id' => $r1]);
        self::assertEquals('Test Report 3', $item->fullname);

        // Check that it doesn't affect any other reports
        $item = $DB->get_record('report_builder', ['id' => $r2]);
        self::assertEquals('Test Report 2', $item->fullname);

        // Modifying a non-existent record
        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Attempted to edit a non-existent report');

        $this->resolve_graphql_mutation(
            'totara_reportbuilder_update_report_title',
            ['reportid' => 100, 'title' => 'Test Report 6']
        );
    }

    /**
     * Integration test of the AJAX mutation through the GraphQL stack.
     */
    public function test_ajax_query() {
        global $DB;

        $this->setAdminUser();

        $r1 =  $this->create_report('user' , 'Test Report 1');
        $r2 =  $this->create_report('user' , 'Test Report 2');

        $result = $this->execute_graphql_operation(
            'totara_reportbuilder_update_report_title',
            ['reportid' => $r1, 'title' => 'Test Report 3']
        );

        $result = $result->toArray(true);
        self::assertArrayHasKey('data', $result);
        self::assertSame(
            ['totara_reportbuilder_update_report_title' => 'Test Report 3'],
            $result['data']
        );

        $item = $DB->get_record('report_builder', ['id' => $r1]);
        self::assertEquals('Test Report 3', $item->fullname);

        $item = $DB->get_record('report_builder', ['id' => $r2]);
        self::assertEquals('Test Report 2', $item->fullname);
    }

}