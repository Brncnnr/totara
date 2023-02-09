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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package totara_job
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/totara/job/lib.php');

use totara_job\job_assignment;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * Tests the totara job assignment type resolver
 */
class totara_job_webapi_resolver_type_assignment_testcase extends advanced_testcase {

    use webapi_phpunit_helper;

    private function resolve($field, $job, array $args = []) {
        return $this->resolve_graphql_type('totara_job_assignment', $field, $job, $args);
    }

    private static function create_fake_job_assignment($data) {
        $data = (array)$data;
        if (!array_key_exists('id', $data)) {
            $data['id'] = 7;
        }
        if (!array_key_exists('userid', $data)) {
            $data['userid'] = 8;
        }
        if (!array_key_exists('idnumber', $data)) {
            $data['idnumber'] = 'job_'.$data['id'];
        }
        if (!array_key_exists('timecreated', $data)) {
            $data['timecreated'] = 1559187405;
        }
        if (!array_key_exists('timemodified', $data)) {
            $data['timemodified'] = 1559187405;
        }
        if (!array_key_exists('usermodified', $data)) {
            $data['usermodified'] = 9;
        }
        if (!array_key_exists('managerjapath', $data)) {
            $data['managerjapath'] = '/' . $data['id'];
        }
        if (!array_key_exists('sortorder', $data)) {
            $data['sortorder'] = 1;
        }
        if (!array_key_exists('positionassignmentdate', $data)) {
            $data['positionassignmentdate'] = $data['timemodified'];
        }

        // Constructor is private, but I want to directly test it, Reflection allows us to do this.
        $class = new ReflectionClass(job_assignment::class);
        $instance = $class->newInstanceWithoutConstructor();
        $construct = $class->getMethod('__construct');
        $construct->setAccessible(true);
        $construct->invoke($instance, (object)$data);
        return $instance;
    }

    private function create_job_assignment($data) {
        $data = (array)$data;
        if (!array_key_exists('userid', $data)) {
            $user = $this->getDataGenerator()->create_user();
            $data['userid'] = $user->id;
        }
        if (!array_key_exists('idnumber', $data)) {
            $data['idnumber'] = 'job_x';
        }
        $instance = job_assignment::create($data);
        return $instance;
    }

    public function test_resolve_jobs_only() {
        $job = self::create_fake_job_assignment(['id' => 7]);
        self::assertSame(
            7,
            $this->resolve('id', $job)
        );

        try {
            $this->resolve('id', ['id' => 7]);
            $this->fail('Only job_assignment instances should be accepted');
        } catch (\coding_exception $ex) {
            self::assertSame(
                'Coding error detected, it must be fixed by a programmer: Only job_assignment objects are accepted: array',
                $ex->getMessage()
            );
        }

        try {
            $this->resolve('id', (object)['id' => 7]);
            $this->fail('Only job_assignment instances should be accepted');
        } catch (\coding_exception $ex) {
            self::assertSame(
                'Coding error detected, it must be fixed by a programmer: Only job_assignment objects are accepted: object',
                $ex->getMessage()
            );
        }

        try {
            $this->resolve('id', 7);
            $this->fail('Only job_assignment instances should be accepted');
        } catch (\coding_exception $ex) {
            self::assertSame(
                'Coding error detected, it must be fixed by a programmer: Only job_assignment objects are accepted: integer',
                $ex->getMessage()
            );
        }
    }

    public function test_resolve_id() {
        self::assertSame(7, $this->resolve('id', self::create_fake_job_assignment(['id' => 7])));
        self::assertSame('7', $this->resolve('id', self::create_fake_job_assignment(['id' => '7'])));
        self::assertSame(0, $this->resolve('id', self::create_fake_job_assignment(['id' => 0])));
        self::assertSame('0', $this->resolve('id', self::create_fake_job_assignment(['id' => '0'])));
        self::assertSame(-10, $this->resolve('id', self::create_fake_job_assignment(['id' => -10])));
        self::assertSame('-10', $this->resolve('id', self::create_fake_job_assignment(['id' => '-10'])));
        self::assertSame(null, $this->resolve('id', self::create_fake_job_assignment(['id' => null])));
        self::assertSame('', $this->resolve('id', self::create_fake_job_assignment(['id' => ''])));
    }

    public function test_resolve_userid() {
        self::assertSame(7, $this->resolve('userid', self::create_fake_job_assignment(['userid' => 7])));
        self::assertSame('7', $this->resolve('userid', self::create_fake_job_assignment(['userid' => '7'])));
        self::assertSame(0, $this->resolve('userid', self::create_fake_job_assignment(['userid' => 0])));
        self::assertSame('0', $this->resolve('userid', self::create_fake_job_assignment(['userid' => '0'])));
        self::assertSame(-10, $this->resolve('userid', self::create_fake_job_assignment(['userid' => -10])));
        self::assertSame('-10', $this->resolve('userid', self::create_fake_job_assignment(['userid' => '-10'])));
        self::assertSame(null, $this->resolve('userid', self::create_fake_job_assignment(['userid' => null])));
        self::assertSame('', $this->resolve('userid', self::create_fake_job_assignment(['userid' => ''])));
    }

    public function test_resolve_user() {
        $user = $this->getDataGenerator()->create_user();
        self::assertSame((array)$user, (array)$this->resolve('user', self::create_fake_job_assignment(['userid' => $user->id])));
        self::assertSame((array)$user, (array)$this->resolve('user', $this->create_job_assignment(['userid' => $user->id])));
    }

