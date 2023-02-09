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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package editor_weka
 */
defined('MOODLE_INTERNAL') || die();

use core\orm\query\builder;
use editor_weka\local\file_helper;

class editor_weka_file_helper_testcase extends advanced_testcase {

    public function test_get_upload_repository_as_not_logged_in_user(): void {
        $repositories = file_helper::get_upload_repository(context_system::instance()->id);
        $this->assertEquals(0, $repositories['repository_id']);
        $this->assertStringContainsString('repository_ajax.php?action=upload', $repositories['url']);

        $user = $this->getDataGenerator()->create_user();

        $repositories = file_helper::get_upload_repository(context_user::instance($user->id)->id);
        $this->assertEquals(0, $repositories['repository_id']);
        $this->assertStringContainsString('repository_ajax.php?action=upload', $repositories['url']);
    }

    public function test_get_upload_repository_as_admin_user(): void {
        $user = $this->getDataGenerator()->create_user();

        $this->setAdminUser();

        $expected_repository = $this->get_expected_repository();

        $repositories = file_helper::get_upload_repository();
        $this->assertEquals($expected_repository->id, $repositories['repository_id']);
        $this->assertStringContainsString('repository_ajax.php?action=upload', $repositories['url']);

        $repositories = file_helper::get_upload_repository(context_system::instance()->id);
        $this->assertEquals($expected_repository->id, $repositories['repository_id']);
        $this->assertStringContainsString('repository_ajax.php?action=upload', $repositories['url']);

        $repositories = file_helper::get_upload_repository(context_user::instance($user->id)->id);
        $this->assertEquals($expected_repository->id, $repositories['repository_id']);
        $this->assertStringContainsString('repository_ajax.php?action=upload', $repositories['url']);
    }

    public function test_get_upload_repository_as_user(): void {
        $user = $this->getDataGenerator()->create_user();

        $this->setUser($user);

        $expected_repository = $this->get_expected_repository();

        $repositories = file_helper::get_upload_repository();
        $this->assertEquals($expected_repository->id, $repositories['repository_id']);
        $this->assertStringContainsString('repository_ajax.php?action=upload', $repositories['url']);

        $repositories = file_helper::get_upload_repository(context_system::instance()->id);
        $this->assertEquals($expected_repository->id, $repositories['repository_id']);
        $this->assertStringContainsString('repository_ajax.php?action=upload', $repositories['url']);

        $repositories = file_helper::get_upload_repository(context_user::instance($user->id)->id);
        $this->assertEquals($expected_repository->id, $repositories['repository_id']);
        $this->assertStringContainsString('repository_ajax.php?action=upload', $repositories['url']);
    }

    public function test_get_upload_repository_as_differnt_user(): void {
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $this->setUser($user1);

        $expected_repository = $this->get_expected_repository();

        $repositories = file_helper::get_upload_repository(context_user::instance($user2->id)->id);
        $this->assertEquals($expected_repository->id, $repositories['repository_id']);
        $this->assertStringContainsString('repository_ajax.php?action=upload', $repositories['url']);
    }

    public function test_get_uplolad_repository_with_multi_tenancy_enabled() {
        /** @var $tenant_generator \totara_tenant\testing\generator */
        $tenant_generator = \totara_tenant\testing\generator::instance();
        $tenant_generator->enable_tenants();

        $expected_repository = $this->get_expected_repository();

        $tenant1 = $tenant_generator->create_tenant();
        $tenant2 = $tenant_generator->create_tenant();

        $user1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $user2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);

        $system_user = $this->getDataGenerator()->create_user();

        $this->setUser($user1);

        $repositories = file_helper::get_upload_repository();
        $this->assertEquals($expected_repository->id, $repositories['repository_id']);
        $this->assertStringContainsString('repository_ajax.php?action=upload', $repositories['url']);

        $repositories = file_helper::get_upload_repository(context_system::instance()->id);
        $this->assertEquals($expected_repository->id, $repositories['repository_id']);
        $this->assertStringContainsString('repository_ajax.php?action=upload', $repositories['url']);

        $repositories = file_helper::get_upload_repository(context_user::instance($system_user->id)->id);
        $this->assertEquals($expected_repository->id, $repositories['repository_id']);
        $this->assertStringContainsString('repository_ajax.php?action=upload', $repositories['url']);

        $repositories = file_helper::get_upload_repository(context_user::instance($user1->id)->id);
        $this->assertEquals($expected_repository->id, $repositories['repository_id']);
        $this->assertStringContainsString('repository_ajax.php?action=upload', $repositories['url']);

        $repositories = file_helper::get_upload_repository(context_user::instance($user2->id)->id);
        $this->assertEquals(0, $repositories['repository_id']);
        $this->assertStringContainsString('repository_ajax.php?action=upload', $repositories['url']);

        $this->setUser($system_user);

        $repositories = file_helper::get_upload_repository();
        $this->assertEquals($expected_repository->id, $repositories['repository_id']);
        $this->assertStringContainsString('repository_ajax.php?action=upload', $repositories['url']);

