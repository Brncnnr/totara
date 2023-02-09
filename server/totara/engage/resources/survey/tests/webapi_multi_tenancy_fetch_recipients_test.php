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
 * @package engage_survey
 */
defined('MOODLE_INTERNAL') || die();

use totara_webapi\phpunit\webapi_phpunit_helper;
use totara_engage\webapi\resolver\query\shareto_recipients;
use engage_survey\totara_engage\resource\survey;
use core_user\totara_engage\share\recipient\user;

class engage_survey_webapi_multi_tenancy_fetch_recipients_testcase extends advanced_testcase {
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
            'firstname' => uniqid('tenant_one_participant_one_'),
            'lastname' => uniqid('tenant_one_participant_one_')
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
        $this->tenant_one_participant_one = null;
        $this->tenant_one_participant_two = null;
        $this->system_user_one = null;
        $this->system_user_two = null;
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
     * @return \engage_survey\testing\generator
     */
    private function get_survey_generator(): \engage_survey\testing\generator {
        $generator = $this->getDataGenerator();

        /** @var \engage_survey\testing\generator $survey_generator */
        $survey_generator = $generator->get_plugin_generator('engage_survey');
        return $survey_generator;
    }

    /**
     * Creating a graphql query parameters that are intentionally for survey only.
     *
     * @param survey    $survey
     * @param string    $search_string
     *
     * @return string[]
     */
    private function create_survey_query_parameters(survey $survey, string $search_string): array {
        return [
            'itemid' => $survey->get_id(),
            'component' => survey::get_resource_type(),
            'access' => $survey->get_access_code(),
            'search' => $search_string,
            'theme' => 'ventura',
        ];
    }

    /**
     * @return void
     */
    public function test_find_system_user_as_tenant_member_in_tenant_survey_without_isolation(): void {
        $survey_generator = $this->get_survey_generator();

        $this->setUser($this->tenant_one_user_one);
        $survey = $survey_generator->create_public_survey();

        $result = $this->resolve_graphql_query(
            $this->get_graphql_name(shareto_recipients::class),
            $this->create_survey_query_parameters($survey, $this->system_user_one->firstname)
        );

        self::assertIsArray($result);
        self::assertNotEmpty($result);
        self::assertCount(1, $result);

        $result_record = reset($result);
        self::assertIsArray($result_record);

        self::assertArrayHasKey('component', $result_record);
        self::assertEquals('core_user', $result_record['component']);

        self::assertArrayHasKey('area', $result_record);
        self::assertEquals(user::AREA, $result_record['area']);

        self::assertArrayHasKey('instanceid', $result_record);
        self::assertEquals($this->system_user_one->id, $result_record['instanceid']);
    }

    /**
     * @return void
     */
    public function test_find_system_user_as_tenant_member_in_tenant_survey_with_isolation(): void {
        set_config('tenantsisolated', 1);
        $survey_generator = $this->get_survey_generator();

        $this->setUser($this->tenant_one_user_one);
        $survey = $survey_generator->create_public_survey();

        $result = $this->resolve_graphql_query(
            $this->get_graphql_name(shareto_recipients::class),
            $this->create_survey_query_parameters($survey, $this->system_user_one->lastname)
        );

        self::assertIsArray($result);
        self::assertEmpty($result);
    }

    /**
     * @return void
     */
    public function test_find_tenant_member_as_tenant_member_in_tenant_survey_with_isolation(): void {
        set_config('tenantsisolated', 1);
        $survey_generator = $this->get_survey_generator();

        $this->setUser($this->tenant_one_user_one);
        $survey = $survey_generator->create_public_survey();

        $result = $this->resolve_graphql_query(
            $this->get_graphql_name(shareto_recipients::class),
            $this->create_survey_query_parameters($survey, $this->tenant_one_user_two->firstname)
        );

        self::assertIsArray($result);
        self::assertNotEmpty($result);
        self::assertCount(1, $result);

        $result_record = reset($result);
        self::assertIsArray($result_record);

        self::assertArrayHasKey('component', $result_record);
        self::assertEquals('core_user', $result_record['component']);

        self::assertArrayHasKey('area', $result_record);
        self::assertEquals(user::AREA, $result_record['area']);

        self::assertArrayHasKey('instanceid', $result_record);
        self::assertEquals($this->tenant_one_user_two->id, $result_record['instanceid']);
    }

