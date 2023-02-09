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
 * @package totara_mobile
 */

namespace totara_mobile\webapi\resolver\mutation;

use core\webapi\execution_context;

class create_webview extends \core\webapi\mutation_resolver {
    public static function resolve(array $args, execution_context $ec) {
        global $USER;

        require_capability('totara/mobile:use', \context_user::instance($USER->id));

        if (!($ec instanceof \totara_mobile\webapi\execution_context)) {
            throw new \coding_exception('invalid webview request');
        }

        $deviceid = $ec->get_device_id();
        if (!$deviceid) {
            throw new \coding_exception('invalid webview request');
        }

        $url = $args['url'];
        $url = clean_param($url, PARAM_URL);
        if (!$url) {
            throw new \invalid_parameter_exception('url required');
        }


        return \totara_mobile\local\device::create_webview($deviceid, $url);
    }
}
