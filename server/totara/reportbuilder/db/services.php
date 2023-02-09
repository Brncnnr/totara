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

$functions = [

    'totara_reportbuilder_set_default_search' => [
        'classname' => '\totara_reportbuilder\external',
        'methodname' => 'set_default_search',
        'classpath' => 'totara/reportbuilder/classses/external.php',
        'description' => 'Allows the user to set their default saved search',
        'ajax' => true,
        'type' => 'write',
    ],
];

$services = [
    // None by default.
];