    /**
     * @return void
     */
    public function test_find_tenant_member_as_tenant_member_in_tenant_survey_without_isolation(): void {
        $survey_generator = $this->get_survey_generator();

        $this->setUser($this->tenant_one_user_one);
        $survey = $survey_generator->create_public_survey();

        $result = $this->resolve_graphql_query(
            $this->get_graphql_name(shareto_recipients::class),
            $this->create_survey_query_parameters($survey, $this->tenant_one_user_two->firstname)
        );

        self::assertIsArray($result);
        self::assertNotEmpty($result);
        self::assertCount(1, $result);

        $result_record = reset($result);
        self::assertIsArray($result_record);

        self::assertArrayHasKey('component', $result_record);
        self::assertEquals('core_user', $result_record['component']);

        self::assertArrayHasKey('area', $result_record);
        self::assertEquals(user::AREA, $result_record['area']);

        self::assertArrayHasKey('instanceid', $result_record);
        self::assertEquals($this->tenant_one_user_two->id, $result_record['instanceid']);
    }

    /**
     * @return void
     */
    public function test_find_tenant_participant_as_tenant_member_in_tenant_survey_with_isolation(): void {
        set_config('tenantsisolated', 1);
        $survey_generator = $this->get_survey_generator();

        $this->setUser($this->tenant_one_user_one);
        $survey = $survey_generator->create_public_survey();

        $result = $this->resolve_graphql_query(
            $this->get_graphql_name(shareto_recipients::class),
            $this->create_survey_query_parameters($survey, $this->tenant_one_participant_one->firstname)
        );

        self::assertIsArray($result);
        self::assertNotEmpty($result);
        self::assertCount(1, $result);

        $result_record = reset($result);
        self::assertIsArray($result_record);

        self::assertArrayHasKey('component', $result_record);
        self::assertEquals('core_user', $result_record['component']);

        self::assertArrayHasKey('area', $result_record);
        self::assertEquals(user::AREA, $result_record['area']);

        self::assertArrayHasKey('instanceid', $result_record);
        self::assertEquals($this->tenant_one_participant_one->id, $result_record['instanceid']);
    }

    /**
     * @return void
     */
    public function test_find_tenant_participant_as_tenant_member_in_tenant_survey_without_isolation(): void {
        $survey_generator = $this->get_survey_generator();

        $this->setUser($this->tenant_one_user_one);
        $survey = $survey_generator->create_public_survey();

        $result = $this->resolve_graphql_query(
            $this->get_graphql_name(shareto_recipients::class),
            $this->create_survey_query_parameters($survey, $this->tenant_one_participant_two->firstname)
        );

        self::assertIsArray($result);
        self::assertNotEmpty($result);
        self::assertCount(1, $result);

        $result_record = reset($result);
        self::assertIsArray($result_record);

        self::assertArrayHasKey('component', $result_record);
        self::assertEquals('core_user', $result_record['component']);

        self::assertArrayHasKey('area', $result_record);
        self::assertEquals(user::AREA, $result_record['area']);

        self::assertArrayHasKey('instanceid', $result_record);
        self::assertEquals($this->tenant_one_participant_two->id, $result_record['instanceid']);
    }

    /**
     * @return void
     */
    public function test_find_system_user_as_tenant_participant_in_tenant_survey_with_isolation(): void {
        set_config('tenantsisolated', 1);
        $survey_generator = $this->get_survey_generator();

        $this->setUser($this->tenant_one_user_two);
        $survey = $survey_generator->create_public_survey();

        // Log in as tenant participant to search for system user.
        $this->setUser($this->tenant_one_participant_one);
        $result = $this->resolve_graphql_query(
            $this->get_graphql_name(shareto_recipients::class),
            $this->create_survey_query_parameters($survey, $this->system_user_two->firstname)
        );

        self::assertIsArray($result);
        self::assertEmpty($result);
    }

    /**
     * @return void
     */
    public function test_find_system_user_as_tenant_participant_in_tenant_survey_without_isolation(): void {
        $survey_generator = $this->get_survey_generator();

        $this->setUser($this->tenant_one_user_two);
        $survey = $survey_generator->create_public_survey();

        // Log in as tenant participant to search for system user.
        $this->setUser($this->tenant_one_participant_two);
        $result = $this->resolve_graphql_query(
            $this->get_graphql_name(shareto_recipients::class),
            $this->create_survey_query_parameters($survey, $this->system_user_one->lastname)
        );

        self::assertIsArray($result);
        self::assertNotEmpty($result);
        self::assertCount(1, $result);

        $result_record = reset($result);
        self::assertIsArray($result_record);

        self::assertArrayHasKey('component', $result_record);
        self::assertEquals('core_user', $result_record['component']);

        self::assertArrayHasKey('area', $result_record);
        self::assertEquals(user::AREA, $result_record['area']);

        self::assertArrayHasKey('instanceid', $result_record);
        self::assertEquals($this->system_user_one->id, $result_record['instanceid']);
    }