    public function test_resolve_fullname() {
        $user = $this->getDataGenerator()->create_user();

        self::assertSame('7', $this->resolve('fullname', self::create_fake_job_assignment(['fullname' => 7])));
        self::assertSame('7', $this->resolve('fullname', self::create_fake_job_assignment(['fullname' => '7'])));
        self::assertSame('Test', $this->resolve('fullname', self::create_fake_job_assignment(['fullname' => 'Test'])));
        self::assertSame('Test & Test', $this->resolve('fullname', self::create_fake_job_assignment(['fullname' => 'Test & Test'])));
        self::assertSame('Test', $this->resolve('fullname', self::create_fake_job_assignment(['fullname' => '<p>Test</p>'])));
        self::assertSame('Test & Test', $this->resolve('fullname', self::create_fake_job_assignment(['fullname' => '<p>Test & Test</p>'])));
        self::assertSame('Unnamed job assignment (ID: job_7)', $this->resolve('fullname', self::create_fake_job_assignment(['fullname' => null])));
        self::assertSame('Unnamed job assignment (ID: job_7)', $this->resolve('fullname', self::create_fake_job_assignment(['fullname' => ''])));

        self::assertSame('7', $this->resolve('fullname', self::create_fake_job_assignment(['fullname' => 7]), ['format' => 'PLAIN']));
        self::assertSame('7', $this->resolve('fullname', self::create_fake_job_assignment(['fullname' => '7']), ['format' => 'PLAIN']));
        self::assertSame('Test', $this->resolve('fullname', self::create_fake_job_assignment(['fullname' => 'Test']), ['format' => 'PLAIN']));
        self::assertSame('Test & Test', $this->resolve('fullname', self::create_fake_job_assignment(['fullname' => 'Test & Test']), ['format' => 'PLAIN']));
        self::assertSame('Test', $this->resolve('fullname', self::create_fake_job_assignment(['fullname' => '<p>Test</p>']), ['format' => 'PLAIN']));
        self::assertSame('Test & Test', $this->resolve('fullname', self::create_fake_job_assignment(['fullname' => '<p>Test & Test</p>']), ['format' => 'PLAIN']));
        self::assertSame('Unnamed job assignment (ID: job_7)', $this->resolve('fullname', self::create_fake_job_assignment(['fullname' => null]), ['format' => 'PLAIN']));
        self::assertSame('Unnamed job assignment (ID: job_7)', $this->resolve('fullname', self::create_fake_job_assignment(['fullname' => '']), ['format' => 'PLAIN']));

        self::assertSame('7', $this->resolve('fullname', self::create_fake_job_assignment(['fullname' => 7]), ['format' => 'HTML']));
        self::assertSame('7', $this->resolve('fullname', self::create_fake_job_assignment(['fullname' => '7']), ['format' => 'HTML']));
        self::assertSame('Test', $this->resolve('fullname', self::create_fake_job_assignment(['fullname' => 'Test']), ['format' => 'HTML']));
        self::assertSame('Test &#38; Test', $this->resolve('fullname', self::create_fake_job_assignment(['fullname' => 'Test & Test']), ['format' => 'HTML']));
        self::assertSame('Test', $this->resolve('fullname', self::create_fake_job_assignment(['fullname' => '<p>Test</p>']), ['format' => 'HTML']));
        self::assertSame('Test &#38; Test', $this->resolve('fullname', self::create_fake_job_assignment(['fullname' => '<p>Test & Test</p>']), ['format' => 'HTML']));
        self::assertSame('Unnamed job assignment (ID: job_7)', $this->resolve('fullname', self::create_fake_job_assignment(['fullname' => null]), ['format' => 'HTML']));
        self::assertSame('Unnamed job assignment (ID: job_7)', $this->resolve('fullname', self::create_fake_job_assignment(['fullname' => '']), ['format' => 'HTML']));

        self::assertSame(null, $this->resolve('fullname', self::create_fake_job_assignment(['fullname' => 7, 'userid' => $user->id]), ['format' => 'RAW']));
        self::assertSame(null, $this->resolve('fullname', self::create_fake_job_assignment(['fullname' => '7', 'userid' => $user->id]), ['format' => 'RAW']));
        self::assertSame(null, $this->resolve('fullname', self::create_fake_job_assignment(['fullname' => 'Test', 'userid' => $user->id]), ['format' => 'RAW']));
        self::assertSame(null, $this->resolve('fullname', self::create_fake_job_assignment(['fullname' => 'Test & Test', 'userid' => $user->id]), ['format' => 'RAW']));
        self::assertSame(null, $this->resolve('fullname', self::create_fake_job_assignment(['fullname' => '<p>Test</p>', 'userid' => $user->id]), ['format' => 'RAW']));
        self::assertSame(null, $this->resolve('fullname', self::create_fake_job_assignment(['fullname' => '<p>Test & Test</p>', 'userid' => $user->id]), ['format' => 'RAW']));
        self::assertSame(null, $this->resolve('fullname', self::create_fake_job_assignment(['fullname' => null, 'userid' => $user->id]), ['format' => 'RAW']));
        self::assertSame(null, $this->resolve('fullname', self::create_fake_job_assignment(['fullname' => '', 'userid' => $user->id]), ['format' => 'RAW']));

        $this->setAdminUser();

        self::assertSame(7, $this->resolve('fullname', self::create_fake_job_assignment(['fullname' => 7, 'userid' => $user->id]), ['format' => 'RAW']));
        self::assertSame('7', $this->resolve('fullname', self::create_fake_job_assignment(['fullname' => '7', 'userid' => $user->id]), ['format' => 'RAW']));
        self::assertSame('Test', $this->resolve('fullname', self::create_fake_job_assignment(['fullname' => 'Test', 'userid' => $user->id]), ['format' => 'RAW']));
        self::assertSame('Test & Test', $this->resolve('fullname', self::create_fake_job_assignment(['fullname' => 'Test & Test', 'userid' => $user->id]), ['format' => 'RAW']));
        self::assertSame('<p>Test</p>', $this->resolve('fullname', self::create_fake_job_assignment(['fullname' => '<p>Test</p>', 'userid' => $user->id]), ['format' => 'RAW']));
        self::assertSame('<p>Test & Test</p>', $this->resolve('fullname', self::create_fake_job_assignment(['fullname' => '<p>Test & Test</p>', 'userid' => $user->id]), ['format' => 'RAW']));
        self::assertSame('Unnamed job assignment (ID: job_7)', $this->resolve('fullname', self::create_fake_job_assignment(['fullname' => null, 'userid' => $user->id]), ['format' => 'RAW']));
        self::assertSame('Unnamed job assignment (ID: job_7)', $this->resolve('fullname', self::create_fake_job_assignment(['fullname' => '', 'userid' => $user->id]), ['format' => 'RAW']));
    }
    public function test_resolve_shortname() {
        $user = $this->getDataGenerator()->create_user();

        self::assertSame('7', $this->resolve('shortname', self::create_fake_job_assignment(['shortname' => 7])));
        self::assertSame('7', $this->resolve('shortname', self::create_fake_job_assignment(['shortname' => '7'])));
        self::assertSame('Test', $this->resolve('shortname', self::create_fake_job_assignment(['shortname' => 'Test'])));
        self::assertSame('Test & Test', $this->resolve('shortname', self::create_fake_job_assignment(['shortname' => 'Test & Test'])));
        self::assertSame('Test', $this->resolve('shortname', self::create_fake_job_assignment(['shortname' => '<p>Test</p>'])));
        self::assertSame('Test & Test', $this->resolve('shortname', self::create_fake_job_assignment(['shortname' => '<p>Test & Test</p>'])));
        self::assertSame(null, $this->resolve('shortname', self::create_fake_job_assignment(['shortname' => null])));
        self::assertSame(null, $this->resolve('shortname', self::create_fake_job_assignment(['shortname' => ''])));

        self::assertSame('7', $this->resolve('shortname', self::create_fake_job_assignment(['shortname' => 7]), ['format' => 'PLAIN']));
        self::assertSame('7', $this->resolve('shortname', self::create_fake_job_assignment(['shortname' => '7']), ['format' => 'PLAIN']));
        self::assertSame('Test', $this->resolve('shortname', self::create_fake_job_assignment(['shortname' => 'Test']), ['format' => 'PLAIN']));
        self::assertSame('Test & Test', $this->resolve('shortname', self::create_fake_job_assignment(['shortname' => 'Test & Test']), ['format' => 'PLAIN']));
        self::assertSame('Test', $this->resolve('shortname', self::create_fake_job_assignment(['shortname' => '<p>Test</p>']), ['format' => 'PLAIN']));
        self::assertSame('Test & Test', $this->resolve('shortname', self::create_fake_job_assignment(['shortname' => '<p>Test & Test</p>']), ['format' => 'PLAIN']));
        self::assertSame(null, $this->resolve('shortname', self::create_fake_job_assignment(['shortname' => null]), ['format' => 'PLAIN']));
        self::assertSame(null, $this->resolve('shortname', self::create_fake_job_assignment(['shortname' => '']), ['format' => 'PLAIN']));

        self::assertSame('7', $this->resolve('shortname', self::create_fake_job_assignment(['shortname' => 7]), ['format' => 'HTML']));
        self::assertSame('7', $this->resolve('shortname', self::create_fake_job_assignment(['shortname' => '7']), ['format' => 'HTML']));
        self::assertSame('Test', $this->resolve('shortname', self::create_fake_job_assignment(['shortname' => 'Test']), ['format' => 'HTML']));
        self::assertSame('Test &#38; Test', $this->resolve('shortname', self::create_fake_job_assignment(['shortname' => 'Test & Test']), ['format' => 'HTML']));
        self::assertSame('Test', $this->resolve('shortname', self::create_fake_job_assignment(['shortname' => '<p>Test</p>']), ['format' => 'HTML']));
        self::assertSame('Test &#38; Test', $this->resolve('shortname', self::create_fake_job_assignment(['shortname' => '<p>Test & Test</p>']), ['format' => 'HTML']));
        self::assertSame(null, $this->resolve('shortname', self::create_fake_job_assignment(['shortname' => null]), ['format' => 'HTML']));
        self::assertSame(null, $this->resolve('shortname', self::create_fake_job_assignment(['shortname' => '']), ['format' => 'HTML']));

        self::assertSame(null, $this->resolve('shortname', self::create_fake_job_assignment(['shortname' => 7, 'userid' => $user->id]), ['format' => 'RAW']));
        self::assertSame(null, $this->resolve('shortname', self::create_fake_job_assignment(['shortname' => '7', 'userid' => $user->id]), ['format' => 'RAW']));
        self::assertSame(null, $this->resolve('shortname', self::create_fake_job_assignment(['shortname' => 'Test', 'userid' => $user->id]), ['format' => 'RAW']));
        self::assertSame(null, $this->resolve('shortname', self::create_fake_job_assignment(['shortname' => 'Test & Test', 'userid' => $user->id]), ['format' => 'RAW']));
        self::assertSame(null, $this->resolve('shortname', self::create_fake_job_assignment(['shortname' => '<p>Test</p>', 'userid' => $user->id]), ['format' => 'RAW']));
        self::assertSame(null, $this->resolve('shortname', self::create_fake_job_assignment(['shortname' => '<p>Test & Test</p>', 'userid' => $user->id]), ['format' => 'RAW']));
        self::assertSame(null, $this->resolve('shortname', self::create_fake_job_assignment(['shortname' => null, 'userid' => $user->id]), ['format' => 'RAW']));
        self::assertSame(null, $this->resolve('shortname', self::create_fake_job_assignment(['shortname' => '', 'userid' => $user->id]), ['format' => 'RAW']));

        $this->setAdminUser();

        self::assertSame(7, $this->resolve('shortname', self::create_fake_job_assignment(['shortname' => 7, 'userid' => $user->id]), ['format' => 'RAW']));
        self::assertSame('7', $this->resolve('shortname', self::create_fake_job_assignment(['shortname' => '7', 'userid' => $user->id]), ['format' => 'RAW']));
        self::assertSame('Test', $this->resolve('shortname', self::create_fake_job_assignment(['shortname' => 'Test', 'userid' => $user->id]), ['format' => 'RAW']));
        self::assertSame('Test & Test', $this->resolve('shortname', self::create_fake_job_assignment(['shortname' => 'Test & Test', 'userid' => $user->id]), ['format' => 'RAW']));
        self::assertSame('<p>Test</p>', $this->resolve('shortname', self::create_fake_job_assignment(['shortname' => '<p>Test</p>', 'userid' => $user->id]), ['format' => 'RAW']));
        self::assertSame('<p>Test & Test</p>', $this->resolve('shortname', self::create_fake_job_assignment(['shortname' => '<p>Test & Test</p>', 'userid' => $user->id]), ['format' => 'RAW']));
        self::assertSame(null, $this->resolve('shortname', self::create_fake_job_assignment(['shortname' => null, 'userid' => $user->id]), ['format' => 'RAW']));
        self::assertSame(null, $this->resolve('shortname', self::create_fake_job_assignment(['shortname' => '', 'userid' => $user->id]), ['format' => 'RAW']));
    }

