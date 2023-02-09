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
 * @package hierarchy_organisation
 */

// Endpoints for the picker
$functions = [
    // List organisation frameworks
    'hierarchy_organisation_framework_index' => [
        'classname'     => \hierarchy_organisation\services\organisation_framework::class,
        'methodname'    => 'index',
        'description'   => 'List or search for organisation frameworks',
        'type'          => 'read',
        'loginrequired' => true,
        'ajax'          => true,
        'capabilities'  => 'totara/hierarchy:vieworganisationframeworks',
    ],

    // List organisations
    'hierarchy_organisation_index' => [
        'classname'     => \hierarchy_organisation\services\organisation::class,
        'methodname'    => 'index',
        'description'   => 'List or search for organisations',
        'type'          => 'read',
        'loginrequired' => true,
        'ajax'          => true,
        'capabilities'  => 'totara/hierarchy:vieworganisation',
    ],

    // Query organisation
    'hierarchy_organisation_show' => [
        'classname'     => \hierarchy_organisation\services\organisation::class,
        'methodname'    => 'show',
        'description'   => 'Display a single organisation',
        'type'          => 'read',
        'loginrequired' => true,
        'ajax'          => true,
        'capabilities'  => 'totara/hierarchy:vieworganisation',
    ],
];