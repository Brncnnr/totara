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

namespace totara_webapi;

use \totara_webapi\endpoint_type\base as endpoint_type;
use totara_webapi\endpoint_type\factory as endpoint_type_factory;

class schema_file_loader {

    /** @var endpoint_type */
    private $type;

    /** @var string */
    private $dir_path = 'webapi';

    public function __construct(endpoint_type $type) {
        $this->type = $type;
    }

    /**
     * Loads all schema files returning the content
     *
     * @return array
     */
    public function load(): array {
        global $CFG;

        $schemas = [];


        // We will be looking for .graphqls files inside the type-specific subdirectory.
        // Start by getting all relevant types.
        $type_names = [$this->type::get_name()];
        if ($this->type->use_all_schemas()) {
            $type_names = array_map(
                function ($type_class_name) {
                    /** @var endpoint_type $type_class_name */
                    return $type_class_name::get_name();
                },
                endpoint_type_factory::get_all_types()
            );
        }

        // Add any additional files from core
        $filenames = $this->get_graphqls_files($CFG->dirroot . '/lib/webapi');
        foreach ($filenames as $filename) {
            // Core file is skipped as it is read separately
            if (preg_match("|" . preg_quote(DIRECTORY_SEPARATOR) . "schema\\.graphqls$|", $filename)) {
                continue;
            }
            $schemas[$filename] = $this->get_schema_file_content($filename);
        }

        // Add schema files for all relevant types.
        foreach ($type_names as $type_name) {
            $filenames = $this->get_graphqls_files($CFG->dirroot . "/lib/webapi/$type_name");
            foreach ($filenames as $filename) {
                $schemas[$filename] = $this->get_schema_file_content($filename);
            }
        }

        // Then read all plugin schema files, here the order or names do not matter
        // as they will all be merged together and then extend the main schema
        $types = \core_component::get_plugin_types();
        foreach ($types as $type => $typedir) {
            $plugins = \core_component::get_plugin_list($type);
            foreach ($plugins as $plugin => $plugindir) {
                // Always get .graphqls files from directly within webapi directory
                $filenames = $this->get_graphqls_files("$plugindir/{$this->dir_path}");
                foreach ($filenames as $filename) {
                    $schemas[$filename] = $this->get_schema_file_content($filename);
                }

                // Add schema files for all relevant types.
                foreach ($type_names as $type_name) {
                    $filenames = $this->get_graphqls_files("$plugindir/{$this->dir_path}/$type_name");
                    foreach ($filenames as $filename) {
                        $schemas[$filename] = $this->get_schema_file_content($filename);
                    }
                }
            }
        }

        if ($CFG->debugdeveloper) {
            foreach (\core_component::get_core_subsystems() as $subsystem => $dir) {
                if (!$dir) {
                    continue;
                }
                $filenames = $this->get_graphqls_files("$dir/webapi");
                if (!empty($filenames)) {
                    debugging('.graphqls files are not allowed in core subsystems, use lib/webapi/schema.graphqls instead');
                }
            }
        }

        return $schemas;
    }

    /**
     * Read contents of given schema file
     *
     * @param string $filename
     * @return string
     * @throws \Exception
     */
    private function get_schema_file_content(string $filename): string {
        $content = file_get_contents($filename);
        if ($content === false) {
            throw new \Exception('Could not read schema file '.$filename);
        }
        return $content;
    }

    /**
     * Get all .graphqls files in given folder
     *
     * @param string $dir
     * @return array
     */
    protected function get_graphqls_files(string $dir): array {
        return local\util::get_files_from_dir($dir, 'graphqls');
    }


}