    public function test_resolve_idnumber() {
        self::assertSame(7, $this->resolve('idnumber', self::create_fake_job_assignment(['idnumber' => 7])));
        self::assertSame('7', $this->resolve('idnumber', self::create_fake_job_assignment(['idnumber' => '7'])));
        self::assertSame(0, $this->resolve('idnumber', self::create_fake_job_assignment(['idnumber' => 0])));
        self::assertSame('0', $this->resolve('idnumber', self::create_fake_job_assignment(['idnumber' => '0'])));
        self::assertSame('idnumber', $this->resolve('idnumber', self::create_fake_job_assignment(['idnumber' => 'idnumber'])));
        self::assertSame('こんにちは', $this->resolve('idnumber', self::create_fake_job_assignment(['idnumber' => 'こんにちは'])));
        self::assertSame(null, $this->resolve('idnumber', self::create_fake_job_assignment(['idnumber' => null])));
        self::assertSame(null, $this->resolve('idnumber', self::create_fake_job_assignment(['idnumber' => ''])));
    }

    public function test_resolve_description() {
        $user = $this->getDataGenerator()->create_user();

        $job = self::create_fake_job_assignment(['description' => 7]);
        self::assertSame('7', $this->resolve('description', $job));
        self::assertSame('7', $this->resolve('description', self::create_fake_job_assignment(['description' => '7'])));
        self::assertSame('Test', $this->resolve('description', self::create_fake_job_assignment(['description' => 'Test'])));
        self::assertSame('Test &amp; Test', $this->resolve('description', self::create_fake_job_assignment(['description' => 'Test & Test'])));
        self::assertSame('<p>Test</p>', $this->resolve('description', self::create_fake_job_assignment(['description' => '<p>Test</p>'])));
        self::assertSame('<p>Test &amp; Test</p>', $this->resolve('description', self::create_fake_job_assignment(['description' => '<p>Test & Test</p>'])));
        self::assertSame('', $this->resolve('description', self::create_fake_job_assignment(['description' => null])));
        self::assertSame('', $this->resolve('description', self::create_fake_job_assignment(['description' => ''])));

        self::assertSame('7', $this->resolve('description', self::create_fake_job_assignment(['description' => 7]), ['format' => 'PLAIN']));
        self::assertSame('7', $this->resolve('description', self::create_fake_job_assignment(['description' => '7']), ['format' => 'PLAIN']));
        self::assertSame('Test', $this->resolve('description', self::create_fake_job_assignment(['description' => 'Test']), ['format' => 'PLAIN']));
        self::assertSame('Test & Test', $this->resolve('description', self::create_fake_job_assignment(['description' => 'Test & Test']), ['format' => 'PLAIN']));
        self::assertSame("Test\n", $this->resolve('description', self::create_fake_job_assignment(['description' => '<p>Test</p>']), ['format' => 'PLAIN']));
        self::assertSame("Test & Test\n", $this->resolve('description', self::create_fake_job_assignment(['description' => '<p>Test & Test</p>']), ['format' => 'PLAIN']));
        self::assertSame('', $this->resolve('description', self::create_fake_job_assignment(['description' => null]), ['format' => 'PLAIN']));
        self::assertSame('', $this->resolve('description', self::create_fake_job_assignment(['description' => '']), ['format' => 'PLAIN']));

        self::assertSame('7', $this->resolve('description', self::create_fake_job_assignment(['description' => 7]), ['format' => 'HTML']));
        self::assertSame('7', $this->resolve('description', self::create_fake_job_assignment(['description' => '7']), ['format' => 'HTML']));
        self::assertSame('Test', $this->resolve('description', self::create_fake_job_assignment(['description' => 'Test']), ['format' => 'HTML']));
        self::assertSame('Test &amp; Test', $this->resolve('description', self::create_fake_job_assignment(['description' => 'Test & Test']), ['format' => 'HTML']));
        self::assertSame('<p>Test</p>', $this->resolve('description', self::create_fake_job_assignment(['description' => '<p>Test</p>']), ['format' => 'HTML']));
        self::assertSame('<p>Test &amp; Test</p>', $this->resolve('description', self::create_fake_job_assignment(['description' => '<p>Test & Test</p>']), ['format' => 'HTML']));
        self::assertSame('', $this->resolve('description', self::create_fake_job_assignment(['description' => null]), ['format' => 'HTML']));
        self::assertSame('', $this->resolve('description', self::create_fake_job_assignment(['description' => '']), ['format' => 'HTML']));

        self::assertSame(null, $this->resolve('description', self::create_fake_job_assignment(['description' => 7, 'userid' => $user->id]), ['format' => 'RAW']));
        self::assertSame(null, $this->resolve('description', self::create_fake_job_assignment(['description' => '7', 'userid' => $user->id]), ['format' => 'RAW']));
        self::assertSame(null, $this->resolve('description', self::create_fake_job_assignment(['description' => 'Test', 'userid' => $user->id]), ['format' => 'RAW']));
        self::assertSame(null, $this->resolve('description', self::create_fake_job_assignment(['description' => 'Test & Test', 'userid' => $user->id]), ['format' => 'RAW']));
        self::assertSame(null, $this->resolve('description', self::create_fake_job_assignment(['description' => '<p>Test</p>', 'userid' => $user->id]), ['format' => 'RAW']));
        self::assertSame(null, $this->resolve('description', self::create_fake_job_assignment(['description' => '<p>Test & Test</p>', 'userid' => $user->id]), ['format' => 'RAW']));
        self::assertSame(null, $this->resolve('description', self::create_fake_job_assignment(['description' => null, 'userid' => $user->id]), ['format' => 'RAW']));
        self::assertSame(null, $this->resolve('description', self::create_fake_job_assignment(['description' => '', 'userid' => $user->id]), ['format' => 'RAW']));

        $this->setAdminUser();

        self::assertSame('7', $this->resolve('description', self::create_fake_job_assignment(['description' => 7, 'userid' => $user->id]), ['format' => 'RAW']));
        self::assertSame('7', $this->resolve('description', self::create_fake_job_assignment(['description' => '7', 'userid' => $user->id]), ['format' => 'RAW']));
        self::assertSame('Test', $this->resolve('description', self::create_fake_job_assignment(['description' => 'Test', 'userid' => $user->id]), ['format' => 'RAW']));
        self::assertSame('Test & Test', $this->resolve('description', self::create_fake_job_assignment(['description' => 'Test & Test', 'userid' => $user->id]), ['format' => 'RAW']));
        self::assertSame('<p>Test</p>', $this->resolve('description', self::create_fake_job_assignment(['description' => '<p>Test</p>', 'userid' => $user->id]), ['format' => 'RAW']));
        self::assertSame('<p>Test & Test</p>', $this->resolve('description', self::create_fake_job_assignment(['description' => '<p>Test & Test</p>', 'userid' => $user->id]), ['format' => 'RAW']));
        self::assertSame('', $this->resolve('description', self::create_fake_job_assignment(['description' => null, 'userid' => $user->id]), ['format' => 'RAW']));
        self::assertSame('', $this->resolve('description', self::create_fake_job_assignment(['description' => '', 'userid' => $user->id]), ['format' => 'RAW']));
    }

