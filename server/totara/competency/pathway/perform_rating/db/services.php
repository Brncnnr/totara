<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @package pathway_perform_rating
 */

$functions = [
    'pathway_perform_rating_create' => [
        'classname' => \pathway_perform_rating\external::class,
        'methodname' => 'create',
        'description' => "Create a new performance activity rating pathway",
        'type' => 'post',
        'loginrequired' => true,
        'ajax' => true,
    ],

    'pathway_perform_rating_update' => [
        'classname' => \pathway_perform_rating\external::class,
        'methodname' => 'update',
        'description' => "Update the performance activity rating pathway detail",
        'type' => 'post',
        'loginrequired' => true,
        'ajax' => true,
    ],
];
