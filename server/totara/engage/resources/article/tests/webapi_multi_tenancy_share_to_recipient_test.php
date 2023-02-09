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
use totara_engage\webapi\resolver\query\shareto_recipients;
use engage_article\totara_engage\resource\article;
use core_user\totara_engage\share\recipient\user;

class engage_article_webapi_multi_tenancy_share_content_testcase extends advanced_testcase {
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
    private $tenant_one_participant;

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
    protected function tearDown(): void {
        $this->tenant_one_user_one = null;
        $this->tenant_one_user_two = null;
        $this->tenant_two_user = null;
        $this->tenant_one_participant = null;
        $this->system_user_one = null;
        $this->system_user_two = null;
    }

    /**
     * @return void
     */
    protected function setUp(): void {
        $generator = $this->getDataGenerator();
        $tenant_genertor = $this->get_tenant_generator();

        $tenant_one = $tenant_genertor->create_tenant();
        $tenant_two = $tenant_genertor->create_tenant();

        $this->tenant_one_user_one = $generator->create_user([
            'firstname' => uniqid('tenant_one_user_one_'),
            'lastname' => uniqid('tenant_two_user_one_'),
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

        $this->tenant_one_participant = $generator->create_user([
            'firstname' => uniqid('tenant_one_participant_'),
            'lastname' => uniqid('tenant_one_participant_')
        ]);

        // Set user as participant of tennat one.
        $tenant_genertor->set_user_participation($this->tenant_one_participant->id, [$tenant_one->id]);
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
     * @return \engage_article\testing\generator
     */
    private function get_article_generator(): \engage_article\testing\generator {
        $generator = $this->getDataGenerator();

        /** @var \engage_article\testing\generator $article_generator */
        $article_generator = $generator->get_plugin_generator('engage_article');
        return $article_generator;
    }

    /**
     * @return void
     */
    public function test_find_system_user_as_tenant_member_to_share_tenant_article(): void {
        $article_generator = $this->get_article_generator();

        $this->setUser($this->tenant_one_user_one);
        $article = $article_generator->create_public_article();

        $query_name = $this->get_graphql_name(shareto_recipients::class);
        $parameters = [
            'search' => $this->system_user_one->firstname,
            'itemid' => $article->get_id(),
            'component' => article::get_resource_type(),
            'access' => $article->get_access_code(),
            'theme' => 'ventura',
        ];

        $result_one = $this->resolve_graphql_query($query_name, $parameters);
        self::assertIsArray($result_one);
        self::assertNotEmpty($result_one);
        self::assertCount(1, $result_one);

        $result_one_record = reset($result_one);
        self::assertIsArray($result_one_record);

        self::assertArrayHasKey('component', $result_one_record);
        self::assertEquals('core_user', $result_one_record['component']);

        self::assertArrayHasKey('area', $result_one_record);
        self::assertEquals(user::AREA, $result_one_record['area']);

        self::assertArrayHasKey('instanceid', $result_one_record);
        self::assertEquals($this->system_user_one->id, $result_one_record['instanceid']);

        set_config('tenantsisolated', 1);
        $result_two = $this->resolve_graphql_query($query_name, $parameters);

        self::assertIsArray($result_two);
        self::assertEmpty($result_two);
    }

    /**
     * @return void
     */
    public function test_find_system_user_as_tenant_member_to_share_system_article(): void {
        $article_generator = $this->get_article_generator();
        $this->setUser($this->system_user_one);

        $article = $article_generator->create_public_article();

        // Log in as tenant user to share the system article.
        $this->setUser($this->tenant_one_user_one);

        $query_name = $this->get_graphql_name(shareto_recipients::class);
        $parameters = [
            'search' => $this->system_user_two->firstname,
            'itemid' => $article->get_id(),
            'component' => article::get_resource_type(),
            'access' => $article->get_access_code(),
            'theme' => 'ventura',
        ];

        $result_one = $this->resolve_graphql_query($query_name, $parameters);
        self::assertIsArray($result_one);
        self::assertNotEmpty($result_one);
        self::assertCount(1, $result_one);

        $result_one_record = reset($result_one);
        self::assertIsArray($result_one_record);

        self::assertArrayHasKey('component', $result_one_record);
        self::assertEquals('core_user', $result_one_record['component']);

        self::assertArrayHasKey('area', $result_one_record);
        self::assertEquals(user::AREA, $result_one_record['area']);

        self::assertArrayHasKey('instanceid', $result_one_record);
        self::assertEquals($this->system_user_two->id, $result_one_record['instanceid']);
    }

    /**
     * @return void
     */
    public function test_find_system_user_as_tenant_member_to_share_system_article_with_isolation_mode_on(): void {
        $article_generator = $this->get_article_generator();

        $this->setUser($this->system_user_one);
        $article = $article_generator->create_public_article();

        set_config('tenantsisolated', 1);
        $this->setUser($this->tenant_one_user_one);

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage(get_string('error:permissiondenied', 'totara_engage'));

        $this->resolve_graphql_query(
            $this->get_graphql_name(shareto_recipients::class),
            [
                'search' => $this->system_user_two->lastname,
                'itemid' => $article->get_id(),
                'component' => article::get_resource_type(),
                'access' => $article->get_access_code(),
                'theme' => 'ventura',
            ]
        );
    }

    /**
     * @return void
     */
    public function test_find_tenant_participant_as_tenant_member_to_share_tennat_article(): void {
        $article_generator = $this->get_article_generator();

        $this->setUser($this->tenant_one_user_one);
        $article = $article_generator->create_public_article();

        $query_name = $this->get_graphql_name(shareto_recipients::class);
        $parameters = [
            'search' => $this->tenant_one_participant->lastname,
            'itemid' => $article->get_id(),
            'component' => article::get_resource_type(),
            'access' => $article->get_access_code(),
            'theme' => 'ventura',
        ];

        $result_one = $this->resolve_graphql_query($query_name, $parameters);

        self::assertIsArray($result_one);
        self::assertNotEmpty($result_one);
        self::assertCount(1, $result_one);

        $result_one_record = reset($result_one);
        self::assertIsArray($result_one_record);

        self::assertArrayHasKey('component', $result_one_record);
        self::assertEquals('core_user', $result_one_record['component']);

        self::assertArrayHasKey('area', $result_one_record);
        self::assertEquals(user::AREA, $result_one_record['area']);

        self::assertArrayHasKey('instanceid', $result_one_record);
        self::assertEquals($this->tenant_one_participant->id, $result_one_record['instanceid']);

        set_config('tenantsisolated', 1);
        $result_two = $this->resolve_graphql_query($query_name, $parameters);

        self::assertIsArray($result_two);
        self::assertNotEmpty($result_two);
        self::assertCount(1, $result_two);

        $result_two_record = reset($result_two);
        self::assertIsArray($result_two_record);

        self::assertArrayHasKey('component', $result_two_record);
        self::assertEquals('core_user', $result_two_record['component']);

        self::assertArrayHasKey('area', $result_two_record);
        self::assertEquals(user::AREA, $result_two_record['area']);

        self::assertArrayHasKey('instanceid', $result_two_record);
        self::assertEquals($this->tenant_one_participant->id, $result_two_record['instanceid']);
    }

    /**
     * @return void
     */
    public function test_find_tenant_member_as_participant_to_share_tenant_article(): void {
        $article_generator = $this->get_article_generator();

        $this->setUser($this->tenant_one_user_one);
        $article = $article_generator->create_public_article();

        // Log in as tenant participant and try to search for member two to share.
        $this->setUser($this->tenant_one_participant);

        $query_name = $this->get_graphql_name(shareto_recipients::class);
        $parameters = [
            'search' => $this->tenant_one_user_two->lastname,
            'itemid' => $article->get_id(),
            'component' => article::get_resource_type(),
            'access' => $article->get_access_code(),
            'theme' => 'ventura',
        ];

        $result_one = $this->resolve_graphql_query($query_name, $parameters);

        self::assertIsArray($result_one);
        self::assertNotEmpty($result_one);
        self::assertCount(1, $result_one);

        $result_one_record = reset($result_one);
        self::assertIsArray($result_one_record);

        self::assertArrayHasKey('component', $result_one_record);
        self::assertEquals('core_user', $result_one_record['component']);

        self::assertArrayHasKey('area', $result_one_record);
        self::assertEquals(user::AREA, $result_one_record['area']);

        self::assertArrayHasKey('instanceid', $result_one_record);
        self::assertEquals($this->tenant_one_user_two->id, $result_one_record['instanceid']);

        set_config('tenantsisolated', 1);
        $result_two = $this->resolve_graphql_query($query_name, $parameters);

        self::assertIsArray($result_two);
        self::assertNotEmpty($result_two);
        self::assertCount(1, $result_two);

        $result_two_record = reset($result_two);
        self::assertIsArray($result_two_record);

        self::assertArrayHasKey('component', $result_two_record);
        self::assertEquals('core_user', $result_two_record['component']);

        self::assertArrayHasKey('area', $result_two_record);
        self::assertEquals(user::AREA, $result_two_record['area']);

        self::assertArrayHasKey('instanceid', $result_two_record);
        self::assertEquals($this->tenant_one_user_two->id, $result_two_record['instanceid']);
    }

    /**
     * @return void
     */
    public function test_find_tenant_member_as_pariticipant_to_share_system_article(): void {
        $article_generator = $this->get_article_generator();

        $this->setUser($this->tenant_one_participant);
        $article = $article_generator->create_public_article();

        $query_name = $this->get_graphql_name(shareto_recipients::class);
        $parameters = [
            'search' => $this->tenant_one_user_one->firstname,
            'itemid' => $article->get_id(),
            'component' => article::get_resource_type(),
            'access' => $article->get_access_code(),
            'theme' => 'ventura',
        ];

        $result_one = $this->resolve_graphql_query($query_name, $parameters);
        self::assertIsArray($result_one);
        self::assertNotEmpty($result_one);
        self::assertCount(1, $result_one);

        $result_one_record = reset($result_one);
        self::assertIsArray($result_one_record);

        self::assertArrayHasKey('component', $result_one_record);
        self::assertEquals('core_user', $result_one_record['component']);

        self::assertArrayHasKey('area', $result_one_record);
        self::assertEquals(user::AREA, $result_one_record['area']);

        self::assertArrayHasKey('instanceid', $result_one_record);
        self::assertEquals($this->tenant_one_user_one->id, $result_one_record['instanceid']);

        set_config('tenantsisolated', 1);
        $result_two = $this->resolve_graphql_query($query_name, $parameters);

        self::assertIsArray($result_two);
        self::assertEmpty($result_two);
    }

    /**
     * @return void
     */
    public function test_find_tenant_member_as_system_user_to_share_system_article(): void {
        $article_generator = $this->get_article_generator();

        $this->setUser($this->system_user_one);
        $article = $article_generator->create_public_article();

        $query_name = $this->get_graphql_name(shareto_recipients::class);
        $parameters = [
            'search' => $this->tenant_one_user_one->firstname,
            'itemid' => $article->get_id(),
            'component' => article::get_resource_type(),
            'access' => $article->get_access_code(),
            'theme' => 'ventura',
        ];

        $result_one = $this->resolve_graphql_query($query_name, $parameters);
        self::assertIsArray($result_one);
        self::assertNotEmpty($result_one);
        self::assertCount(1, $result_one);

        $result_one_record = reset($result_one);
        self::assertIsArray($result_one_record);

        self::assertArrayHasKey('component', $result_one_record);
        self::assertEquals('core_user', $result_one_record['component']);

        self::assertArrayHasKey('area', $result_one_record);
        self::assertEquals(user::AREA, $result_one_record['area']);

        self::assertArrayHasKey('instanceid', $result_one_record);
        self::assertEquals($this->tenant_one_user_one->id, $result_one_record['instanceid']);

        set_config('tenantsisolated', 1);
        $result_two = $this->resolve_graphql_query($query_name, $parameters);

        self::assertIsArray($result_two);
        self::assertEmpty($result_two);
    }

    /**
     * @return void
     */
    public function test_find_tenant_two_member_as_tenant_one_member_to_share_tenant_one_article(): void {
        $article_generator = $this->get_article_generator();

        $this->setUser($this->tenant_one_user_one);
        $article = $article_generator->create_public_article();

        $query_name = $this->get_graphql_name(shareto_recipients::class);
        $parameters = [
            'search' => $this->tenant_two_user->firstname,
            'itemid' => $article->get_id(),
            'component' => article::get_resource_type(),
            'access' => $article->get_access_code(),
            'theme' => 'ventura',
        ];

        $result_one = $this->resolve_graphql_query($query_name, $parameters);
        self::assertIsArray($result_one);
        self::assertEmpty($result_one);

        set_config('tenantsisolated', 1);
        $result_two = $this->resolve_graphql_query($query_name, $parameters);

        self::assertIsArray($result_two);
        self::assertEmpty($result_two);
    }
}