    public function test_resolve_startdate() {
        self::assertSame('7', $this->resolve('startdate', self::create_fake_job_assignment(['startdate' => 7])));
        self::assertSame('7', $this->resolve('startdate', self::create_fake_job_assignment(['startdate' => '7'])));
        self::assertSame(null, $this->resolve('startdate', self::create_fake_job_assignment(['startdate' => 0])));
        self::assertSame(null, $this->resolve('startdate', self::create_fake_job_assignment(['startdate' => '0'])));
        self::assertSame(null, $this->resolve('startdate', self::create_fake_job_assignment(['startdate' => null])));
        self::assertSame(null, $this->resolve('startdate', self::create_fake_job_assignment(['startdate' => ''])));

        self::assertSame('1559250946', $this->resolve('startdate', self::create_fake_job_assignment(['startdate' => 1559250946])));
        self::assertSame('1559250946', $this->resolve('startdate', self::create_fake_job_assignment(['startdate' => '1559250946'])));
        self::assertSame('1559250946', $this->resolve('startdate', self::create_fake_job_assignment(['startdate' => 1559250946]), ['format' => 'TIMESTAMP']));
        self::assertSame('1559250946', $this->resolve('startdate', self::create_fake_job_assignment(['startdate' => '1559250946']), ['format' => 'TIMESTAMP']));
        self::assertSame('2019-05-31T05:15:46+0800', $this->resolve('startdate', self::create_fake_job_assignment(['startdate' => 1559250946]), ['format' => 'ISO8601']));
        self::assertSame('2019-05-31T05:15:46+0800', $this->resolve('startdate', self::create_fake_job_assignment(['startdate' => '1559250946']), ['format' => 'ISO8601']));
    }

