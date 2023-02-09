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
namespace totara_contentmarketplace\observer;

use coding_exception;
use container_course\course_helper;
use core\entity\course;
use totara_contentmarketplace\course\course_image_downloader;
use totara_contentmarketplace\entity\course_module_source;
use totara_contentmarketplace\event\base_learning_object_updated;

final class learning_object_observer {
    /**
     * learning_object_observer constructor.
     */
    private function __construct() {
    }

    /**
     * @param base_learning_object_updated $event
     */
    public static function on_learning_object_updated(base_learning_object_updated $event): void {
        $other = $event->other;

        /** @var course_module_source[] $course_module_sources */
        $course_module_sources = course_module_source::repository()
            ->with('course')
            ->filter_by_id_and_component($event->objectid, $other["marketplace_component"])
            ->get();

        foreach ($course_module_sources as $course_module_source) {
            $course = $course_module_source->course;
            if (is_null($course)) {
                debugging("'{$other['name']}' activity module has already been removed.");
                continue;
            }
            $new_course = self::get_updated_course_record($event, $course);

            $new_image = $event->get_new_image();
            $new_image = new course_image_downloader($course->id, $new_image);
            $old_image = $event->get_old_image();
            if (isset($old_image)) {
                $old_image = new course_image_downloader($course->id, $old_image);
                $old_image->compare_and_update($new_image);
            } else {
                $new_image->download_image_for_course();
            }

            if (empty($new_course)) {
                // Skip updating this course.
                continue;
            }

            // Update the course accordingly.
            course_helper::update_course($course->id, (object) $new_course);
        }
    }

    /**
     * @param base_learning_object_updated $event
     * @param course                       $course
     *
     * @return array
     */
    private static function  get_updated_course_record(base_learning_object_updated $event, course $course): array {
        $map = [
            "description" => "summary",
            "name" => "fullname"
        ];

        $new_course = [];
        $other = $event->other;

        foreach ($map as $key => $value) {
            if (!$event->validate_data_key($key)) {
                throw new coding_exception("The key {$key} of event data is incorrect");
            }

            if (isset($other[$key]) && $course->get_attribute($value) !== $other[$key]) {
                $extra_data_key = $event->get_extra_key($key);
                if (!$event->validate_data_key($extra_data_key)) {
                    throw new coding_exception("The key {$extra_data_key} of event other data is incorrect");
                }

                $old_value = $other[$extra_data_key];
                if (isset($old_value) && $course->get_attribute($value) === $old_value) {
                    $new_course[$value] = $other[$key];
                }
            }
        }

        return $new_course;
    }
}