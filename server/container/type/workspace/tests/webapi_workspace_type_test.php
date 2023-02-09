<?php
/**
 * This file is part of Totara Learn
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package container_workspace
 */
defined('MOODLE_INTERNAL') || die();

use totara_webapi\phpunit\webapi_phpunit_helper;
use container_workspace\local\workspace_helper;
use core\format;
use container_workspace\interactor\workspace\interactor as workspace_interactor;
use container_workspace\query\workspace\access;
use core\files\file_helper;
use container_workspace\workspace;

class container_workspace_webapi_workspace_type_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    /**
     * @return void
     */
    public function test_resolve_field_workspace_name_without_format(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $workspace = workspace_helper::create_workspace("Workspace 101", $user_one->id);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Invalid format given");

        $this->resolve_graphql_type(
            'container_workspace_workspace',
            'name',
            $workspace
        );
    }

    /**
     * @return void
     */
    public function test_resolve_field_workspace_name_with_format_raw(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $xss_name = /** @lang text */"<script>alert('workspace 101');</script>";
        $workspace = workspace_helper::create_workspace($xss_name, $user_one->id);

        $name = $this->resolve_graphql_type(
            'container_workspace_workspace',
            'name',
            $workspace,
            ['format' => format::FORMAT_RAW],
            $workspace->get_context()
        );

        $this->assertEquals($xss_name, $name);
    }

    /**
     * @return void
     */
    public function test_resolve_field_workspace_name_with_format_html(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $xss_name = /** @lang text */"<script>alert('workspace 101');</script>";
        $workspace = workspace_helper::create_workspace($xss_name, $user_one->id);

        $name = $this->resolve_graphql_type(
            'container_workspace_workspace',
            'name',
            $workspace,
            ['format' => format::FORMAT_HTML],
            $workspace->get_context()
        );

        $clean_string = format_string($xss_name);
        $this->assertEquals($clean_string, $name);
    }

    /**
     * @return void
     */
    public function test_resolve_field_workspace_name_with_format_plain(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $xss_name = /** @lang text */"<script>alert('workspace 101');</script>";
        $workspace = workspace_helper::create_workspace($xss_name, $user_one->id);

        $name = $this->resolve_graphql_type(
            'container_workspace_workspace',
            'name',
            $workspace,
            ['format' => format::FORMAT_PLAIN],
            $workspace->get_context()
        );

        $this->assertEquals("alert('workspace 101');", $name);
    }

    /**
     * @return void
     */
    public function test_resolve_field_worksapce_id(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        $workspace_id = $this->resolve_graphql_type(
            'container_workspace_workspace',
            'id',
            $workspace,
            [],
            $workspace->get_context()
        );

        $this->assertEquals($workspace->get_id(), $workspace_id);
    }

    /**
     * @return void
     */
    public function test_resolve_field_workspace_interactor(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        /** @var workspace_interactor $workspace_interactor */
        $workspace_interactor = $this->resolve_graphql_type(
            'container_workspace_workspace',
            'interactor',
            $workspace,
            [],
            $workspace->get_context()
        );

        $this->assertInstanceOf(workspace_interactor::class, $workspace_interactor);

        $this->assertEquals($workspace->get_id(), $workspace_interactor->get_workspace()->get_id());
        $this->assertEquals($user_one->id, $workspace_interactor->get_user_id());
    }

    /**
     * @return void
     */
    public function test_resolve_field_workspace_owner(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        $owner = $this->resolve_graphql_type(
            'container_workspace_workspace',
            'owner',
            $workspace,
            [],
            $workspace->get_context()
        );

        $this->assertIsObject($owner);
        $this->assertObjectHasAttribute('id', $owner);

        $this->assertEquals($user_one->id, $owner->id);
    }

    /**
     * @return void
     */
    public function test_resolve_field_workspace_owner_when_owner_is_unset(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        // Remove the owmner
        $workspace->remove_user();

        $owner = $this->resolve_graphql_type(
            'container_workspace_workspace',
            'owner',
            $workspace,
            [],
            $workspace->get_context()
        );

        $this->assertNull($owner);
    }

    /**
     * @return void
     */
    public function test_resolve_field_workspace_total_members(): void {
        $this->setAdminUser();
        $generator = $this->getDataGenerator();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        $total_members = $this->resolve_graphql_type(
            'container_workspace_workspace',
            'total_members',
            $workspace,
            [],
            $workspace->get_context()
        );

        $this->assertEquals(1, $total_members);
    }

    /**
     * @return void
     */
    public function test_resolve_field_workspace_total_audiences(): void {
        $this->setAdminUser();
        $generator = $this->getDataGenerator();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        $total_audiences = $this->resolve_graphql_type(
            'container_workspace_workspace',
            'total_audiences',
            $workspace,
            [],
            $workspace->get_context()
        );

        $this->assertEquals(0, $total_audiences);
    }

    /**
     * @return void
     */
    public function test_resolve_field_workspace_access(): void {
        $this->setAdminUser();
        $generator = $this->getDataGenerator();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_private_workspace();

        $access_value = $this->resolve_graphql_type(
            'container_workspace_workspace',
            'access',
            $workspace,
            [],
            $workspace->get_context()
        );

        $this->assertSame(access::get_code(access::PRIVATE), $access_value);
    }

    /**
     * @return void
     */
    public function test_resolve_field_workspace_image(): void {
        global $CFG;

        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);
        require_once("{$CFG->dirroot}/lib/filelib.php");

        $file_record = new stdClass();
        $file_record->itemid = file_get_unused_draft_itemid();
        $file_record->component = 'user';
        $file_record->filearea = 'draft';
        $file_record->filename = 'image_page.png';
        $file_record->filepath = '/';
        $file_record->contextid = context_user::instance($user_one->id)->id;

        $fs = get_file_storage();
        $fs->create_file_from_string($file_record, "Content");

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace('Workspace 101');

        $workspace->save_image($file_record->itemid, $user_one->id);
        $context = $workspace->get_context();

        $workspace_image = $this->resolve_graphql_type(
            'container_workspace_workspace',
            'image',
            $workspace,
            [
                'theme' => 'ventura',
            ],
            $context
        );

        $file_helper = new file_helper(
            workspace::get_type(),
            workspace::IMAGE_AREA,
            $context
        );

        $stored_file_url = $file_helper->get_file_url();

        $this->assertNotNull($stored_file_url);
        $this->assertEquals($stored_file_url->out(), $workspace_image);
    }
}