    public function test_resolve_enddate() {
        self::assertSame('7', $this->resolve('enddate', self::create_fake_job_assignment(['enddate' => 7])));
        self::assertSame('7', $this->resolve('enddate', self::create_fake_job_assignment(['enddate' => '7'])));
        self::assertSame(null, $this->resolve('enddate', self::create_fake_job_assignment(['enddate' => 0])));
        self::assertSame(null, $this->resolve('enddate', self::create_fake_job_assignment(['enddate' => '0'])));
        self::assertSame(null, $this->resolve('enddate', self::create_fake_job_assignment(['enddate' => null])));
        self::assertSame(null, $this->resolve('enddate', self::create_fake_job_assignment(['enddate' => ''])));

        self::assertSame('1559250946', $this->resolve('enddate', self::create_fake_job_assignment(['enddate' => 1559250946])));
        self::assertSame('1559250946', $this->resolve('enddate', self::create_fake_job_assignment(['enddate' => '1559250946'])));
        self::assertSame('1559250946', $this->resolve('enddate', self::create_fake_job_assignment(['enddate' => 1559250946]), ['format' => 'TIMESTAMP']));
        self::assertSame('1559250946', $this->resolve('enddate', self::create_fake_job_assignment(['enddate' => '1559250946']), ['format' => 'TIMESTAMP']));
        self::assertSame('2019-05-31T05:15:46+0800', $this->resolve('enddate', self::create_fake_job_assignment(['enddate' => 1559250946]), ['format' => 'ISO8601']));
        self::assertSame('2019-05-31T05:15:46+0800', $this->resolve('enddate', self::create_fake_job_assignment(['enddate' => '1559250946']), ['format' => 'ISO8601']));
    }

