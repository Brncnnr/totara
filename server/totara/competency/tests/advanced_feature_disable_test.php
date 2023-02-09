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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package totara_competency
 */

use core\webapi\mutation_resolver;
use core\webapi\query_resolver;
use totara_competency\webapi\resolver\query\competency;
use totara_competency\webapi\resolver\query\linked_courses;
use totara_competency\webapi\resolver\query\scale;
use totara_competency\webapi\resolver\query\scales;
use totara_competency\webapi\resolver\query\user_assignments;
use totara_core\advanced_feature;
use totara_core\feature_not_available_exception;
use totara_mvc\admin_controller;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @group totara_competency
 */
class totara_competency_advanced_feature_disable_testcase extends advanced_testcase {

    use webapi_phpunit_helper;

    /**
     * @return string[]
     */
    protected static function get_components(): array {
        $parent_components = [
            'totara_competency',
            'totara_criteria',
        ];

        $subplugins = [];
        foreach ($parent_components as $component) {
            $subplugins[] = core_component::get_subplugins($component);
        }
        $subplugins = array_merge(...$subplugins);

        $components = $parent_components;
        foreach ($subplugins as $plugin_type => $plugin_names) {
            foreach ($plugin_names as $plugin_name) {
                $components[] = "{$plugin_type}_{$plugin_name}";
            }
        }
        return $components;
    }

    /**
     * @return string[]
     */
    protected static function get_classes_to_ignore(): array {
        return [
            competency::class,
            linked_courses::class,
            scale::class,
            scales::class,
            user_assignments::class,
        ];
    }

    protected function setUp(): void {
        parent::setUp();
        self::setAdminUser();
        advanced_feature::disable('competency_assignment');
    }

    public function get_webapi_mutation_data_provider(): array {
        $mutations = [];
        foreach (self::get_components() as $component) {
            $mutations[] = core_component::get_namespace_classes(
                'webapi\\resolver\\mutation',
                mutation_resolver::class,
                $component
            );
        }
        $mutations = array_merge(...$mutations);

        $result = [];
        foreach ($mutations as $mutation) {
            if (in_array($mutation, self::get_classes_to_ignore())) {
                continue;
            }
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
        $this->expectExceptionMessage('Feature competency_assignment is not available.');

        $operation = str_replace('\\webapi\\resolver\\mutation\\', '_', $mutation_name);
        $this->resolve_graphql_mutation($operation);
    }

    public function get_webapi_query_data_provider(): array {
        $queries = [];
        foreach (self::get_components() as $component) {
            $queries[] = core_component::get_namespace_classes(
                'webapi\\resolver\\query',
                query_resolver::class,
                $component
            );
        }
        $queries = array_merge(...$queries);

        $result = [];
        foreach ($queries as $query) {
            if (in_array($query, self::get_classes_to_ignore())) {
                continue;
            }
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
        $this->expectExceptionMessage('Feature competency_assignment is not available.');

        $operation = str_replace('\\webapi\\resolver\\query\\', '_', $query_name);
        $this->resolve_graphql_query($operation);
    }

    /**
     * Returns an array with all totara_competency controllers
     *
     * @return string[]
     */
    public function get_controller_data_provider(): array {
        $controllers = [];
        foreach (self::get_components() as $component) {
            $controllers[] = $this->get_controller_classes($component);
        }
        $controllers = array_merge(...$controllers);

        $result = [];
        foreach ($controllers as $controller) {
            if (in_array($controller, self::get_classes_to_ignore())) {
                continue;
            }
            $result[$controller] = [$controller];
        }
        return $result;
    }

    /**
     * @param string $component
     * @return string[]
     */
    private function get_controller_classes(string $component): array {
        return array_filter(
            array_keys(core_component::get_component_classes_in_namespace($component, 'controllers')),
            static function (string $class_name) {
                // Admin controllers do check features differently so ignore those
                return !is_subclass_of($class_name, admin_controller::class)
                    && !(new ReflectionClass($class_name))->isAbstract();
            }
        );
    }

    /**
     * @dataProvider get_controller_data_provider
     * @param string $controller
     * @throws coding_exception
     */
    public function test_controllers_throw_error_if_feature_is_disabled(string $controller): void {
        self::setAdminUser();

        /** @var \totara_competency\testing\generator $generator */
        $generator = self::getDataGenerator()->get_plugin_generator('totara_competency');
        $competency = $generator->create_competency();

        $_GET['competency_id'] = $competency->id;

        $this->expectException(feature_not_available_exception::class);
        $this->expectExceptionMessage('Feature competency_assignment is not available.');

        (new $controller())->process();
    }

}
