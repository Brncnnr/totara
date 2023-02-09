<?php
/*
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Samantha Jayasinghe <samantha.jayasinghe@totaralearning.com>
 * @package totara_engage
 */

defined('MOODLE_INTERNAL') || die();

use totara_core\advanced_feature;
use totara_webapi\phpunit\webapi_phpunit_helper;
use engage_article\totara_engage\resource\article;
/**
 * Tests the user_contributions query
 */
class totara_engage_webapi_resolver_query_user_contributions_testcase extends advanced_testcase {

    use webapi_phpunit_helper;

    private const QUERY = 'totara_engage_user_contributions';
    private const OPERATION_NAME = 'totara_engage_user_contribution_cards';

    private function execute_query(array $args) {
        return $this->resolve_graphql_query(self::QUERY, $args);
    }

    private function setup_user() {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        return $user;
    }

    private function create_article($name, $userid, $content = null): article {
        /** @var \engage_article\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('engage_article');
        $params = ['name' => $name, 'userid' => $userid];
        if ($content !== null) {
            $params['content'] = $content;
        }

        return $generator->create_article($params);
    }

    public function test_user_contribution(): void {
        $user = $this->setup_user();
        $this->create_article('test', $user->id);
        $result = $this->execute_query([
            'component' => 'engage_article',
            'user_id' => $user->id,
            'area' => 'otheruserlib',
            'theme' => 'ventura',
        ]);
        $this->assertArrayHasKey('cursor', $result);
        $this->assertArrayHasKey('cards', $result);
    }

    public function test_no_arguments(): void {
        $this->setup_user();
        self::expectException(coding_exception::class);
        $this->execute_query([]);
    }

    public function test_invalid_component_name_not_accepted(): void {
        $user = $this->setup_user();
        self::expectException(coding_exception::class);
        self::expectExceptionMessage("Component is a required field.");
        $this->execute_query([
            'user_id' => $user->id,
            'area' => 'otheruserlib',
            'theme' => 'ventura',
        ]);
    }

    public function test_invalid_area_not_accepted(): void {
        $user = $this->setup_user();
        self::expectException(coding_exception::class);
        self::expectExceptionMessage("Query user_contributions does not support the 'test' area.");
        $this->execute_query([
            'component' => 'engage_article',
            'user_id' => $user->id,
            'area' => 'test',
            'theme' => 'ventura',
        ]);
    }

    public function test_invalid_userid_not_accepted(): void {
        $this->setup_user();
        self::expectException(coding_exception::class);
        self::expectExceptionMessage('Query user_contributions must specify the "user_id" field');
        $this->execute_query([
            'component' => 'engage_article',
            'theme' => 'ventura',
        ]);
    }

    public function test_successful_ajax_call(): void {
        $user = $this->setup_user();

        $result = $this->parsed_graphql_operation(
            self::OPERATION_NAME,
            [
                'component' => 'engage_article',
                'user_id' => $user->id,
                'area' => 'otheruserlib',
                'include_footnotes' => true,
                'theme' => 'ventura',
            ]
        );
        $this->assert_webapi_operation_successful($result);
    }

    public function test_failed_ajax_query(): void {
        self::setAdminUser();

        $user = $this->getDataGenerator()->create_user();
        $feature = 'engage_resources';
        advanced_feature::disable($feature);
        $result = $this->parsed_graphql_operation(
            self::OPERATION_NAME,
            [
                'component' => 'engage_article',
                'user_id' => $user->id,
                'area' => 'otheruserlib',
                'include_footnotes' => false,
                'theme' => 'ventura',
            ]
        );
        $this->assert_webapi_operation_failed($result, 'Feature engage_resources is not available.');
    }
}