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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package totara_contentmarketplace
 */

namespace totara_contentmarketplace\observer;

use core\event\base;
use core\event\course_module_deleted;
use totara_contentmarketplace\entity\course_module_source as course_module_source_entity;

final class course_module_source {

    /**
     * Deletes all corresponding course module source records when a course module is deleted.
     *
     * @param base|course_module_deleted $event
     */
    public static function course_module_deleted(base $event): void {
        $repository = course_module_source_entity::repository()->where('cm_id', $event->objectid);
        if ($repository->exists()) {
            $repository->delete();
        }
    }
}
