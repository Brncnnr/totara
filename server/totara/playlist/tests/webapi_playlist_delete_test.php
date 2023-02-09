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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralearning.com>
 * @package totara_playlist
 */
defined('MOODLE_INTERNAL') || die();

use core\entity\tenant;
use core\webapi\execution_context;
use totara_engage\access\access;
use totara_engage\resource\resource_factory;
use totara_playlist\playlist;
use totara_webapi\graphql;
use totara_webapi\phpunit\webapi_phpunit_helper;

class totara_playlist_webapi_playlist_delete_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    public function test_delete_playlist() {
        global $DB;
        [$playlist, $resource_item, $owner] = $this->prepare();

        $ec = execution_context::create('ajax', 'totara_playlist_delete_playlist');
        $result = graphql::execute_operation($ec, ['id' => $playlist->get_id()]);

        $this->assertEmpty($result->errors);
        $this->assertNotEmpty($result->data);

        $record = $DB->get_record('engage_resource', ['id' => $resource_item->get_id()]);
        $this->assertEquals(0, $record->countusage);
        $sql = 'SELECT 1 FROM "ttr_playlist" p WHERE p.id = :id';
        $this->assertFalse($DB->record_exists_sql($sql, ['id' => $playlist->get_id()]));
    }

    public function test_delete_playlist_other_user() {
        [$playlist, $resource_item, $owner] = $this->prepare();
        $user = $this->getDataGenerator()->create_user();

        $this->setUser($user);
        $ec = execution_context::create('ajax', 'totara_playlist_delete_playlist');
        $result = graphql::execute_operation($ec, ['id' => $playlist->get_id()]);

        $this->assertNotEmpty($result->errors);
        $this->assertEmpty($result->data);

        $this->assertStringContainsString("totara_playlist/error:delete", $result->errors[0]->getMessage());
    }

    public function test_delete_playlist_other_tenant_manager() {
        $generator = $this->getDataGenerator();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');

        $tenant_generator->enable_tenants();
        $tenant1 = new tenant($tenant_generator->create_tenant());
        $tenant2 = new tenant($tenant_generator->create_tenant());

        $user1 = $generator->create_user(['tenantid' => $tenant1->id]);
        $user2 = $generator->create_user(['tenantid' => $tenant2->id]);

        // Make user2 tenant manager
        $tenant2_context = context_tenant::instance($tenant2->id);
        $roleid = $this->getDataGenerator()->create_role();
        assign_capability('totara/engage:manage', CAP_ALLOW, $roleid, $tenant2_context);
        role_assign($roleid, $user2->id, $tenant2_context);

        $this->setUser($user1);
        $playlist = playlist::create('Hello world', access::PUBLIC);

        $this->setUser($user2);
        $ec = execution_context::create('ajax', 'totara_playlist_delete_playlist');
        $result = graphql::execute_operation($ec, ['id' => $playlist->get_id()]);

        $this->assertNotEmpty($result->errors);
        $this->assertEmpty($result->data);

        $this->assertStringContainsString("totara_playlist/error:delete", $result->errors[0]->getMessage());

    }

    public function test_delete_playlist_same_tenant_manager() {
        $generator = $this->getDataGenerator();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');

        $tenant_generator->enable_tenants();
        $tenant1 = new tenant($tenant_generator->create_tenant());

        $user1 = $generator->create_user(['tenantid' => $tenant1->id]);
        $user2 = $generator->create_user(['tenantid' => $tenant1->id]);

        $this->setUser($user1);
        $playlist = playlist::create('Hello world', access::PUBLIC);

        // Another user within same tenant cannot remove playlist.
        $this->setUser($user2);
        $ec = execution_context::create('ajax', 'totara_playlist_delete_playlist');
        $result = graphql::execute_operation($ec, ['id' => $playlist->get_id()]);

        $this->assertNotEmpty($result->errors);
        $this->assertEmpty($result->data);

        $this->assertStringContainsString("totara_playlist/error:delete", $result->errors[0]->getMessage());

        // Assign management capabilities
        $tenant1_context = context_tenant::instance($tenant1->id);
        $roleid = $this->getDataGenerator()->create_role();
        assign_capability('totara/engage:manage', CAP_ALLOW, $roleid, $tenant1_context);
        role_assign($roleid, $user2->id, $tenant1_context);

        // Deletion must be successful.
        $ec = execution_context::create('ajax', 'totara_playlist_delete_playlist');
        $result = graphql::execute_operation($ec, ['id' => $playlist->get_id()]);

        $this->assertEmpty($result->errors);
        $this->assertNotEmpty($result->data);
        $this->assertTrue($result->data['result']);
    }

    /**
     * Prepare common instances
     * @return array
     */
    protected function prepare() {
        $generator = $this->getDataGenerator();
        $owner = $generator->create_user();
        $this->setUser($owner);

        /** @var \engage_article\testing\generator $articlegen */
        $articlegen = $generator->get_plugin_generator('engage_article');
        $article = $articlegen->create_article([
            'access' => access::PUBLIC
        ]);

        $playlist = playlist::create('Hello world', access::PUBLIC);
        $resource_item = resource_factory::create_instance_from_id($article->get_id());
        $playlist->add_resource($resource_item);

        return [$playlist, $resource_item, $owner];
    }
}