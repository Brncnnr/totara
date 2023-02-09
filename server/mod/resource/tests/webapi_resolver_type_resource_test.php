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

use totara_webapi\phpunit\webapi_phpunit_helper;
use mod_resource\webapi\resolver\query;
use core\format;
/**
 *
 * Tests the mod resource (file) webapi type.
 */
class mod_resource_webapi_resolver_type_resource_testcase extends advanced_testcase {

    use webapi_phpunit_helper;

    /**
     * Create a course with a resource (file), and a user enrolled on the course
     * @return array [$resource, $user]
     */
    private function create_resource_data() : array {
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
     * Format the resource to match the expectations of the type resolver.
     */
    private function format_resource($resource) : array {
        $data = [];

        $data['moduleinfo'] = $resource;

        $cm = get_coursemodule_from_instance('resource', $resource->id, null, true, MUST_EXIST);
        $context = context_module::instance($cm->id);
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'mod_resource', 'content', 0, 'sortorder DESC, id ASC', false);

        if (count($files) < 1) {
            return null;
        } else {
            $data['fileinfo'] = reset($files);
            unset($files);
        }

        return $data;
    }

    /**
     * Test that the file type resolver only works when given the expected data
     * e.g. ['moduleinfo' => mdl_resource.record, 'fileinfo' => \stored_file]
     */
    public function test_resolve_invalid_type(): void {
        [$resource, ] = $this->create_resource_data();

        $cm = get_coursemodule_from_instance('resource', $resource->id, null, true, MUST_EXIST);
        $context = context_module::instance($cm->id);

        // Test failure with invalid type data.
        $data = new \stdClass();
        $this->expectException(Error::class);
        $this->expectExceptionMessage('Cannot use object of type stdClass as array');
        $this->resolve_graphql_type('mod_resource_resource', 'id', $data, [], $context);
    }

    /**
     * Test failure with empty data.
     *
     * @return void
     */
    public function test_resolve_empty_data(): void {
        [$resource,] = $this->create_resource_data();

        $cm = get_coursemodule_from_instance('resource', $resource->id, null, true, MUST_EXIST);
        $context = context_module::instance($cm->id);

        $data = [];
        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Resource file type resolver did not receive expected data');
        $this->resolve_graphql_type('mod_resource_resource', 'id', $data, [], $context);
    }

    /**
     * Test failure with only moduleinfo.
     *
     * @return void
     */
    public function test_resolve_moduleinfo_only(): void {
        [$resource,] = $this->create_resource_data();

        $cm = get_coursemodule_from_instance('resource', $resource->id, null, true, MUST_EXIST);
        $context = context_module::instance($cm->id);
        $expected = $this->format_resource($resource);

        $data = ['moduleinfo' => $expected['moduleinfo']];
        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Resource file type resolver did not receive expected data');
        $this->resolve_graphql_type('mod_resource_resource', 'id', $data, [], $context);
    }

    /**
     * Test failure with invalid file info.
     *
     * @return void
     */
    public function test_resolve_invalid_fileinfo(): void {
        [$resource,] = $this->create_resource_data();

        $cm = get_coursemodule_from_instance('resource', $resource->id, null, true, MUST_EXIST);
        $context = context_module::instance($cm->id);
        $expected = $this->format_resource($resource);

        $finfo = new stdClass();
        $data = ['moduleinfo' => $expected['moduleinfo'], 'fileinfo' => $finfo];
        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Resource file type resolver did not receive expected data');
        $this->resolve_graphql_type('mod_resource_resource', 'id', $data, [], $context);
    }

    /**
     * Test failure with only fileinfo.
     *
     * @return void
     */
    public function test_resolve_fileinfo_only(): void {
        [$resource,] = $this->create_resource_data();

        $cm = get_coursemodule_from_instance('resource', $resource->id, null, true, MUST_EXIST);
        $context = context_module::instance($cm->id);
        $expected = $this->format_resource($resource);

        $data = ['fileinfo' => $expected['fileinfo']];
        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Resource file type resolver did not receive expected data');
        $this->resolve_graphql_type('mod_resource_resource', 'id', $data, [], $context);
    }

    /**
     * Test the file type resolver for the id field
     */
    public function test_resolve_id() {
        list($resource, $user) = $this->create_resource_data();

        $data = $this->format_resource($resource);

        $cm = get_coursemodule_from_instance('resource', $resource->id, null, true, MUST_EXIST);
        $context = context_module::instance($cm->id);
        $value = $this->resolve_graphql_type('mod_resource_resource', 'id', $data, [], $context);
        $this->assertTrue(is_string($value));
        $this->assertEquals($resource->id, $value);
    }
    /**
     * Test the type resolver for the mimetype fieldof the resource (file).
     */
    public function test_resolve_mimetype() {
        list($resource, $user) = $this->create_resource_data();

        $data = $this->format_resource($resource);

        $cm = get_coursemodule_from_instance('resource', $resource->id, null, true, MUST_EXIST);
        $context = context_module::instance($cm->id);
        $value = $this->resolve_graphql_type('mod_resource_resource', 'mimetype', $data, [], $context);
        $this->assertTrue(is_string($value));
        $this->assertEquals('text/plain', $value);
    }

    /**
     * Test the type resolver for the size field
     */
    public function test_resolve_filesize() {
        list($resource, $user) = $this->create_resource_data();

        $data = $this->format_resource($resource);

        $cm = get_coursemodule_from_instance('resource', $resource->id, null, true, MUST_EXIST);
        $context = context_module::instance($cm->id);
        $value = $this->resolve_graphql_type('mod_resource_resource', 'size', $data, [], $context);
        $this->assertTrue(is_string($value));
        $this->assertEquals('18', $value); // 18 bytes, this is a tiny text test file.
    }

    /**
     * Test the type resolver for the size field
     */
    public function test_resolve_fileurl() {
        global $CFG;
        list($resource, $user) = $this->create_resource_data();

        $data = $this->format_resource($resource);

        $cm = get_coursemodule_from_instance('resource', $resource->id, null, true, MUST_EXIST);
        $context = context_module::instance($cm->id);
        $value = $this->resolve_graphql_type('mod_resource_resource', 'fileurl', $data, ['format' => format::FORMAT_PLAIN], $context);
        $this->assertTrue(is_string($value));
        $expected = $CFG->wwwroot . '/pluginfile.php/'.$context->id.'/mod_resource/content/0/mainfile';
        $this->assertEquals($expected, $value);
    }
}
