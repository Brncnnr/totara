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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralearning.com>
 * @package totara_mobile
 */

namespace totara_mobile\webapi\resolver\query;

use core\webapi\execution_context;
use totara_mobile\language\compiler;
use totara_mobile\language\source;

class language_strings extends \core\webapi\query_resolver {
    public static function resolve(array $args, execution_context $ec) {
        global $USER;

        require_capability('totara/mobile:use', \context_user::instance($USER->id));

        $lang = $args['lang'];
        $source = source::instance('totara_mobile', $lang)->filter_prefix('app:');
        return compiler::instance($source)->get_json();
    }
}