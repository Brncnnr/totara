<?php
/**
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_engage
 */

defined('MOODLE_INTERNAL') || die();

$icons = [
    'totara_engage|add-to-playlist' => [
        'data' => [
            'classes' => 'tfont-var-list_plus'
        ]
    ],

    'totara_engage|public' => [
        'data' => [
            'classes' => 'tfont-var-globe'
        ]
    ],
];

$aliases = [
    'totara_engage|comment' => 'comment',
    'totara_engage|share' => 'share-link',
    'totara_engage|restricted' => 'users',
    'totara_engage|private' => 'lock',
    'totara_engage|limited' => 'users',
];
