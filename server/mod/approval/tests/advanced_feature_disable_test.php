<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package mod_approval
 */

use core\webapi\mutation_resolver;
use core\webapi\query_resolver;
use core_phpunit\testcase;
use totara_core\advanced_feature;
use totara_core\feature_not_available_exception;
use totara_mvc\admin_controller;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @group approval_workflow
 */
class mod_approval_advanced_feature_disable_testcase extends testcase {
    use webapi_phpunit_helper;

    protected function setUp(): void {
        advanced_feature::disable('approval_workflows');
    }

    protected function tearDown(): void {
        advanced_feature::enable('approval_workflows');
    }

    /**
     * Data provider for mutations test.
     *
     * @return array
     */
    public function get_webapi_mutation_data_provider(): array {
        $result = [];
        $mutations = core_component::get_namespace_classes('webapi\\resolver\\mutation', mutation_resolver::class, 'mod_approval');
        foreach ($mutations as $mutation) {
            $result[$mutation] = [$mutation];
        }
        return $result;
    }

    /**
     * @dataProvider get_webapi_mutation_data_provider
     * @param string $mutation_name
     */
    public function test_webapi_mutators_throw_error_if_feature_is_disabled(string $mutation_name): void {
        $this->expectException(feature_not_available_exception::class);
        $this->expectExceptionMessage('Feature approval_workflows is not available.');

        $operation = str_replace('\\webapi\\resolver\\mutation\\', '_', $mutation_name);
        $this->resolve_graphql_mutation($operation);
    }

    /**
     * Data provider for queries test.
     *
     * @return array
     */
    public function get_webapi_query_data_provider(): array {
        $result = [];
        $queries = core_component::get_namespace_classes('webapi\\resolver\\query', query_resolver::class, 'mod_approval');
        foreach ($queries as $query) {
            $result[$query] = [$query];
        }
        return $result;
    }

    /**
     * @dataProvider get_webapi_query_data_provider
     * @param string $query_name
     */
    public function test_webapi_queries_throw_error_if_feature_is_disabled(string $query_name): void {
        $this->expectException(feature_not_available_exception::class);
        $this->expectExceptionMessage('Feature approval_workflows is not available.');

        $operation = str_replace('\\webapi\\resolver\\query\\', '_', $query_name);
        $this->resolve_graphql_query($operation);
    }

    /**
     * Data provider for controller classes
     *
     * @return string[]
     */
    public function get_controller_data_provider(): array {
        $result = [];
        $controllers  = $this->get_controller_classes();
        foreach ($controllers as $controller) {
            $result[$controller] = [$controller];
        }
        return $result;
    }

    /**
     * @return string[]
     */
    private function get_controller_classes(): array {
        return array_filter(
            array_keys(core_component::get_component_classes_in_namespace('mod_approval', 'controllers')),
            static function (string $class_name) {
                // If admin controllers check features differently: !is_subclass_of($class_name, admin_controller::class)
                return !(new ReflectionClass($class_name))->isAbstract();
            }
        );
    }

    /**
     * @dataProvider get_controller_data_provider
     * @param string $controller
     */
    public function test_controllers_throw_error_if_feature_is_disabled(string $controller): void {
        self::setAdminUser();

        $this->expectException(feature_not_available_exception::class);
        $this->expectExceptionMessage('Feature approval_workflows is not available.');

        (new $controller())->process();
    }

}
