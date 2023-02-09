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
use container_workspace\workspace;
use core\json_editor\node\paragraph;

class container_workspace_webapi_update_workspace_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    /**
     * @return void
     */
    public function test_update_workspace_from_private_to_public(): void {
        $generator = $this->getDataGenerator();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $this->setAdminUser();
        $workspace = $workspace_generator->create_private_workspace('wowop hop hko');

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Cannot update to public workspace');

        $this->resolve_graphql_mutation(
            'container_workspace_update',
            [
                'id' => $workspace->get_id(),
                'private' => false,
                'hidden' => false
            ]
        );
    }

    /**
     * @return void
     */
    public function test_update_workspace_from_public_to_private(): void {
        $generator = $this->getDataGenerator();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $this->setAdminUser();

        $workspace = $workspace_generator->create_workspace();

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Cannot update to private workspace");
        $this->resolve_graphql_mutation(
            'container_workspace_update',
            [
                'id' => $workspace->get_id(),
                'private' => true
            ]
        );
    }

    /**
     * @return void
     */
    public function test_update_hidden_workspace_to_private_workspace(): void {
        $this->setAdminUser();
        $generator = $this->getDataGenerator();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_hidden_workspace('hooh oookoko');

        $this->assertTrue($workspace->is_hidden());
        $this->assertFalse($workspace->is_private());
        $this->assertFalse($workspace->is_public());

        /** @var workspace $updated_workspace */
        $updated_workspace = $this->resolve_graphql_mutation(
            'container_workspace_update',
            [
                'id' => $workspace->get_id(),
                'private' => true,
                'hidden' => false
            ]
        );

        $this->assertSame($updated_workspace->get_id(), $workspace->get_id());
        $this->assertTrue($updated_workspace->is_private());
        $this->assertFalse($updated_workspace->is_hidden());
        $this->assertFalse($updated_workspace->is_public());
    }

    /**
     * @return void
     */
    public function test_update_hidden_workspace_with_public_workspace(): void {
        $this->setAdminUser();
        $generator = $this->getDataGenerator();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace('ddu ddu ddu');

        $this->assertTrue($workspace->is_public());

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Cannot have a hidden public workspace");
        $this->resolve_graphql_mutation(
            'container_workspace_update',
            [
                'id' => $workspace->get_id(),
                'private' => false,
                'hidden' => true
            ]
        );
    }

    /**
     * @return void
     */
    public function test_update_private_to_hidden_workspace(): void {
        $this->setAdminUser();
        $generator = $this->getDataGenerator();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_private_workspace('oookokokokoko');

        $this->assertTrue($workspace->is_private());
        $this->assertFalse($workspace->is_hidden());

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Cannot go down to hidden workspace");

        $this->resolve_graphql_mutation(
            'container_workspace_update',
            [
                'id' => $workspace->get_id(),
                'private' => true,
                'hidden' => true
            ]
        );
    }

    /**
     * @return void
     */
    public function test_update_workspace_with_null_description(): void {
        global $DB;

        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);
        $original_description = json_encode([
            'type' => 'doc',
            'content' => [
                paragraph::create_json_node_from_text("This is the text")
            ]
        ]);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace(null, $original_description, FORMAT_JSON_EDITOR);

        $workspace_id = $workspace->get_id();
        self::assertEquals($original_description, $DB->get_field('course', 'summary', ['id' => $workspace_id]));

        // Run mutation to update description.
        $this->resolve_graphql_mutation(
            'container_workspace_update',
            [
                'id' => $workspace_id,
                'name' => $workspace->get_name(),
                'description' => null
            ]
        );

        $after_updated_description = $DB->get_field('course', 'summary', ['id' => $workspace_id]);
        self::assertNotEquals($original_description, $after_updated_description);

        self::assertEmpty($after_updated_description);
        self::assertNotNull($after_updated_description);
    }

    /**
     * @return void
     */
    public function test_update_workspace_without_description_specified(): void {
        global $DB;

        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);
        $original_description = json_encode([
            'type' => 'doc',
            'content' => [
                paragraph::create_json_node_from_text("This is the text")
            ]
        ]);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace(null, $original_description, FORMAT_JSON_EDITOR);

        $workspace_id = $workspace->get_id();
        self::assertEquals($original_description, $DB->get_field('course', 'summary', ['id' => $workspace_id]));

        // Run mutation to update description.
        $this->resolve_graphql_mutation(
            'container_workspace_update',
            [
                'id' => $workspace_id,
                'name' => 'New Name',
            ]
        );

        $after_updated_description = $DB->get_field('course', 'summary', ['id' => $workspace_id]);
        self::assertEquals($original_description, $after_updated_description);
        self::assertNotEquals($workspace->get_name(), $DB->get_field('course', 'fullname', ['id' => $workspace_id]));
    }

    public function test_update_workspace_with_hashtag(): void {
        global $CFG, $DB;

        $this->setAdminUser();
        $generator = $this->getDataGenerator();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace('oookokokokoko');

        $summary = '{"type":"doc","content":[{"type":"paragraph","content":[{"type":"text","text":"This is a summary with a "},{"type":"hashtag","attrs":{"text":"testme"}},{"type":"text","text":" hashtag."}]}]}';

        $this->resolve_graphql_mutation(
            'container_workspace_update',
            [
                'id' => $workspace->get_id(),
                'name' => $workspace->get_name(),
                'description' => $summary,
                'description_format' => FORMAT_JSON_EDITOR,
                'private' => false,
                'hidden' => false
            ]
        );

        // Check that hashtag was identified and stored.
        $where = "tagcollid = " . $CFG->hashtag_collection_id;
        $sql_result = $DB->get_field_select('tag', 'name', $where, null, MUST_EXIST);
        $this->assertEquals('testme', $sql_result);
    }

    public function test_update_workspace_with_long_name(): void {

        $this->setAdminUser();
        $generator = $this->getDataGenerator();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace('oookokokokoko');

        $this->expectException('coding_exception');
        $this->expectExceptionMessage('The workspace name exceeds the maximum length');

        $this->resolve_graphql_mutation(
            'container_workspace_update',
            [
                'id' => $workspace->get_id(),
                'name' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vivamus volutpat accumsan ligula.
                          Curabitur ut euismod tellus, eget facilisis metus. Fusce eu hendrerit risus, non
                          bibendum arcu. Donec iaculis porta arcu ut sollicitudin. Phasellus tempus elit nisi',
            ]
        );
    }
}