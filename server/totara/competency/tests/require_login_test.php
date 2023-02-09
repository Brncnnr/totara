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
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @group totara_competency
 */
class totara_competency_require_login_testcase extends advanced_testcase {

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
            $result[$mutation] = [$mutation];
        }
        return $result;
    }

    /**
     * @dataProvider get_webapi_mutation_data_provider
     * @param string $mutation_name
     */
    public function test_webapi_mutators_throw_error_if_not_logged_in(string $mutation_name): void {
        $operation = str_replace('\\webapi\\resolver\\mutation\\', '_', $mutation_name);

        try {
            $this->resolve_graphql_mutation($operation);
            $this->fail('require_login_exception not thrown.');
        } catch (require_login_exception $require_login_exception) {
            // What we want.
        } catch (Exception $exception) {
            $this->fail('require_login_exception not thrown.');
        }

        self::setAdminUser();

        try {
            $this->resolve_graphql_mutation($operation);
            $this->fail('require_login_exception not thrown.');
        } catch (require_login_exception $require_login_exception) {
            $this->fail('require_login_exception was thrown despite being logged in.');
        } catch (Exception | TypeError $exception) {
            // What we want.
        }
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
            $result[$query] = [$query];
        }
        return $result;
    }

    /**
     * @dataProvider get_webapi_query_data_provider
     * @param string $query_name
     */
    public function test_webapi_queries_throw_error_if_not_logged_in(string $query_name): void {
        $operation = str_replace('\\webapi\\resolver\\query\\', '_', $query_name);

        try {
            $this->resolve_graphql_query($operation);
            $this->fail('require_login_exception not thrown.');
        } catch (require_login_exception $require_login_exception) {
            // What we want.
        } catch (Exception $exception) {
            $this->fail('require_login_exception not thrown.');
        }

        self::setAdminUser();

        try {
            $this->resolve_graphql_query($operation);
            $this->fail('require_login_exception not thrown.');
        } catch (require_login_exception $require_login_exception) {
            $this->fail('require_login_exception was thrown despite being logged in.');
        } catch (Exception | TypeError $exception) {
            // What we want.
        }
    }

}
