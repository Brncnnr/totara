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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package mod_perform
 */

use core\webapi\mutation_resolver;
use core\webapi\query_resolver;
use mod_perform\controllers\activity\print_user_activity;
use mod_perform\controllers\activity\view_user_activity;
use mod_perform\entity\activity\activity as activity_entity;
use mod_perform\entity\activity\subject_instance;
use totara_core\advanced_feature;
use totara_core\feature_not_available_exception;
use totara_mvc\admin_controller;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @group perform
 */
class mod_perform_advanced_feature_disable_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    protected function setUp(): void {
        parent::setUp();
        advanced_feature::disable('performance_activities');
    }

    public function get_webapi_mutation_data_provider(): array {
        $result = [];
        $mutations = core_component::get_namespace_classes('webapi\\resolver\\mutation', mutation_resolver::class, 'mod_perform');
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
        $this->expectExceptionMessage('Feature performance_activities is not available.');

        $operation = str_replace('\\webapi\\resolver\\mutation\\', '_', $mutation_name);
        $this->resolve_graphql_mutation($operation);
    }

    public function get_webapi_query_data_provider(): array {
        $result = [];
        $queries = core_component::get_namespace_classes('webapi\\resolver\\query', query_resolver::class, 'mod_perform');
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
        try {
            $operation = str_replace('\\webapi\\resolver\\query\\', '_', $query_name);
            $this->resolve_graphql_query($operation);
            $this->fail('Expected exception');
        } catch (feature_not_available_exception $ex) {
            $debugging_messages = $this->getDebuggingMessages();
            $this->resetDebugging();
            if ($debugging_messages) {
                $debugging_message = reset($debugging_messages);
                $this->assertSame($debugging_message->message, 'mod_perform\webapi\resolver\query\notifications::get_middleware has been deprecated');
            }
            $this->assertStringContainsString('Feature performance_activities is not available.', $ex->getMessage());
        }
    }

    /**
     * Returns an array with all mod_perform controllers
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
            array_keys(core_component::get_component_classes_in_namespace('mod_perform', 'controllers')),
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
        if ($controller === view_user_activity::class || $controller === print_user_activity::class) {
            $this->markTestSkipped('This controller needs special setup, skipping.');
        }
        self::setAdminUser();

        /** @var \mod_perform\testing\generator $generator */
        $generator = \mod_perform\testing\generator::instance();

        $config = \mod_perform\testing\activity_generator_configuration::new()
            ->set_number_of_activities(1)
            ->set_number_of_tracks_per_activity(1)
            ->set_number_of_users_per_user_group_type(1);

        $generator->create_full_activities($config);

        $_GET['activity_id'] = activity_entity::repository()->order_by('id')->first()->id;
        $_GET['subject_instance_id'] = subject_instance::repository()->order_by('id')->first()->id;

        $this->expectException(feature_not_available_exception::class);
        $this->expectExceptionMessage('Feature performance_activities is not available.');

        (new $controller())->process();
    }

}
