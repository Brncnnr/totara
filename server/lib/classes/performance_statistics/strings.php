<?php
/**
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
 * @package core
 */

namespace core\performance_statistics;

use core\session\manager;
use stdClass;

class strings extends provider {

    /**
     * @inheritDoc
     */
    public function get_data() {
        global $CFG;
        // The string manager is not fully initialised
        if (!empty($CFG->early_install_lang)) {
            return null;
        }

        // String Manager performance summary
        $string_manager = get_string_manager();
        if (method_exists($string_manager, 'get_performance_summary')) {
            [$filterinfo, $nice_names] = $string_manager->get_performance_summary();
            $data = new stdClass();
            $data->data = $filterinfo;
            $data->names = $nice_names;
        }

        return $data ?? null;
    }

}