<?php

/*
 * This file is part of Totara LMS
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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface\output;

defined('MOODLE_INTERNAL') || die();

require_once('builder/seminarevent_filterbar_builder.php');

use core\output\template;
use mod_facetoface\output\builder\seminarevent_filterbar_builder;

/**
 * A filter bar that comprises a set of filters for seminar event list
 */
class seminarevent_filterbar extends template {
    /**
     * Create a new builder object.
     *
     * @param string $id     part of form id
     * @param string $method get or post
     *
     * @return seminarevent_filterbar_builder
     */
    public static function builder(string $id, string $method = 'get'): seminarevent_filterbar_builder {
        return new seminarevent_filterbar_builder($id, $method);
    }
}