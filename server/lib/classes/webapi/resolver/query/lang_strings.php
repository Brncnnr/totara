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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package core
 */

namespace core\webapi\resolver\query;

use core\webapi\execution_context;
use core\webapi\query_resolver;

class lang_strings extends query_resolver {

    public static function resolve(array $args, execution_context $ec) {
        $lang = $args['lang'];
        $sm = get_string_manager();
        $result = array();

        foreach ($args['ids'] as $id) {
            list($identifier, $component) = explode(',', $id);
            $identifier = clean_param(trim($identifier), PARAM_STRINGID);
            $component = clean_param(trim($component), PARAM_COMPONENT);

            $string = $sm->get_string($identifier, $component, null, $lang);
            $result[] = ['lang' => $lang, 'identifier' => $identifier, 'component' => $component, 'string' => $string];
        }

        return $result;
    }

}