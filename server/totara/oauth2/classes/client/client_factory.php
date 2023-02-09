<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package totara_oauth2
 */
namespace totara_oauth2\client;

use coding_exception;
use core_component;

class client_factory {
    /**
     * @return void
     */
    private function __construct() {
    }

    /**
     * @param string $component
     * @return base
     */
    public static function create_client(string $component): base {
        $classes = core_component::get_namespace_classes(
            'oauth2_client',
            base::class,
            $component
        );

        if (empty($classes)) {
            throw new coding_exception("No client provider class found for component '{$component}'");
        }

        if (count($classes) !== 1) {
            debugging("There are more than one class that are found", DEBUG_DEVELOPER);
        }

        $cls = reset($classes);
        return new $cls();
    }
}