        $repositories = file_helper::get_upload_repository(context_system::instance()->id);
        $this->assertEquals($expected_repository->id, $repositories['repository_id']);
        $this->assertStringContainsString('repository_ajax.php?action=upload', $repositories['url']);

        $repositories = file_helper::get_upload_repository(context_user::instance($system_user->id)->id);
        $this->assertEquals($expected_repository->id, $repositories['repository_id']);
        $this->assertStringContainsString('repository_ajax.php?action=upload', $repositories['url']);

        $repositories = file_helper::get_upload_repository(context_user::instance($user1->id)->id);
        $this->assertEquals($expected_repository->id, $repositories['repository_id']);
        $this->assertStringContainsString('repository_ajax.php?action=upload', $repositories['url']);

        $repositories = file_helper::get_upload_repository(context_user::instance($user2->id)->id);
        $this->assertEquals($expected_repository->id, $repositories['repository_id']);
        $this->assertStringContainsString('repository_ajax.php?action=upload', $repositories['url']);
    }

    public function test_get_uplolad_repository_with_multi_tenancy_and_isolation_enabled() {
        /** @var $tenant_generator \totara_tenant\testing\generator */
        $tenant_generator = \totara_tenant\testing\generator::instance();
        $tenant_generator->enable_tenants();
        set_config('tenantsisolated', 1);

        $expected_repository = $this->get_expected_repository();

        $tenant1 = $tenant_generator->create_tenant();
        $tenant2 = $tenant_generator->create_tenant();

        $user1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $user2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);

        $system_user = $this->getDataGenerator()->create_user();

        $this->setUser($user1);

        $repositories = file_helper::get_upload_repository();
        $this->assertEquals(0, $repositories['repository_id']);
        $this->assertStringContainsString('repository_ajax.php?action=upload', $repositories['url']);

        $repositories = file_helper::get_upload_repository(context_system::instance()->id);
        $this->assertEquals(0, $repositories['repository_id']);
        $this->assertStringContainsString('repository_ajax.php?action=upload', $repositories['url']);

        $repositories = file_helper::get_upload_repository(context_user::instance($system_user->id)->id);
        $this->assertEquals(0, $repositories['repository_id']);
        $this->assertStringContainsString('repository_ajax.php?action=upload', $repositories['url']);

        $repositories = file_helper::get_upload_repository(context_user::instance($user1->id)->id);
        $this->assertEquals($expected_repository->id, $repositories['repository_id']);
        $this->assertStringContainsString('repository_ajax.php?action=upload', $repositories['url']);

        $repositories = file_helper::get_upload_repository(context_user::instance($user2->id)->id);
        $this->assertEquals(0, $repositories['repository_id']);
        $this->assertStringContainsString('repository_ajax.php?action=upload', $repositories['url']);

        $this->setUser($system_user);

        $repositories = file_helper::get_upload_repository();
        $this->assertEquals($expected_repository->id, $repositories['repository_id']);
        $this->assertStringContainsString('repository_ajax.php?action=upload', $repositories['url']);

        $repositories = file_helper::get_upload_repository(context_system::instance()->id);
        $this->assertEquals($expected_repository->id, $repositories['repository_id']);
        $this->assertStringContainsString('repository_ajax.php?action=upload', $repositories['url']);

        $repositories = file_helper::get_upload_repository(context_user::instance($system_user->id)->id);
        $this->assertEquals($expected_repository->id, $repositories['repository_id']);
        $this->assertStringContainsString('repository_ajax.php?action=upload', $repositories['url']);

        $repositories = file_helper::get_upload_repository(context_user::instance($user1->id)->id);
        $this->assertEquals($expected_repository->id, $repositories['repository_id']);
        $this->assertStringContainsString('repository_ajax.php?action=upload', $repositories['url']);

        $repositories = file_helper::get_upload_repository(context_user::instance($user2->id)->id);
        $this->assertEquals($expected_repository->id, $repositories['repository_id']);
        $this->assertStringContainsString('repository_ajax.php?action=upload', $repositories['url']);
    }

    /**
     * Get upload repository record from database
     *
     * @return stdClass
     */
    private function get_expected_repository(): stdClass {
        return builder::table('repository')
            ->where('type', 'upload')
            ->one();
    }

    /**
     * @return void
     */
    public function test_get_repository_data_has_max_bytes(): void {
        global $CFG;

        // Set the max bytes for config so that we can make the repository data
        // to return the value that we want.
        $CFG->maxbytes = 1024;

        $generator = self::getDataGenerator();
        $user = $generator->create_user();

        $this->setUser($user);
        $repository_data = file_helper::get_upload_repository(
            context_user::instance($user->id)->id
        );

        self::assertArrayHasKey('max_bytes', $repository_data);
        self::assertIsNumeric($repository_data['max_bytes']);
        self::assertEquals(get_max_upload_file_size(1024), $repository_data['max_bytes']);

        self::assertArrayHasKey('repository_id', $repository_data);
        self::assertIsNumeric($repository_data['repository_id']);

        self::assertArrayHasKey('url', $repository_data);
    }
}