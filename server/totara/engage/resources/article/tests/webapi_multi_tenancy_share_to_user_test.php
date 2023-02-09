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
 * @package engage_article
 */
defined('MOODLE_INTERNAL') || die();

use totara_webapi\phpunit\webapi_phpunit_helper;
use totara_engage\webapi\resolver\mutation\share;
use engage_article\totara_engage\resource\article;
use core_user\totara_engage\share\recipient\user;
use totara_engage\exception\share_exception;

class engage_article_webapi_multi_tenancy_share_to_user_testcase extends advanced_testcase {
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
    private $tenant_two_user;

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
    private $system_user_one;

    /**
     * @var stdClass|null
     */
    private $system_user_two;

    /**
     * @return void
     */
    protected function setUp(): void {
        $tenant_generator = $this->get_tenant_participant();

        $tenant_one = $tenant_generator->create_tenant();
        $tenant_two = $tenant_generator->create_tenant();

        $generator = $this->getDataGenerator();

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
            'firstname' => uniqid('tenant_one_participant_one_'),
            'lastname' => uniqid('tenant_one_participant_one_')
        ]);

        $this->tenant_one_participant_two = $generator->create_user([
            'firstname' => uniqid('tenant_one_participant_two_'),
            'lastname' => uniqid('tenant_one_pariticipant_two_')
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
        $this->tenant_one_participant_one = null;
        $this->tenant_one_participant_two = null;
        $this->system_user_one = null;
        $this->system_user_two = null;
    }

    /**
     * @return \totara_tenant\testing\generator
     */
    private function get_tenant_participant(): \totara_tenant\testing\generator {
        $generator = $this->getDataGenerator();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        return $tenant_generator;
    }

    /**
     * @return \engage_article\testing\generator
     */
    private function get_article_generator(): \engage_article\testing\generator {
        $generator = $this->getDataGenerator();

        /** @var \engage_article\testing\generator $article_generator */
        $article_generator = $generator->get_plugin_generator('engage_article');
        return $article_generator;
    }

    /**
     * @param int $user_id
     * @param int $article_id
     *
     * @return stdClass|null
     */
    private function get_user_recipient_record(int $user_id, int $article_id): ?stdClass {
        global $DB;

        $sql = '
            SELECT esr.* FROM "ttr_engage_share" es
            INNER JOIN "ttr_engage_share_recipient" esr ON es.id = esr.shareid
            WHERE esr.instanceid = :user_id
            AND es.itemid = :article_id
            AND esr.component = :core_user
            AND esr.area = :core_user_area
            AND es.component = :engage_article
        ';

        $record = $DB->get_record_sql(
            $sql,
            [
                'user_id' => $user_id,
                'article_id' => $article_id,
                'core_user' => 'core_user',
                'core_user_area' => user::AREA,
                'engage_article' => article::get_resource_type()
            ]
        );

        return !$record ? null : $record;
    }

    /**
     * @return void
     */
    public function test_share_tenant_article_to_participant_as_tenant_member_without_isolation_mode(): void {
        $article_generator = $this->get_article_generator();

        $this->setUser($this->tenant_one_user_one);
        $article = $article_generator->create_public_article();

        $mutation_name = $this->get_graphql_name(share::class);
        $parameters = [
            'itemid' => $article->get_id(),
            'component' => article::get_resource_type(),
            'recipients' => [
                [
                    'instanceid' => $this->tenant_one_participant_one->id,
                    'component' => 'core_user',
                    'area' => user::AREA
                ]
            ]
        ];

        $result = $this->resolve_graphql_mutation($mutation_name, $parameters);

        self::assertIsArray($result);
        self::assertArrayHasKey('sharedbycount', $result);
        self::assertEquals(0, $result['sharedbycount']);

        $recipient_record = $this->get_user_recipient_record(
            $this->tenant_one_participant_one->id,
            $article->get_id()
        );

        self::assertNotNull($recipient_record);
    }

    /**
     * @return void
     */
    public function test_share_tenant_article_to_participant_as_tenant_member_with_isolation_mode(): void {
        $article_generator = $this->get_article_generator();

        $this->setUser($this->tenant_one_user_one);
        $article = $article_generator->create_public_article();

        $mutation_name = $this->get_graphql_name(share::class);
        $parameters = [
            'itemid' => $article->get_id(),
            'component' => article::get_resource_type(),
            'recipients' => [
                [
                    'instanceid' => $this->tenant_one_participant_one->id,
                    'component' => 'core_user',
                    'area' => user::AREA
                ]
            ],
        ];

        set_config('tenantsisolated', 1);
        $result = $this->resolve_graphql_mutation($mutation_name, $parameters);

        self::assertIsArray($result);
        self::assertArrayHasKey('sharedbycount', $result);
        self::assertEquals(0, $result['sharedbycount']);

        $recipient_record = $this->get_user_recipient_record(
            $this->tenant_one_participant_one->id,
            $article->get_id()
        );

        self::assertNotNull($recipient_record);
    }

    /**
     * @return void
     */
    public function test_share_tenant_article_to_system_user_as_tenant_member_without_isolation(): void {
        $article_generator = $this->get_article_generator();

        $this->setUser($this->tenant_one_user_one);
        $article = $article_generator->create_public_article();

        $mutation_name = $this->get_graphql_name(share::class);
        $parameters = [
            'itemid' => $article->get_id(),
            'component' => article::get_resource_type(),
            'recipients' => [
                [
                    'instanceid' => $this->system_user_one->id,
                    'component' => 'core_user',
                    'area' => user::AREA
                ]
            ]
        ];

        $result = $this->resolve_graphql_mutation($mutation_name, $parameters);

        self::assertIsArray($result);
        self::assertArrayHasKey('sharedbycount', $result);
        self::assertEquals(0, $result['sharedbycount']);

        $recipient_record = $this->get_user_recipient_record(
            $this->system_user_one->id,
            $article->get_id()
        );

        self::assertNotNull($recipient_record);
    }

    /**
     * @return void
     */
    public function test_share_tenant_article_to_system_user_as_tenant_member_with_isolation(): void {
        $article_generator = $this->get_article_generator();

        $this->setUser($this->tenant_one_user_one);
        $article = $article_generator->create_public_article();

        $mutation_name = $this->get_graphql_name(share::class);
        $parameters = [
            'itemid' => $article->get_id(),
            'component' => article::get_resource_type(),
            'recipients' => [
                [
                    'instanceid' => $this->system_user_two->id,
                    'component' => 'core_user',
                    'area' => user::AREA
                ]
            ]
        ];

        set_config('tenantsisolated', 1);

        $this->expectException(share_exception::class);
        $this->expectExceptionMessage(get_string('error:invalid_recipient', 'totara_engage'));

        $this->resolve_graphql_mutation($mutation_name, $parameters);
    }

    /**
     * @return void
     */
    public function test_share_system_article_to_tenant_member_as_system_user_without_isolation(): void {
        $article_generator = $this->get_article_generator();

        $this->setUser($this->system_user_one);
        $article = $article_generator->create_public_article();

        $mutation_name = $this->get_graphql_name(share::class);
        $parameters = [
            'itemid' => $article->get_id(),
            'component' => article::get_resource_type(),
            'recipients' => [
                [
                    'instanceid' => $this->tenant_one_user_two->id,
                    'component' => 'core_user',
                    'area' => user::AREA
                ]
            ]
        ];

        $result = $this->resolve_graphql_mutation($mutation_name, $parameters);

        self::assertIsArray($result);
        self::assertArrayHasKey('sharedbycount', $result);
        self::assertEquals(0, $result['sharedbycount']);

        $recipient_record = $this->get_user_recipient_record(
            $this->tenant_one_user_two->id,
            $article->get_id()
        );

        self::assertNotNull($recipient_record);
    }

    /**
     * @return void
     */
    public function test_share_system_article_to_tenant_member_as_system_user_with_isolation(): void {
        $article_generator = $this->get_article_generator();

        $this->setUser($this->system_user_two);
        $article = $article_generator->create_public_article();

        $mutation_name = $this->get_graphql_name(share::class);
        $parameters = [
            'itemid' => $article->get_id(),
            'component' => article::get_resource_type(),
            'recipients' => [
                [
                    'instanceid' => $this->tenant_one_user_one->id,
                    'component' => 'core_user',
                    'area' => user::AREA
                ]
            ]
        ];

        set_config('tenantsisolated', 1);

        $this->expectException(share_exception::class);
        $this->expectExceptionMessage(get_string('error:invalid_recipient', 'totara_engage'));

        $this->resolve_graphql_mutation($mutation_name, $parameters);
    }

    /**
     * @return void
     */
    public function test_share_tenant_article_to_tenant_participant_as_other_tenant_member_without_isolation(): void {
        $article_generator = $this->get_article_generator();

        $this->setUser($this->tenant_one_user_one);
        $article = $article_generator->create_public_article();

        // Log in as tenant one user two to share the article to tenant participant.
        $this->setUser($this->tenant_one_user_two);

        $result = $this->resolve_graphql_mutation(
            $this->get_graphql_name(share::class),
            [
                'itemid' => $article->get_id(),
                'component' => article::get_resource_type(),
                'recipients' => [
                    [
                        'instanceid' => $this->tenant_one_participant_one->id,
                        'component' => 'core_user',
                        'area' => user::AREA
                    ]
                ]
            ]
        );

        self::assertIsArray($result);
        self::assertArrayHasKey('sharedbycount', $result);
        self::assertEquals(1, $result['sharedbycount']);

        $recipient_record = $this->get_user_recipient_record(
            $this->tenant_one_participant_one->id,
            $article->get_id()
        );

        self::assertNotNull($recipient_record);
    }

    /**
     * @return void
     */
    public function test_share_tenant_article_to_tenant_pariticipant_as_other_tenant_member_with_isolation(): void {
        $article_generator = $this->get_article_generator();

        $this->setUser($this->tenant_one_user_one);
        $article = $article_generator->create_public_article();

        // Log in as tenant one user two to share the article to the tenant participant.
        $this->setUser($this->tenant_one_user_two);
        set_config('tenantsisolated', 1);

        $result = $this->resolve_graphql_mutation(
            $this->get_graphql_name(share::class),
            [
                'itemid' => $article->get_id(),
                'component' => article::get_resource_type(),
                'recipients' => [
                    [
                        'instanceid' => $this->tenant_one_participant_two->id,
                        'component' => 'core_user',
                        'area' => user::AREA
                    ]
                ]
            ]
        );

        self::assertIsArray($result);
        self::assertArrayHasKey('sharedbycount', $result);
        self::assertEquals(1, $result['sharedbycount']);

        $recipient_record = $this->get_user_recipient_record(
            $this->tenant_one_participant_two->id,
            $article->get_id()
        );

        self::assertNotNull($recipient_record);
    }

    /**
     * @return void
     */
    public function test_share_tenant_article_to_system_user_as_tenant_participant_without_isolation(): void {
        $article_generator = $this->get_article_generator();

        $this->setUser($this->tenant_one_user_one);
        $article = $article_generator->create_public_article();

        // Log in as tenant participant to share to the system user.
        $this->setUser($this->tenant_one_participant_two);
        $result = $this->resolve_graphql_mutation(
            $this->get_graphql_name(share::class),
            [
                'itemid' => $article->get_id(),
                'component' => article::get_resource_type(),
                'recipients' => [
                    [
                        'instanceid' => $this->system_user_one->id,
                        'component' => 'core_user',
                        'area' => user::AREA
                    ]
                ]
            ]
        );

        self::assertIsArray($result);
        self::assertArrayHasKey('sharedbycount', $result);
        self::assertEquals(1, $result['sharedbycount']);

        $recipient_record = $this->get_user_recipient_record(
            $this->system_user_one->id,
            $article->get_id()
        );

        self::assertNotNull($recipient_record);
    }

    /**
     * @return void
     */
    public function test_share_tenant_article_to_system_user_as_tenant_participant_with_isolation(): void {
        $article_generator = $this->get_article_generator();

        $this->setUser($this->tenant_one_user_one);
        $article = $article_generator->create_public_article();

        // Log in as tenant participant to share the article with the system user.
        $this->setUser($this->tenant_one_participant_one);
        set_config('tenantsisolated', 1);

        $this->expectException(share_exception::class);
        $this->expectExceptionMessage(get_string('error:invalid_recipient', 'totara_engage'));

        $this->resolve_graphql_mutation(
            $this->get_graphql_name(share::class),
            [
                'itemid' => $article->get_id(),
                'component' => article::get_resource_type(),
                'recipients' => [
                    [
                        'instanceid' => $this->system_user_two->id,
                        'component' => 'core_user',
                        'area' => user::AREA
                    ]
                ]
            ]
        );
    }

    /**
     * @return void
     */
    public function test_share_tenant_article_to_other_tenant_as_tenant_participant_without_isolation(): void {
        $article_generator = $this->get_article_generator();

        $this->setUser($this->tenant_one_user_one);
        $article = $article_generator->create_public_article();

        // Log in as tenant participant to share the article with different tenant user.
        $this->setUser($this->tenant_one_participant_one);

        $this->expectException(share_exception::class);
        $this->expectExceptionMessage(get_string('error:invalid_recipient', 'totara_engage'));

        $this->resolve_graphql_mutation(
            $this->get_graphql_name(share::class),
            [
                'itemid' => $article->get_id(),
                'component' => article::get_resource_type(),
                'recipients' => [
                    [
                        'instanceid' => $this->tenant_two_user->id,
                        'component' => 'core_user',
                        'area' => user::AREA
                    ]
                ]
            ]
        );
    }

    /**
     * @return void
     */
    public function test_share_tenant_article_to_other_tenant_as_tenant_participant_with_isolation(): void {
        $article_generator = $this->get_article_generator();

        $this->setUser($this->tenant_one_user_one);
        $article = $article_generator->create_public_article();

        $this->setUser($this->tenant_one_participant_two);
        set_config('tenantsisolated', 1);

        $this->expectException(share_exception::class);
        $this->expectExceptionMessage(get_string('error:invalid_recipient', 'totara_engage'));

        $this->resolve_graphql_mutation(
            $this->get_graphql_name(share::class),
            [
                'itemid' => $article->get_id(),
                'component' => article::get_resource_type(),
                'recipients' => [
                    [
                        'instanceid' => $this->tenant_two_user->id,
                        'component' => 'core_user',
                        'area' => user::AREA
                    ]
                ]
            ]
        );
    }

    /**
     * @return void
     */
    public function test_share_system_article_to_tenant_user_as_tenant_participant_without_isolation(): void {
        $article_generator = $this->get_article_generator();

        $this->setUser($this->system_user_one);
        $article = $article_generator->create_public_article();

        // Log in as tenant participant user to share the article to tenant member.
        $this->setUser($this->tenant_one_participant_one);
        $result = $this->resolve_graphql_mutation(
            $this->get_graphql_name(share::class),
            [
                'itemid' => $article->get_id(),
                'component' => article::get_resource_type(),
                'recipients' => [
                    [
                        'instanceid' => $this->tenant_one_user_one->id,
                        'component' => 'core_user',
                        'area' => user::AREA
                    ]
                ]
            ]
        );

        self::assertIsArray($result);
        self::assertArrayHasKey('sharedbycount', $result);
        self::assertEquals(1, $result['sharedbycount']);

        $recipient_record = $this->get_user_recipient_record(
            $this->tenant_one_user_one->id,
            $article->get_id()
        );

        self::assertNotNull($recipient_record);
    }

    /**
     * @return void
     */
    public function test_share_system_article_to_tenant_user_as_tenant_participant_with_isolation(): void {
        $article_generator = $this->get_article_generator();

        $this->setUser($this->system_user_one);
        $article = $article_generator->create_public_article();

        $this->setUser($this->tenant_one_participant_two);
        set_config('tenantsisolated', 1);

        $this->expectException(share_exception::class);
        $this->expectExceptionMessage(get_string('error:invalid_recipient', 'totara_engage'));

        $this->resolve_graphql_mutation(
            $this->get_graphql_name(share::class),
            [
                'component' => article::get_resource_type(),
                'itemid' => $article->get_id(),
                'recipients' => [
                    [
                        'instanceid' => $this->tenant_one_user_one->id,
                        'component' => 'core_user',
                        'area' => user::AREA
                    ]
                ]
            ]
        );
    }
}