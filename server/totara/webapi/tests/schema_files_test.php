<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package totara_webapi
 */

use core_phpunit\testcase;
use totara_webapi\endpoint_type\ajax;
use totara_webapi\endpoint_type\dev;
use totara_webapi\schema_builder;
use totara_webapi\schema_file_loader;
use totara_webapi\endpoint_type\base as endpoint_type;
use totara_webapi\endpoint_type\factory as endpoint_type_factory;

class totara_webapi_schema_files_testcase extends testcase {

    public function test_ajax(): void {
        global $CFG;

        // Load the graphql files and confirm that ajax/test.graphqls is picked up.
        $schema_file_loader = new schema_file_loader(new ajax());
        $this->set_dir_path($schema_file_loader, 'tests/fixtures/webapi');
        $schemas = $schema_file_loader->load();
        $this->assertArrayHasKey(
            $CFG->dirroot . '/totara/webapi/tests/fixtures/webapi/ajax/test.graphqls',
            $schemas
        );
        $this->assertArrayNotHasKey(
            $CFG->dirroot . '/totara/webapi/tests/fictures/webapi/dev/test.graphqls',
            $schemas
        );
    }

    public function test_dev(): void {
        global $CFG;

        // Load the graphql files and confirm that ajax/test.graphqls and dev/test.graphqls is picked up.
        $schema_file_loader = new schema_file_loader(new dev());
        $this->set_dir_path($schema_file_loader, 'tests/fixtures/webapi');
        $schemas = $schema_file_loader->load();
        $this->assertArrayHasKey(
            $CFG->dirroot . '/totara/webapi/tests/fixtures/webapi/ajax/test.graphqls',
            $schemas
        );
        $this->assertArrayHasKey(
            $CFG->dirroot . '/totara/webapi/tests/fixtures/webapi/dev/test.graphqls',
            $schemas
        );
    }

    /**
     * Confirm that duplicate query names are picked up in all endpoint types.
     *
     * @return void
     */
    public function test_duplicate_query_names(): void {
        $types = endpoint_type_factory::get_all_types();
        foreach ($types as $type) {
            $type = new $type();

            // First clear cache.
            $this->clear_schema_cache($type);

            $schema_file_loader = new schema_file_loader($type);
            $this->set_dir_path($schema_file_loader, 'tests/fixtures/webapi');
            $schema_builder = new schema_builder($schema_file_loader, $type);
            $schema = $schema_builder->build();

            try {
                $schema->assertValid();
                $this->fail("Expected duplicate queries to fail schema validation.");
            } catch (Exception $e) {
                $this->assertStringContainsString(
                    'Field Query.test_query_1 can only be defined once.',
                    $e->getMessage()
                );
            }
        }
    }

    /**
     * Confirm that all the endpoint types have valid schemas.
     *
     * @return void
     */
    public function test_schema_validation(): void {
        $types = endpoint_type_factory::get_all_types();
        foreach ($types as $type) {
            $type = new $type();
            $this->clear_schema_cache($type);
            (new schema_builder(new schema_file_loader($type), $type))->build()->assertValid();
        }
    }

    private function clear_schema_cache(endpoint_type $type): void {
        $cache = \cache::make('totara_webapi', 'schema');
        $cache_key = 'parsed_schema_' . $type::get_name();
        $cache->delete($cache_key);
    }

    private function set_dir_path(schema_file_loader $schema_file_loader, string $path): void {
        $class = new ReflectionClass(schema_file_loader::class);
        $property = $class->getProperty('dir_path');
        $property->setAccessible(true);
        $property->setValue($schema_file_loader, $path);
    }

}
