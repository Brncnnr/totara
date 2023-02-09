<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @package hierarchy_position
 */

// Endpoints for the picker
$functions = [
    // List position frameworks
    'hierarchy_position_framework_index' => [
        'classname'     => \hierarchy_position\services\position_framework::class,
        'methodname'    => 'index',
        'description'   => 'List or search for position frameworks',
        'type'          => 'read',
        'loginrequired' => true,
        'ajax'          => true,
        'capabilities'  => 'totara/hierarchy:viewpositionframeworks',
    ],

    // List positions
    'hierarchy_position_index' => [
        'classname'     => \hierarchy_position\services\position::class,
        'methodname'    => 'index',
        'description'   => 'List or search for positions',
        'type'          => 'read',
        'loginrequired' => true,
        'ajax'          => true,
        'capabilities'  => 'totara/hierarchy:viewposition',
    ],

    // Query position
    'hierarchy_position_show' => [
        'classname'     => \hierarchy_position\services\position::class,
        'methodname'    => 'show',
        'description'   => 'Display a single position',
        'type'          => 'read',
        'loginrequired' => true,
        'ajax'          => true,
        'capabilities'  => 'totara/hierarchy:viewposition',
    ],
];