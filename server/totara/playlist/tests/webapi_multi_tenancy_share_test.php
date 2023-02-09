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
 * @package totara_playlist
 */
defined('MOODLE_INTERNAL') || die();

use totara_webapi\phpunit\webapi_phpunit_helper;
use totara_engage\webapi\resolver\mutation\share;
use totara_playlist\playlist;
use core_user\totara_engage\share\recipient\user;
use totara_engage\exception\share_exception;

class totara_playlist_webapi_multi_tenancy_share_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    /**
     * @var stdClass|null
     */
    private $tenant_one_user_one;

    /**
     * @var stdClass|null
     */
    private $tenant_one_user_two;

    /**
     * @var stdClass|null
     */
    private $tenant_one_participant_one;

    /**
     * @var stdClass|null
     */
    private $tenant_one_participant_two;

    /**
     * @var stdClass|null
     */
    private $tenant_two_user;

    /**
     * @var stdClass|null
     */
    private $system_user_one;

    /**
     * @var stdClass|null
     */
    private $system_user_two;

    /**
     * @return void
     */
    protected function setUp(): void {
        $tenant_generator = $this->get_tenant_generator();
        $generator = $this->getDataGenerator();

        $tenant_one = $tenant_generator->create_tenant();
        $tenant_two = $tenant_generator->create_tenant();

        $this->tenant_one_user_one = $generator->create_user([
            'firstname' => uniqid('tenant_one_user_one_'),
            'lastname' => uniqid('tenant_one_user_one_'),
            'tenantid' => $tenant_one->id
        ]);

        $this->tenant_one_user_two = $generator->create_user([
            'firstname' => uniqid('tenant_one_user_two_'),
            'lastname' => uniqid('tenant_one_user_two_'),
            'tenantid' => $tenant_one->id
        ]);

        $this->tenant_two_user = $generator->create_user([
            'firstname' => uniqid('tenant_two_user_'),
            'lastname' => uniqid('tenant_two_user_'),
            'tenantid' => $tenant_two->id
        ]);

        $this->system_user_one = $generator->create_user([
            'firstname' => uniqid('system_user_one_'),
            'lastname' => uniqid('system_user_one_')
        ]);

        $this->system_user_two = $generator->create_user([
            'firstname' => uniqid('system_user_two_'),
            'lastname' => uniqid('system_user_two_')
        ]);

        $this->tenant_one_participant_one = $generator->create_user([
            'lastname' => uniqid('tenant_one_participant_one_'),
            'firstname' => uniqid('tenant_one_participant_one_')
        ]);

        $this->tenant_one_participant_two = $generator->create_user([
            'firstname' => uniqid('tenant_one_participant_two_'),
            'lastname' => uniqid('tenant_one_participant_two_')
        ]);

        $tenant_generator->set_user_participation($this->tenant_one_participant_one->id, [$tenant_one->id]);
        $tenant_generator->set_user_participation($this->tenant_one_participant_two->id, [$tenant_one->id]);
    }

    /**
     * @return void
     */
    protected function tearDown(): void {
        $this->tenant_one_user_one = null;
        $this->tenant_one_user_two = null;
        $this->tenant_two_user = null;
        $this->system_user_one = null;
        $this->system_user_two = null;

        $this->tenant_one_participant_one = null;
        $this->tenant_one_participant_two = null;
    }

    /**
     * @return \totara_tenant\testing\generator
     */
    private function get_tenant_generator(): \totara_tenant\testing\generator {
        $generator = $this->getDataGenerator();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        return $tenant_generator;
    }

    /**
     * @return \totara_playlist\testing\generator
     */
    private function get_playlist_generator(): \totara_playlist\testing\generator {
        $generator = $this->getDataGenerator();

        /** @var \totara_playlist\testing\generator $playlist_generator */
        $playlist_generator = $generator->get_plugin_generator('totara_playlist');
        return $playlist_generator;
    }

    /**
     * @param int $user_id
     * @param int $playlist_id
     *
     * @return stdClass|null
     */
    private function get_playlist_recipient_record(int $user_id, int $playlist_id): ?stdClass {
        global $DB;

        $sql = '
            SELECT esr.* FROM "ttr_engage_share" es
            INNER JOIN "ttr_engage_share_recipient" esr ON es.id = esr.shareid
            WHERE es.component = :totara_playlist
            AND esr.component = :core_user
            AND esr.area = :core_user_area
            AND esr.instanceid = :user_id
            AND es.itemid = :playlist_id
        ';

        $record = $DB->get_record_sql(
            $sql,
            [
                'totara_playlist' => playlist::get_resource_type(),
                'core_user' => 'core_user',
                'core_user_area' => user::AREA,
                'user_id' => $user_id,
                'playlist_id' => $playlist_id
            ]
        );

        return !$record ? null : $record;
    }

    /**
     * @param int $playlist_id
     * @param int $user_id
     *
     * @return array
     */
    private function create_mutation_parameters_for_share(int $playlist_id, int $user_id): array {
        return [
            'itemid' => $playlist_id,
            'component' => playlist::get_resource_type(),
            'recipients' => [
                [
                    'instanceid' => $user_id,
                    'component' => 'core_user',
                    'area' => user::AREA
                ]
            ]
        ];
    }

    /**
     * @return void
     */
    public function test_share_tenant_playlist_to_system_user_as_tenant_member_with_isolation(): void {
        set_config('tenantsisolated', 1);
        $playlist_generator = $this->get_playlist_generator();

        $this->setUser($this->tenant_one_user_one);
        $playlist = $playlist_generator->create_public_playlist();

        // Sharing the playlist to system user one.
        $mutation_name = $this->get_graphql_name(share::class);
        $parameters = $this->create_mutation_parameters_for_share(
            $playlist->get_id(),
            $this->system_user_one->id
        );

        $this->expectException(share_exception::class);
        $this->expectExceptionMessage(get_string('error:invalid_recipient', 'totara_engage'));

        $this->resolve_graphql_mutation($mutation_name, $parameters);
    }

    /**
     * @return void
     */
    public function test_share_tenant_playlist_to_system_user_as_tenant_member_without_isolation(): void {
        $playlist_generator = $this->get_playlist_generator();
        $this->setUser($this->tenant_one_user_one);

        $playlist = $playlist_generator->create_public_playlist();
        $playlist_id = $playlist->get_id();

        $result = $this->resolve_graphql_mutation(
            $this->get_graphql_name(share::class),
            $this->create_mutation_parameters_for_share($playlist_id, $this->system_user_one->id)
        );

        self::assertIsArray($result);
        self::assertArrayHasKey('sharedbycount', $result);
        self::assertEquals(0, $result['sharedbycount']);

        $recipient_record = $this->get_playlist_recipient_record($this->system_user_one->id, $playlist_id);
        self::assertNotNull($recipient_record);
    }

    /**
     * @return void
     */
    public function test_share_tenant_playlist_to_tenant_participant_as_tenant_member_with_isolation(): void {
        set_config('tenantsisolated', 1);
        $playlist_generator = $this->get_playlist_generator();

        $this->setUser($this->tenant_one_user_two);
        $playlist = $playlist_generator->create_public_playlist();
        $playlist_id = $playlist->get_id();

        $result = $this->resolve_graphql_mutation(
            $this->get_graphql_name(share::class),
            $this->create_mutation_parameters_for_share($playlist_id, $this->tenant_one_participant_one->id)
        );

        self::assertIsArray($result);
        self::assertArrayHasKey('sharedbycount', $result);
        self::assertEquals(0, $result['sharedbycount']);

        $recipient_record = $this->get_playlist_recipient_record(
            $this->tenant_one_participant_one->id,
            $playlist_id
        );

        self::assertNotNull($recipient_record);
    }

    /**
     * @return void
     */
    public function test_share_tenant_playlist_to_tenant_participant_as_tenant_member_without_isolation(): void {
        $playlist_generator = $this->get_playlist_generator();

        $this->setUser($this->tenant_one_user_two);
        $playlist = $playlist_generator->create_public_playlist();
        $playlist_id = $playlist->get_id();

        $result = $this->resolve_graphql_mutation(
            $this->get_graphql_name(share::class),
            $this->create_mutation_parameters_for_share($playlist_id, $this->tenant_one_participant_one->id)
        );

        self::assertIsArray($result);
        self::assertArrayHasKey('sharedbycount', $result);
        self::assertEquals(0, $result['sharedbycount']);

        $recipient_record = $this->get_playlist_recipient_record(
            $this->tenant_one_participant_one->id,
            $playlist_id
        );

        self::assertNotNull($recipient_record);
    }

    /**
     * @return void
     */
    public function test_share_tenant_playlist_to_tenant_member_two_as_tenant_member_one_with_isolation(): void {
        set_config('tenantsisolated', 1);

        $playlist_generator = $this->get_playlist_generator();
        $this->setUser($this->tenant_one_user_two);

        $playlist = $playlist_generator->create_public_playlist();
        $playlist_id = $playlist->get_id();

        $this->expectException(share_exception::class);
        $this->expectExceptionMessage(get_string('error:invalid_recipient', 'totara_engage'));

        $this->resolve_graphql_mutation(
            $this->get_graphql_name(share::class),
            $this->create_mutation_parameters_for_share($playlist_id, $this->tenant_two_user->id)
        );
    }

    /**
     * @return void
     */
    public function test_share_tenant_playlist_to_tenant_member_two_as_tenant_member_one_without_isolation(): void {
        $playlist_generator = $this->get_playlist_generator();
        $this->setUser($this->tenant_one_user_two);

        $playlist = $playlist_generator->create_public_playlist();
        $playlist_id = $playlist->get_id();

        $this->expectException(share_exception::class);
        $this->expectExceptionMessage(get_string('error:invalid_recipient', 'totara_engage'));

        $this->resolve_graphql_mutation(
            $this->get_graphql_name(share::class),
            $this->create_mutation_parameters_for_share($playlist_id, $this->tenant_two_user->id)
        );
    }

    /**
     * @return void
     */
    public function test_share_tenant_playlist_to_system_user_as_tenant_participant_with_isolation(): void {
        set_config('tenantsisolated', 1);
        $playlist_generator = $this->get_playlist_generator();
        $playlist = $playlist_generator->create_public_playlist([
            'userid' => $this->tenant_one_user_one->id
        ]);

        $playlist_id = $playlist->get_id();

        // Log in as participant user to share the playlist to system user.
        $this->setUser($this->tenant_one_participant_one);

        $this->expectException(share_exception::class);
        $this->expectExceptionMessage(get_string('error:invalid_recipient', 'totara_engage'));

        $this->resolve_graphql_mutation(
            $this->get_graphql_name(share::class),
            $this->create_mutation_parameters_for_share($playlist_id, $this->system_user_two->id)
        );
    }

    /**
     * @return void
     */
    public function test_share_tenant_playlist_to_system_user_as_tenant_participant_without_isolation(): void {
        $playlist_generator = $this->get_playlist_generator();
        $playlist = $playlist_generator->create_public_playlist([
            'userid' => $this->tenant_one_user_one->id
        ]);

        $playlist_id = $playlist->get_id();

        // Log in as participant user to share the playlist to system user.
        $this->setUser($this->tenant_one_participant_one);

        $result = $this->resolve_graphql_mutation(
            $this->get_graphql_name(share::class),
            $this->create_mutation_parameters_for_share($playlist_id, $this->system_user_two->id)
        );

        self::assertIsArray($result);
        self::assertArrayHasKey('sharedbycount', $result);
        self::assertEquals(1, $result['sharedbycount']);

        $recipient_record = $this->get_playlist_recipient_record(
            $this->system_user_two->id,
            $playlist_id
        );

        self::assertNotNull($recipient_record);
    }

    /**
     * @return void
     */
    public function test_share_tenant_playlist_to_tenant_member_as_tenant_participant_with_isolation(): void {
        set_config('tenantsisolated', 1);
        $playlist_generator = $this->get_playlist_generator();
        $playlist = $playlist_generator->create_public_playlist([
            'userid' => $this->tenant_one_user_one->id
        ]);

        $playlist_id = $playlist->get_id();
        $this->setUser($this->tenant_one_participant_one);

        $result = $this->resolve_graphql_mutation(
            $this->get_graphql_name(share::class),
            $this->create_mutation_parameters_for_share(
                $playlist_id,
                $this->tenant_one_user_two->id
            )
        );

        self::assertIsArray($result);
        self::assertArrayHasKey('sharedbycount', $result);
        self::assertEquals(1, $result['sharedbycount']);

        $recipient_record = $this->get_playlist_recipient_record(
            $this->tenant_one_user_two->id,
            $playlist_id
        );

        self::assertNotNull($recipient_record);
    }

    /**
     * @return void
     */
    public function test_share_tenant_playlist_to_tenant_member_as_tenant_participant_without_isolation(): void {
        $playlist_generator = $this->get_playlist_generator();
        $playlist = $playlist_generator->create_public_playlist([
            'userid' => $this->tenant_one_user_one->id
        ]);

        // Log in as tenant participant.
        $this->setUser($this->tenant_one_participant_one);
        $playlist_id = $playlist->get_id();

        $result = $this->resolve_graphql_mutation(
            $this->get_graphql_name(share::class),
            $this->create_mutation_parameters_for_share($playlist_id, $this->tenant_one_user_two->id)
        );

        self::assertIsArray($result);
        self::assertArrayHasKey('sharedbycount', $result);
        self::assertEquals(1, $result['sharedbycount']);

        $recipient_record = $this->get_playlist_recipient_record(
            $this->tenant_one_user_two->id,
            $playlist_id
        );

        self::assertNotNull($recipient_record);
    }

    /**
     * @return void
     */
    public function test_share_system_playlist_to_tenant_member_as_tenant_participant_with_isolation(): void {
        set_config('tenantsisolated', 1);
        $playlist_generator = $this->get_playlist_generator();

        $playlist = $playlist_generator->create_public_playlist([
            'userid' => $this->system_user_one->id
        ]);

        // Log in as tenant participant to share to the tenant one user.
        $this->setUser($this->tenant_one_participant_one);

        $this->expectException(share_exception::class);
        $this->expectExceptionMessage(get_string('error:invalid_recipient', 'totara_engage'));

        $this->resolve_graphql_mutation(
            $this->get_graphql_name(share::class),
            $this->create_mutation_parameters_for_share(
                $playlist->get_id(),
                $this->tenant_one_user_two->id
            )
        );
    }

    /**
     * @return void
     */
    public function test_share_system_playlist_to_tenant_member_as_tenant_participant_without_isolation(): void {
        $playlist_generator = $this->get_playlist_generator();
        $playlist = $playlist_generator->create_public_playlist([
            'userid' => $this->system_user_one->id
        ]);

        // Log in as tenant participant to share to the tenant one user.
        $this->setUser($this->tenant_one_participant_one);
        $result = $this->resolve_graphql_mutation(
            $this->get_graphql_name(share::class),
            $this->create_mutation_parameters_for_share(
                $playlist->get_id(),
                $this->tenant_one_user_two->id
            )
        );

        self::assertIsArray($result);
        self::assertArrayHasKey('sharedbycount', $result);
        self::assertEquals(1, $result['sharedbycount']);

        $recipient_record = $this->get_playlist_recipient_record(
            $this->tenant_one_user_two->id,
            $playlist->get_id()
        );

        self::assertNotNull($recipient_record);
    }

    /**
     * @return void
     */
    public function test_share_system_playlist_to_tenant_member_as_system_user_with_isolation(): void {
        set_config('tenantsisolated', 1);
        $playlist_generator = $this->get_playlist_generator();

        $this->setUser($this->system_user_two);
        $playlist = $playlist_generator->create_public_playlist();

        $this->expectException(share_exception::class);
        $this->expectExceptionMessage(get_string('error:invalid_recipient', 'totara_engage'));

        $this->resolve_graphql_mutation(
            $this->get_graphql_name(share::class),
            $this->create_mutation_parameters_for_share(
                $playlist->get_id(),
                $this->tenant_one_user_two->id
            )
        );
    }

    /**
     * @return void
     */
    public function test_share_system_playlist_to_tenant_member_as_system_user_without_isolation(): void {
        $playlist_generator = $this->get_playlist_generator();

        $this->setUser($this->system_user_two);
        $playlist = $playlist_generator->create_public_playlist();

        $result = $this->resolve_graphql_mutation(
            $this->get_graphql_name(share::class),
            $this->create_mutation_parameters_for_share(
                $playlist->get_id(),
                $this->tenant_one_user_two->id
            )
        );

        self::assertIsArray($result);
        self::assertArrayHasKey('sharedbycount', $result);
        self::assertEquals(0, $result['sharedbycount']);

        $recipient_record = $this->get_playlist_recipient_record(
            $this->tenant_one_user_two->id,
            $playlist->get_id()
        );

        self::assertNotNull($recipient_record);
    }

    /**
     * @return void
     */
    public function test_share_system_playlist_to_tenant_two_member_as_tenant_member_one_with_isolation(): void {
        set_config('tenantsisolated', 1);

        $playlist_generator = $this->get_playlist_generator();
        $playlist = $playlist_generator->create_public_playlist([
            'userid' => $this->system_user_one->id
        ]);

        // Log in as tenant one member to share the playlist to tenant two member
        $this->setUser($this->tenant_one_user_one);
        $this->expectException(share_exception::class);
        $this->expectExceptionMessage(get_string('error:sharecapability', 'totara_playlist'));

        $this->resolve_graphql_mutation(
            $this->get_graphql_name(share::class),
            $this->create_mutation_parameters_for_share(
                $playlist->get_id(),
                $this->tenant_two_user->id
            )
        );
    }

    /**
     * @return void
     */
    public function test_share_system_playlist_to_tenant_two_member_as_tenant_member_one_without_isolation(): void {
        $playlist_generator = $this->get_playlist_generator();
        $playlist = $playlist_generator->create_public_playlist([
            'userid' => $this->system_user_one->id
        ]);

        // Log in as tenant one member to share the playlist to tenant two member
        $this->setUser($this->tenant_one_user_one);
        $this->expectException(share_exception::class);
        $this->expectExceptionMessage(get_string('error:invalid_recipient', 'totara_engage'));

        $this->resolve_graphql_mutation(
            $this->get_graphql_name(share::class),
            $this->create_mutation_parameters_for_share(
                $playlist->get_id(),
                $this->tenant_two_user->id
            )
        );
    }

    /**
     * @return void
     */
    public function test_share_system_playlist_to_tenant_member_as_same_tenant_member_with_isolation(): void {
        set_config('tenantsisolated', 1);
        $playlist_generator = $this->get_playlist_generator();
        $playlist = $playlist_generator->create_public_playlist([
            'userid' => $this->system_user_one->id
        ]);

        $this->setUser($this->tenant_one_user_two);

        $this->expectException(share_exception::class);
        $this->expectExceptionMessage(get_string('error:sharecapability', 'totara_playlist'));

        $this->resolve_graphql_mutation(
            $this->get_graphql_name(share::class),
            $this->create_mutation_parameters_for_share(
                $playlist->get_id(),
                $this->tenant_one_user_one->id
            )
        );
    }

    /**
     * @return void
     */
    public function test_share_system_playlist_to_tenant_member_as_same_tenant_member_without_isolation(): void {
        $playlist_generator = $this->get_playlist_generator();
        $playlist = $playlist_generator->create_public_playlist([
            'userid' => $this->system_user_one->id
        ]);

        $this->setUser($this->tenant_one_user_two);
        $result = $this->resolve_graphql_mutation(
            $this->get_graphql_name(share::class),
            $this->create_mutation_parameters_for_share(
                $playlist->get_id(),
                $this->tenant_one_user_one->id
            )
        );

        self::assertIsArray($result);
        self::assertArrayHasKey('sharedbycount', $result);
        self::assertEquals(1, $result['sharedbycount']);

        $recipient_record = $this->get_playlist_recipient_record(
            $this->tenant_one_user_one->id,
            $playlist->get_id()
        );

        self::assertNotNull($recipient_record);
    }

    /**
     * @return void
     */
    public function test_share_tenant_participant_playlist_to_tenant_member_as_participant_with_isolation(): void {
        set_config('tenantsisolated', 1);
        $playlist_generator = $this->get_playlist_generator();
        $playlist = $playlist_generator->create_public_playlist([
            'userid' => $this->system_user_one->id
        ]);

        $this->setUser($this->tenant_one_participant_one);

        $this->expectException(share_exception::class);
        $this->expectExceptionMessage(get_string('error:invalid_recipient', 'totara_engage'));

        $this->resolve_graphql_mutation(
            $this->get_graphql_name(share::class),
            $this->create_mutation_parameters_for_share(
                $playlist->get_id(),
                $this->tenant_one_user_one->id
            )
        );
    }

    /**
     * @return void
     */
    public function test_share_tenant_participant_playlist_to_tenant_member_as_participant_without_isolation(): void {
        $playlist_generator = $this->get_playlist_generator();
        $playlist = $playlist_generator->create_public_playlist([
            'userid' => $this->system_user_one->id
        ]);

        $this->setUser($this->tenant_one_participant_one);

        $result = $this->resolve_graphql_mutation(
            $this->get_graphql_name(share::class),
            $this->create_mutation_parameters_for_share(
                $playlist->get_id(),
                $this->tenant_one_user_one->id
            )
        );

        self::assertIsArray($result);
        self::assertArrayHasKey('sharedbycount', $result);
        self::assertEquals(1, $result['sharedbycount']);

        $recipient_record = $this->get_playlist_recipient_record(
            $this->tenant_one_user_one->id,
            $playlist->get_id()
        );

        self::assertNotNull($recipient_record);
    }
}