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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_webapi
 */

namespace totara_webapi;

use GraphQL\Server\OperationParams;
use totara_webapi\local\util;
use totara_webapi\endpoint_type\factory as endpoint_type_factory;

/**
 * Load persistent operation from .graphql file.
 *
 * This is not the classic understanding of persistent queries. Usually persistent
 * queries are using some normalisation and hashes to identify persistent queries.
 *
 * Our frontend client (Apollo) does support those but it would require a more complex
 * setup using a build step to make sure that queries are persisted and useable by Frontend and Backend.
 *
 * In our special implementation we just use the operation name as the unique identifier of the persistent queries.
 * All persistent queries are stored as .graphql files living in the webapi/ajax (or webapi/mobile) folder.
 * The name is used to identify the queries and changes in those files will automatically
 * affect the frontend without the need for rebuilding them.
 *
 * @package totara_webapi
 */
class persistent_operations_loader {

    /**
     * This function can be called as callable, i.e. by the GraphQL library.
     *
     * It tries to find the persistent query using the queryId (which is in reality
     * the same as the operationName and returns the query string.
     *
     * @param $operation_name This is the usually the queryId, in our case both are the same
     * @param OperationParams $operation_params
     * @return string
     * @throws \coding_exception
     */
    public function __invoke($operation_name, OperationParams $operation_params): string {
        $type = $operation_params->getOriginalInput('webapi_type');
        if ($type === null) {
            throw new \coding_exception('No type given. Make sure the OperationParams contains the type in \'webapi_type\'');
        }

        if (!endpoint_type_factory::type_exists($type)) {
            throw new \coding_exception('Invalid type given. Make sure the OperationParams contains the type in \'webapi_type\'');
        }

        $all_operations = self::get_operations($type);
        if (!isset($all_operations[$operation_name])) {
            throw new \coding_exception('Invalid Web API operation name');
        }

        $operation_string = file_get_contents($all_operations[$operation_name]);
        if (!$operation_string) {
            throw new \coding_exception('Invalid Web API operation file');
        }

        return $operation_string;
    }

    /**
     * Returns list of valid persisted operations and their file locations.
     *
     * @param string $type API type such as 'ajax', 'external' or 'mobile'
     * @return array operation name is key, value is the full file path to persisted operation
     */
    public static function get_operations(string $type): array {
        global $CFG;

        if ($type !== clean_param($type, PARAM_ALPHA)) {
            throw new \coding_exception('Invalid operation type');
        }

        if (isset($CFG->cache_graphql_schema) && $CFG->cache_graphql_schema == false) {
            return self::build_persisted_operations_array($type);
        }

        $cache = \cache::make('totara_webapi', 'persistedoperations');
        $operations = $cache->get($type);
        if (!$operations) {
            $operations = self::build_persisted_operations_array($type);
            $cache->set($type, $operations);
        }

        return $operations;
    }

    /**
     * Build array containing all persisted operations for the given type
     * @param string $type
     * @return array
     */
    protected static function build_persisted_operations_array(string $type): array {
        global $CFG;

        $subdir = 'webapi/' . $type;

        $operations = [];

        $files = util::get_files_from_dir($CFG->libdir . '/' . $subdir, 'graphql');
        foreach ($files as $name => $file) {
            $operation_name = 'core_' . $name;
            $operations[$operation_name] = $file;
        }

        foreach (\core_component::get_core_subsystems() as $subsystem => $full_dir) {
            $files = util::get_files_from_dir($full_dir . '/' . $subdir, 'graphql');
            foreach ($files as $name => $file) {
                $operation_name = 'core_' . $subsystem . '_' . $name;
                $operations[$operation_name] = $file;
            }
        }

        $plugin_types = \core_component::get_plugin_types();
        foreach ($plugin_types as $plugin_type => $unused) {
            $plugins = \core_component::get_plugin_list($plugin_type);
            foreach ($plugins as $plugin => $full_dir) {
                $files = util::get_files_from_dir($full_dir . '/' . $subdir, 'graphql');
                foreach ($files as $name => $file) {
                    $operation_name = $plugin_type . '_' . $plugin . '_' . $name;
                    $operations[$operation_name] = $file;
                }
            }
        }

        return $operations;
    }

}