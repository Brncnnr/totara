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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_webapi
 */

use GraphQL\Server\OperationParams;
use totara_webapi\graphql;
use totara_webapi\persistent_operations_loader;

defined('MOODLE_INTERNAL') || die();

class totara_webapi_persistent_operations_loader_test extends \advanced_testcase {

    public function test_load_non_existing_operation() {
        $query_id = 'foobarbaz';

        $params = OperationParams::create([
            'querId' => $query_id,
            'webapi_type' => graphql::TYPE_DEV,
        ]);

        $loader = new persistent_operations_loader();

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Invalid Web API operation name');

        $loader($query_id, $params);
    }

    public function test_load_invalid_type() {
        $query_id = 'foobarbaz';

        $params = OperationParams::create([
            'querId' => $query_id,
            'webapi_type' => 'idonotexist',
        ]);

        $loader = new persistent_operations_loader();

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Invalid type given');

        $loader($query_id, $params);
    }

    public function test_load_no_type() {
        $query_id = 'foobarbaz';

        $params = OperationParams::create([
            'querId' => $query_id
        ]);

        $loader = new persistent_operations_loader();

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('No type given');

        $loader($query_id, $params);
    }

    public function test_dev_type_does_not_have_persisted_operations() {
        $query_id = 'totara_webapi_status_nosession';

        $params = OperationParams::create([
            'querId' => $query_id,
            'webapi_type' => graphql::TYPE_DEV,
        ]);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Invalid Web API operation name');

        $loader = new persistent_operations_loader();
        $loader($query_id, $params);
    }

    public function test_load_existing_operation() {
        global $CFG;

        $query_id = 'totara_webapi_status_nosession';

        $params = OperationParams::create([
            'querId' => $query_id,
            'webapi_type' => graphql::TYPE_AJAX,
        ]);

        $loader = new persistent_operations_loader();
        $schema_string = $loader($query_id, $params);

        $this->assertStringEqualsFile($CFG->dirroot.'/totara/webapi/webapi/ajax/status_nosession.graphql', $schema_string);
    }

    public function test_caching_of_operations() {
        set_config('cache_graphql_schema', false);

        $query_id = 'totara_webapi_status_nosession';

        $params = OperationParams::create([
            'querId' => $query_id,
            'webapi_type' => graphql::TYPE_AJAX,
        ]);

        $cache = \cache::make('totara_webapi', 'persistedoperations');
        $cached_operations = $cache->get(graphql::TYPE_AJAX);

        $this->assertEmpty($cached_operations);

        $loader = new persistent_operations_loader();

        $schema_string = $loader($query_id, $params);
        $this->assertNotEmpty($schema_string);

        // Now try the same thing with caching enabled
        set_config('cache_graphql_schema', true);

        $schema_string2 = $loader($query_id, $params);
        $this->assertSame($schema_string, $schema_string2);

        // There should be something in the cache now
        $cached_operations = $cache->get(graphql::TYPE_AJAX);
        $this->assertNotEmpty($cached_operations);
    }

}