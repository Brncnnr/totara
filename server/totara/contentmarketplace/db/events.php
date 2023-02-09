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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package totara_contentmarketplace
 */
defined('MOODLE_INTERNAL') || die();

use core\event\course_module_deleted;
use totara_contentmarketplace\event\base_learning_object_updated;
use totara_contentmarketplace\observer\course_module_source;
use totara_contentmarketplace\observer\learning_object_observer;

$observers = [
    [
        'eventname' => base_learning_object_updated::class,
        'callback' => [learning_object_observer::class, 'on_learning_object_updated']
    ],
    [
        'eventname' => course_module_deleted::class,
        'callback' => [course_module_source::class, 'course_module_deleted'],
    ],
];