    /**
     * @return void
     */
    public function test_find_tenant_member_as_tenant_participant_in_tenant_survey_with_isolation(): void {
        set_config('tenantsisolated', 1);
        $survey_generator = $this->get_survey_generator();

        $this->setUser($this->tenant_one_user_one);
        $survey = $survey_generator->create_public_survey();

        // Log in as tenant participant to search for tenant member.
        $this->setUser($this->tenant_one_participant_one);
        $result = $this->resolve_graphql_query(
            $this->get_graphql_name(shareto_recipients::class),
            $this->create_survey_query_parameters($survey, $this->tenant_one_user_two->firstname)
        );

        self::assertIsArray($result);
        self::assertNotEmpty($result);
        self::assertCount(1, $result);

        $result_record = reset($result);
        self::assertIsArray($result_record);

        self::assertArrayHasKey('component', $result_record);
        self::assertEquals('core_user', $result_record['component']);

        self::assertArrayHasKey('area', $result_record);
        self::assertEquals(user::AREA, $result_record['area']);

        self::assertArrayHasKey('instanceid', $result_record);
        self::assertEquals($this->tenant_one_user_two->id, $result_record['instanceid']);
    }

    /**
     * @return void
     */
    public function test_find_tenant_member_as_tenant_participant_in_tenant_survey_without_isolation(): void {
        $survey_generator = $this->get_survey_generator();

        $this->setUser($this->tenant_one_user_two);
        $survey = $survey_generator->create_public_survey();

        // Log in as tenant participant and find tenant one user one in this survey.
        $this->setUser($this->tenant_one_participant_one);
        $result = $this->resolve_graphql_query(
            $this->get_graphql_name(shareto_recipients::class),
            [
                'itemid' => $survey->get_id(),
                'component' => survey::get_resource_type(),
                'access' => $survey->get_access_code(),
                'search' => $this->tenant_one_user_one->firstname,
                'theme' => 'ventura',
            ]
        );

        self::assertIsArray($result);
        self::assertNotEmpty($result);
        self::assertCount(1, $result);

        $result_record = reset($result);
        self::assertIsArray($result_record);

        self::assertArrayHasKey('component', $result_record);
        self::assertEquals('core_user', $result_record['component']);

        self::assertArrayHasKey('area', $result_record);
        self::assertEquals(user::AREA, $result_record['area']);

        self::assertArrayHasKey('instanceid', $result_record);
        self::assertEquals($this->tenant_one_user_one->id, $result_record['instanceid']);
    }

    /**
     * @return void
     */
    public function test_find_tenant_member_as_system_user_in_tenant_survey_with_isolation(): void {
        set_config('tenantsisolated', 1);
        $survey_generator = $this->get_survey_generator();

        $this->setUser($this->tenant_one_user_one);
        $survey = $survey_generator->create_public_survey();

        // Log in as system user and search for the tenant one user two.
        $this->setUser($this->system_user_one);

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage(get_string('error:permissiondenied', 'totara_engage'));

        $this->resolve_graphql_query(
            $this->get_graphql_name(shareto_recipients::class),
            $this->create_survey_query_parameters($survey, $this->tenant_one_user_two->firstname)
        );
    }

    /**
     * @return void
     */
    public function test_find_tenant_member_as_system_user_in_tenant_survey_without_isolation(): void {
        $survey_generator = $this->get_survey_generator();
        $this->setUser($this->tenant_one_user_one);
        $survey = $survey_generator->create_public_survey();

        $this->setUser($this->system_user_two);

        $result = $this->resolve_graphql_query(
            $this->get_graphql_name(shareto_recipients::class),
            $this->create_survey_query_parameters($survey, $this->tenant_one_user_two->lastname)
        );

        self::assertIsArray($result);
        self::assertNotEmpty($result);
        self::assertCount(1, $result);

        $result_record = reset($result);
        self::assertIsArray($result_record);

        self::assertArrayHasKey('component', $result_record);
        self::assertEquals('core_user', $result_record['component']);

        self::assertArrayHasKey('area', $result_record);
        self::assertEquals(user::AREA, $result_record['area']);

        self::assertArrayHasKey('instanceid', $result_record);
        self::assertEquals($this->tenant_one_user_two->id, $result_record['instanceid']);
    }

    /**
     * @return void
     */
    public function test_find_tenant_member_as_system_user_in_system_survey_with_isolation(): void {
        set_config('tenantsisolated', 1);
        $survey_generator = $this->get_survey_generator();

        $this->setUser($this->system_user_one);
        $survey = $survey_generator->create_public_survey();

        // Search for tenant member in this survey.
        $query_name = $this->get_graphql_name(shareto_recipients::class);
        $result_one = $this->resolve_graphql_query(
            $query_name,
            $this->create_survey_query_parameters($survey, $this->tenant_one_user_one->firstname)
        );

        self::assertIsArray($result_one);
        self::assertEmpty($result_one);

        $result_two = $this->resolve_graphql_query(
            $query_name,
            $this->create_survey_query_parameters($survey, $this->tenant_two_user->lastname)
        );

        self::assertIsArray($result_two);
        self::assertEmpty($result_two);
    }

