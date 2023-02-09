<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * @author David Curry <david.curry@totaralearning.com>
 * @package mod_resource
 */

defined('MOODLE_INTERNAL') || die();

use mod_resource\webapi\resolver\query;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * Tests the mod resource (file) webapi query.
 */
class mod_resource_webapi_resolver_query_resource_testcase extends advanced_testcase {

    use webapi_phpunit_helper;

    /**
     * Create a course with a resource (file), and a user enrolled on the course
     * @return array [$resource, $user]
     */
    public function create_resource_data(): array {
        global $USER;

        $this->setAdminUser();
        // Setup test data.
        $course = $this->getDataGenerator()->create_course();

        $fs = get_file_storage();
        $usercontext = context_user::instance($USER->id);

        $record = new stdClass();
        $record->course = $course->id;
        $record->files = file_get_unused_draft_itemid();

        // Attach the main file. We put them in the draft area, create_module will move them.
        $filerecord = array(
            'contextid' => $usercontext->id,
            'component' => 'user',
            'filearea'  => 'draft',
            'itemid'    => $record->files,
            'filepath'  => '/',
            'filename'  => 'mainfile',
            'sortorder' => 1
        );
        $fs->create_file_from_string($filerecord, 'Test resource file');

        // Attach a second file.
        $filerecord['filename'] = 'extrafile';
        $filerecord['sortorder'] = 0;
        $fs->create_file_from_string($filerecord, 'Test resource file 2');

        $resource = $this->getDataGenerator()->create_module('resource', $record);

        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'student', 'manual');

        return [$resource, $user];
    }

    /**
     * Test the results of the query when the current user is not logged in.
     */
    public function test_resolve_no_login() {
        list($resource, $user) = $this->create_resource_data();
        $this->setGuestUser();

        $user = $this->getDataGenerator()->create_user();

        $this->expectException(require_login_exception::class);
        $this->expectExceptionMessage('Course or activity not accessible. (Not enrolled)');

        $this->resolve_graphql_query('mod_resource_resource', ['resourceid' => $resource->id]);
    }

    /**
     * Test the results of the query when called on a valid course by a user not enrolled on said course
     */
    public function test_resolver_user_unenrolled() {
        list($resource, $user) = $this->create_resource_data();
        $u2 = $this->getDataGenerator()->create_user();
        $this->setUser($u2);

        $user = $this->getDataGenerator()->create_user();

        $this->expectException(require_login_exception::class);
        $this->expectExceptionMessage('Course or activity not accessible. (Not enrolled)');

        $this->resolve_graphql_query('mod_resource_resource', ['resourceid' => $resource->id]);
    }

    /**
     * Test the results of the query when called with an invalid instanceid
     */
    public function test_resolver_invalid_instanceid() {
        list($resource, $user) = $this->create_resource_data();
        $this->setUser($user);

        $user = $this->getDataGenerator()->create_user();

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Invalid course module ID');

        $this->resolve_graphql_query('mod_resource_resource', ['resourceid' => $resource->id * 2]);
    }

    /**
     * Test the results of the query when called on a valid course by an enrolled user
     */
    public function test_resolver_valid_usercall() {
        list($resource, $user) = $this->create_resource_data();
        $this->setUser($user);

        $result = $this->resolve_graphql_query('mod_resource_resource', ['resourceid' => $resource->id]);

        $this->assertNotEmpty($result['moduleinfo']);
        $this->assertNotEmpty($result['fileinfo']);
    }

    /**
     * Test the results of the query affected by visibility settings when called on a valid course by an enrolled user.
     */
    public function test_resolver_valid_usercall_visibility() {
        global $DB;

        list($resource, $user) = $this->create_resource_data();
        $this->setUser($user);

        // Update the visibility for the resource.
        $audvisibility = get_config(null, 'audiencevisibility');
        $this->assertSame('0', $audvisibility); // Make sure audvis is off for ease.

        $cm = get_coursemodule_from_instance("resource", $resource->id, $resource->course, false, MUST_EXIST);
        $DB->set_field('course_modules', 'visible', '0', ['id' => $cm->id]);

        // Reset and re-fetch the cminfo cache.
        get_fast_modinfo(0,0,true);
        $modinfo = get_fast_modinfo($resource->course);
        $cminfos = $modinfo->get_instances_of('resource');

        try {
            $this->resolve_graphql_query('mod_resource_resource', ['resourceid' => $resource->id]);
            $this->fail('Expected exception was not thrown');
        } catch (require_login_exception $e) {
            $this->assertStringContainsString('Course or activity not accessible. (Activity is hidden)', $e->getMessage());
        }

        // Now hide the course and check the results are 0
        $DB->set_field('course', 'visible', '0', ['id' => $resource->course]);

        // Clear the course visibility cache
        cache_helper::purge_by_definition('totara_core', 'totara_course_is_viewable', ['userid' => $user->id]);

        try {
            $this->resolve_graphql_query('mod_resource_resource', ['resourceid' => $resource->id]);
            $this->fail('Expected exception was not thrown');
        } catch (require_login_exception $e) {
            $this->assertStringContainsString('Course or activity not accessible. (Course is hidden)', $e->getMessage());
        }

        // Check the admin can still see everything.
        $this->setAdminUser();
        // Reset and re-fetch the cminfo cache.
        get_fast_modinfo(0,0,true);
        $modinfo = get_fast_modinfo($resource->course);

        $results = $this->resolve_graphql_query('mod_resource_resource', ['resourceid' => $resource->id]);
        $this->assertSame($resource->id, $results['moduleinfo']->id);
    }

    /**
     * Test the results of the AJAX query through the GraphQL stack.
     */
    public function test_ajax_query() {
        global $CFG;

        list($resource, $user) = $this->create_resource_data();
        $this->setUser($user);

        $cm = get_coursemodule_from_instance('resource', $resource->id, null, true, MUST_EXIST);
        $context = context_module::instance($cm->id);
        $fileurl = $CFG->wwwroot . '/pluginfile.php/'.$context->id.'/mod_resource/content/0/mainfile';


        $result = $this->execute_graphql_operation('mod_resource_resource', ['resourceid' => $resource->id]);
        $data = $result->toArray()['data'];
        $expected = [
            "mod_resource_resource" => [
                "id" => "{$resource->id}",
                "mimetype" => "text/plain",
                "size" => 18,
                "fileurl" => $fileurl
            ]
        ];
        $this->assertSame($expected, $data);
    }
}
