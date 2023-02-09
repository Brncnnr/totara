<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Simon Player <simon.player@totaralearning.com>
 * @package totara_reportbuilder
 */

namespace totara_reportbuilder\webapi\resolver\type;

use \core\webapi\execution_context;

/**
 * Report template type
 */
class template extends \core\webapi\type_resolver {

    /**
     * Resolves a report template field.
     *
     * @param string $field
     * @param $template
     * @param array $args
     * @param execution_context $ec
     * @return mixed
     */
    public static function resolve(string $field, $template, array $args, execution_context $ec) {
        if (!$template instanceof \totara_reportbuilder\rb\template\base) {
            throw new \coding_exception('Only reportbuilder template objects are accepted');
        }

        switch ($field) {
            case 'key':
                // Don't return the entire namespaced classname, only the final name.
                $class = explode("\\", get_class($template));
                return end($class);
            case 'fullname':
                return $template->fullname;
            case 'label':
                return $template->label;
            case 'summary':
                return $template->summary;
            case 'graph':
                return empty($template->graph['type']) ? '' : $template->graph['type'];
            case 'defaultcolumns':
                return self::default_columns($template);
            case 'source':
                global $CFG;
                require_once($CFG->dirroot . '/totara/reportbuilder/lib.php');

                return \reportbuilder::get_source_object($template->source);
        }

        throw new \coding_exception('Unknown field', $field);
    }

    /**
     * Get the template default columns
     *
     * @param $template
     * @return array
     */
    public static function default_columns($template) {
        $output = [];

        $source = \reportbuilder::get_source_object($template->source);

        foreach ($template->columns as $defaultcolumns) {
            foreach ($source->columnoptions as $columnoption) {
                if ($columnoption->type == $defaultcolumns['type'] && $columnoption->value == $defaultcolumns['value']) {
                    // Get the type name.
                    $langstr = 'type_' . $columnoption->type;
                    if (get_string_manager()->string_exists($langstr, '' . get_class($source))) {
                        // Is there a type string in the source file?
                        $columnoption->type = get_string($langstr, '' . get_class($source));
                    } else if (get_string_manager()->string_exists($langstr, 'totara_reportbuilder')) {
                        // How about in report builder?
                        $columnoption->type = get_string($langstr, 'totara_reportbuilder');
                    }

                    $output[] = $columnoption;

                    continue;
                }
            }
        }

        return $output;
    }
}