    /**
     * @return void
     */
    public function test_find_tenant_member_as_system_user_in_system_survey_without_isolation(): void {
        $survey_generator = $this->get_survey_generator();
        $this->setUser($this->system_user_one);

        $survey = $survey_generator->create_public_survey();

        // Search for tenant one user two.
        $result = $this->resolve_graphql_query(
            $this->get_graphql_name(shareto_recipients::class),
            $this->create_survey_query_parameters($survey, $this->tenant_one_user_two->firstname)
        );

        self::assertIsArray($result);
        self::assertNotEmpty($result);
        self::assertCount(1, $result);

        $result_record = reset($result);
        self::assertIsArray($result_record);

        self::assertArrayHasKey('component', $result_record);
        self::assertEquals('core_user', $result_record['component']);

        self::assertArrayHasKey('area', $result_record);
        self::assertEquals(user::AREA, $result_record['area']);

        self::assertArrayHasKey('instanceid', $result_record);
        self::assertEquals($this->tenant_one_user_two->id, $result_record['instanceid']);
    }

    /**
     * @return void
     */
    public function test_find_tenant_member_as_tenant_participant_in_system_survey_with_isolation(): void {
        set_config('tenantsisolated', 1);
        $survey_generator = $this->get_survey_generator();

        $this->setUser($this->system_user_one);
        $survey = $survey_generator->create_public_survey();

        // Log in as participant user and search for tenant one user.
        $this->setUser($this->tenant_one_participant_one);
        $result = $this->resolve_graphql_query(
            $this->get_graphql_name(shareto_recipients::class),
            $this->create_survey_query_parameters($survey, $this->tenant_one_user_one->firstname)
        );

        self::assertIsArray($result);
        self::assertEmpty($result);
    }

    /**
     * @return void
     */
    public function test_find_tenant_member_as_tenant_participant_in_system_survey_without_isolation(): void {
        $survey_generator = $this->get_survey_generator();
        $this->setUser($this->system_user_two);

        $survey = $survey_generator->create_public_survey();

        // Log in as tenant participant user and search for tenant one user(s).
        $this->setUser($this->tenant_one_participant_two);
        $result = $this->resolve_graphql_query(
            $this->get_graphql_name(shareto_recipients::class),
            $this->create_survey_query_parameters($survey, $this->tenant_one_user_two->lastname)
        );

        self::assertIsArray($result);
        self::assertNotEmpty($result);
        self::assertCount(1, $result);

        $result_record = reset($result);
        self::assertIsArray($result_record);

        self::assertArrayHasKey('component', $result_record);
        self::assertEquals('core_user', $result_record['component']);

        self::assertArrayHasKey('area', $result_record);
        self::assertEquals(user::AREA, $result_record['area']);

        self::assertArrayHasKey('instanceid', $result_record);
        self::assertEquals($this->tenant_one_user_two->id, $result_record['instanceid']);
    }

    /**
     * @return void
     */
    public function test_find_tenant_two_member_as_tenant_one_member_in_system_survey_without_isolation(): void {
        $survey_generator = $this->get_survey_generator();
        $this->setUser($this->system_user_one);

        $survey = $survey_generator->create_public_survey();

        // Log in as tenant one user one to search for tenant two member.
        $this->setUser($this->tenant_one_user_one);
        $result = $this->resolve_graphql_query(
            $this->get_graphql_name(shareto_recipients::class),
            $this->create_survey_query_parameters($survey, $this->tenant_two_user->firstname)
        );

        self::assertIsArray($result);
        self::assertEmpty($result);
    }

    /**
     * @return void
     */
    public function test_find_tenant_two_member_as_tenant_one_member_in_system_survey_with_isolation(): void {
        set_config('tenantsisolated', 1);
        $survey_generator = $this->get_survey_generator();

        $this->setUser($this->system_user_two);
        $survey = $survey_generator->create_public_survey();

        // Log in as tenant one user one to search for tenant two member.
        $this->setUser($this->tenant_one_user_one);

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage(get_string('error:permissiondenied', 'totara_engage'));

        $this->resolve_graphql_query(
            $this->get_graphql_name(shareto_recipients::class),
            $this->create_survey_query_parameters($survey, $this->tenant_two_user->lastname)
        );
    }
}