    public function test_resolve_positionid() {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        self::assertSame(7, $this->resolve('positionid', self::create_fake_job_assignment(['userid' => $user->id, 'positionid' => 7])));
        self::assertSame('7', $this->resolve('positionid', self::create_fake_job_assignment(['userid' => $user->id, 'positionid' => '7'])));
        self::assertSame(null, $this->resolve('positionid', self::create_fake_job_assignment(['userid' => $user->id, 'positionid' => 0])));
        self::assertSame(null, $this->resolve('positionid', self::create_fake_job_assignment(['userid' => $user->id, 'positionid' => '0'])));
        self::assertSame(null, $this->resolve('positionid', self::create_fake_job_assignment(['userid' => $user->id, 'positionid' => null])));
        self::assertSame(null, $this->resolve('positionid', self::create_fake_job_assignment(['userid' => $user->id, 'positionid' => ''])));
    }

    public function test_resolve_position() {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        /** @var \totara_hierarchy\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $framework = $generator->create_pos_frame([]);
        $typeid = $generator->create_pos_type([]);
        $position1 = $generator->create_pos(['frameworkid' => $framework->id, 'typeid' => $typeid]);
        $position2 = $generator->create_pos(['frameworkid' => $framework->id, 'parentid' => $position1->id]);

        self::assertSame(null, $this->resolve('position', self::create_fake_job_assignment([])));
        self::assertSame(null, $this->resolve('position', self::create_fake_job_assignment(['userid' => $user->id, 'positionid' => null])));
        self::assertSame((array)$position1, (array)$this->resolve('position', self::create_fake_job_assignment(['userid' => $user->id, 'positionid' => $position1->id])));
        self::assertSame((array)$position2, (array)$this->resolve('position', self::create_fake_job_assignment(['userid' => $user->id, 'positionid' => $position2->id])));

        // Now give the user the capability to view organisations.
        $context = \context_user::instance($user->id);
        $roleid = $this->getDataGenerator()->create_role();
        $this->getDataGenerator()->role_assign($roleid, $user->id, $context->id);
        assign_capability('totara/hierarchy:viewposition', CAP_PROHIBIT, $roleid, $context);

        // User can't view organisations.
        self::assertSame(null, $this->resolve('position', self::create_fake_job_assignment([])));
        self::assertSame(null, $this->resolve('position', self::create_fake_job_assignment(['userid' => $user->id, 'positionid' => null])));
        self::assertSame(null, $this->resolve('position', self::create_fake_job_assignment(['userid' => $user->id, 'positionid' => $position1->id])));
        self::assertSame(null, $this->resolve('position', self::create_fake_job_assignment(['userid' => $user->id, 'positionid' => $position2->id])));
    }

    public function test_resolve_organisationid() {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        self::assertSame(7, $this->resolve('organisationid', self::create_fake_job_assignment(['userid' => $user->id, 'organisationid' => 7])));
        self::assertSame('7', $this->resolve('organisationid', self::create_fake_job_assignment(['userid' => $user->id, 'organisationid' => '7'])));
        self::assertSame(null, $this->resolve('organisationid', self::create_fake_job_assignment(['userid' => $user->id, 'organisationid' => 0])));
        self::assertSame(null, $this->resolve('organisationid', self::create_fake_job_assignment(['userid' => $user->id, 'organisationid' => '0'])));
        self::assertSame(null, $this->resolve('organisationid', self::create_fake_job_assignment(['userid' => $user->id, 'organisationid' => null])));
        self::assertSame(null, $this->resolve('organisationid', self::create_fake_job_assignment(['userid' => $user->id, 'organisationid' => ''])));
    }

    public function test_resolve_organisation() {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        /** @var \totara_hierarchy\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $framework = $generator->create_org_frame([]);
        $typeid = $generator->create_org_type([]);
        $organisation1 = $generator->create_org(['frameworkid' => $framework->id, 'typeid' => $typeid]);
        $organisation2 = $generator->create_org(['frameworkid' => $framework->id, 'parentid' => $organisation1->id]);

        // Current user can view their own organisations.
        self::assertSame(null, $this->resolve('organisation', self::create_fake_job_assignment(['userid' => $user->id])));
        self::assertSame(null, $this->resolve('organisation', self::create_fake_job_assignment(['userid' => $user->id, 'organisationid' => null])));
        self::assertSame((array)$organisation1, (array)$this->resolve('organisation', self::create_fake_job_assignment(['userid' => $user->id, 'organisationid' => $organisation1->id])));
        self::assertSame((array)$organisation2, (array)$this->resolve('organisation', self::create_fake_job_assignment(['userid' => $user->id, 'organisationid' => $organisation2->id])));

        // Now give the user the capability to view organisations.
        $context = \context_user::instance($user->id);
        $roleid = $this->getDataGenerator()->create_role();
        $this->getDataGenerator()->role_assign($roleid, $user->id, $context->id);
        assign_capability('totara/hierarchy:vieworganisation', CAP_PROHIBIT, $roleid, $context);

        // User can't view organisations.
        self::assertSame(null, $this->resolve('organisation', self::create_fake_job_assignment(['userid' => $user->id])));
        self::assertSame(null, $this->resolve('organisation', self::create_fake_job_assignment(['userid' => $user->id, 'organisationid' => null])));
        self::assertSame(null, $this->resolve('organisation', self::create_fake_job_assignment(['userid' => $user->id, 'organisationid' => $organisation1->id])));
        self::assertSame(null, $this->resolve('organisation', self::create_fake_job_assignment(['userid' => $user->id, 'organisationid' => $organisation2->id])));
    }

    public function test_resolve_managerjaid() {
        self::assertSame(7, $this->resolve('managerjaid', self::create_fake_job_assignment(['managerjaid' => 7])));
        self::assertSame('7', $this->resolve('managerjaid', self::create_fake_job_assignment(['managerjaid' => '7'])));
        self::assertSame(null, $this->resolve('managerjaid', self::create_fake_job_assignment(['managerjaid' => 0])));
        self::assertSame(null, $this->resolve('managerjaid', self::create_fake_job_assignment(['managerjaid' => '0'])));
        self::assertSame(null, $this->resolve('managerjaid', self::create_fake_job_assignment(['managerjaid' => null])));
        self::assertSame(null, $this->resolve('managerjaid', self::create_fake_job_assignment(['managerjaid' => ''])));
        self::assertSame(null, $this->resolve('managerjaid', self::create_fake_job_assignment([])));
    }

    public function test_resolve_managerja() {
        $this->setAdminUser();
        $ja = $this->create_job_assignment([]);

        self::assertSame(null, $this->resolve('managerja', self::create_fake_job_assignment([])));
        self::assertSame(null, $this->resolve('managerja', self::create_fake_job_assignment(['managerjaid' => null])));
        self::assertSame((array)$ja, (array)$this->resolve('managerja', self::create_fake_job_assignment(['managerjaid' => $ja->id])));
    }

    public function test_resolve_tempmanagerjaid() {
        self::assertSame(7, $this->resolve('tempmanagerjaid', self::create_fake_job_assignment(['tempmanagerjaid' => 7])));
        self::assertSame('7', $this->resolve('tempmanagerjaid', self::create_fake_job_assignment(['tempmanagerjaid' => '7'])));
        self::assertSame(null, $this->resolve('tempmanagerjaid', self::create_fake_job_assignment(['tempmanagerjaid' => 0])));
        self::assertSame(null, $this->resolve('tempmanagerjaid', self::create_fake_job_assignment(['tempmanagerjaid' => '0'])));
        self::assertSame(null, $this->resolve('tempmanagerjaid', self::create_fake_job_assignment(['tempmanagerjaid' => null])));
        self::assertSame(null, $this->resolve('tempmanagerjaid', self::create_fake_job_assignment(['tempmanagerjaid' => ''])));
    }

    public function test_resolve_tempmanagerja() {
        $this->setAdminUser();
        $ja = $this->create_job_assignment([]);

        self::assertSame(null, $this->resolve('tempmanagerja', self::create_fake_job_assignment([])));
        self::assertSame(null, $this->resolve('tempmanagerja', self::create_fake_job_assignment(['tempmanagerjaid' => null])));
        self::assertSame((array)$ja, (array)$this->resolve('tempmanagerja', self::create_fake_job_assignment(['tempmanagerjaid' => $ja->id])));
    }

    public function test_resolve_tempmanagerexpirydate() {
        self::assertSame('7', $this->resolve('tempmanagerexpirydate', self::create_fake_job_assignment(['tempmanagerexpirydate' => 7])));
        self::assertSame('7', $this->resolve('tempmanagerexpirydate', self::create_fake_job_assignment(['tempmanagerexpirydate' => '7'])));
        self::assertSame(null, $this->resolve('tempmanagerexpirydate', self::create_fake_job_assignment(['tempmanagerexpirydate' => 0])));
        self::assertSame(null, $this->resolve('tempmanagerexpirydate', self::create_fake_job_assignment(['tempmanagerexpirydate' => '0'])));
        self::assertSame(null, $this->resolve('tempmanagerexpirydate', self::create_fake_job_assignment(['tempmanagerexpirydate' => null])));
        self::assertSame(null, $this->resolve('tempmanagerexpirydate', self::create_fake_job_assignment(['tempmanagerexpirydate' => ''])));

        self::assertSame('1559250946', $this->resolve('tempmanagerexpirydate', self::create_fake_job_assignment(['tempmanagerexpirydate' => 1559250946])));
        self::assertSame('1559250946', $this->resolve('tempmanagerexpirydate', self::create_fake_job_assignment(['tempmanagerexpirydate' => '1559250946'])));
        self::assertSame('1559250946', $this->resolve('tempmanagerexpirydate', self::create_fake_job_assignment(['tempmanagerexpirydate' => 1559250946]), ['format' => 'TIMESTAMP']));
        self::assertSame('1559250946', $this->resolve('tempmanagerexpirydate', self::create_fake_job_assignment(['tempmanagerexpirydate' => '1559250946']), ['format' => 'TIMESTAMP']));
        self::assertSame('2019-05-31T05:15:46+0800', $this->resolve('tempmanagerexpirydate', self::create_fake_job_assignment(['tempmanagerexpirydate' => 1559250946]), ['format' => 'ISO8601']));
        self::assertSame('2019-05-31T05:15:46+0800', $this->resolve('tempmanagerexpirydate', self::create_fake_job_assignment(['tempmanagerexpirydate' => '1559250946']), ['format' => 'ISO8601']));
    }

    public function test_resolve_appraiserid() {
        self::assertSame(7, $this->resolve('appraiserid', self::create_fake_job_assignment(['appraiserid' => 7])));
        self::assertSame('7', $this->resolve('appraiserid', self::create_fake_job_assignment(['appraiserid' => '7'])));
        self::assertSame(null, $this->resolve('appraiserid', self::create_fake_job_assignment(['appraiserid' => 0])));
        self::assertSame(null, $this->resolve('appraiserid', self::create_fake_job_assignment(['appraiserid' => '0'])));
        self::assertSame(null, $this->resolve('appraiserid', self::create_fake_job_assignment(['appraiserid' => null])));
        self::assertSame(null, $this->resolve('appraiserid', self::create_fake_job_assignment(['appraiserid' => ''])));
    }

    public function test_resolve_appraiser() {
        $appraiser = $this->getDataGenerator()->create_user();

        self::assertSame(null, $this->resolve('appraiser', self::create_fake_job_assignment([])));
        self::assertSame(null, $this->resolve('appraiser', self::create_fake_job_assignment(['appraiserid' => null])));
        self::assertSame((array)$appraiser, (array)$this->resolve('appraiser', self::create_fake_job_assignment(['appraiserid' => $appraiser->id])));
    }

    public function test_resolve_staffcount() {
        $this->setAdminUser();
        $user = $this->getDataGenerator()->create_user();
        $ja = $this->create_job_assignment(['userid' => $user->id]);

        self::assertSame(0, $this->resolve('staffcount', self::create_fake_job_assignment([])));
        self::assertSame(0, $this->resolve('staffcount', $ja));
        $this->create_job_assignment(['managerjaid' => $ja->id]);
        $this->create_job_assignment(['managerjaid' => $ja->id]);
        self::assertSame(2, $this->resolve('staffcount', $ja));
    }

    public function test_resolve_tempstaffcount() {
        $this->setAdminUser();
        $user = $this->getDataGenerator()->create_user();
        $ja = $this->create_job_assignment(['userid' => $user->id]);

        self::assertSame(0, $this->resolve('tempstaffcount', self::create_fake_job_assignment([])));
        self::assertSame(0, $this->resolve('tempstaffcount', $ja));
        $this->create_job_assignment(['tempmanagerjaid' => $ja->id, 'tempmanagerexpirydate' => time() + 86400]);
        $this->create_job_assignment(['tempmanagerjaid' => $ja->id, 'tempmanagerexpirydate' => time() + 86400]);
        self::assertSame(2, $this->resolve('tempstaffcount', $ja));
    }

}
