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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package performelement_long_text
 */

defined('MOODLE_INTERNAL') || die();

use editor_weka\hook\find_context;
use mod_perform\hook\post_element_response_submission;
use performelement_long_text\watcher\editor_weka_watcher;
use performelement_long_text\watcher\post_response_submission;

$watchers = [
    [
        'hookname' => find_context::class,
        'callback' => [editor_weka_watcher::class, 'load_context']
    ],
    [
        'hookname' => post_element_response_submission::class,
        'callback' => [post_response_submission::class, 'process_response']
    ],
];
