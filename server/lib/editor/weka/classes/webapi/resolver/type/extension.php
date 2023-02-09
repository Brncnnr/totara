<?php
/**
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package editor_weka
 */
namespace editor_weka\webapi\resolver\type;

use core\webapi\execution_context;
use core\webapi\type_resolver;
use editor_weka\extension\extension as model;

/**
 * Type resolver for editor_weka_extesion
 */
class extension extends type_resolver {
    /**
     * @param string            $field
     * @param model             $source
     * @param array             $args
     * @param execution_context $ec
     *
     * @return mixed|void
     */
    public static function resolve(string $field, $source, array $args, execution_context $ec) {
        if (!($source instanceof model)) {
            $cls = model::class;
            throw new \coding_exception("Expecting instance of source to be a '{$cls}'");
        }

        switch ($field) {
            case 'name':
                return $source->get_extension_name();

            case 'tuicomponent':
                return $source->get_js_path();

            case 'options':
                $options = $source->get_js_parameters();
                if (empty($options)) {
                    return null;
                }

                $json = json_encode($options);
                if (JSON_ERROR_NONE !== json_last_error()) {
                    $msg = json_last_error_msg();
                    throw new \coding_exception("Cannot encode the json content due to: {$msg}");
                }

                return $json;

            default:
                debugging("No field '{$field}' found for type extension", DEBUG_DEVELOPER);
                return null;
        }
    }
}