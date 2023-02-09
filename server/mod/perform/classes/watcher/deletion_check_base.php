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
 * @author Marco Song <marco.song@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\watcher;

use coding_exception;
use core\collection;
use moodle_url;

abstract class deletion_check_base {

    /**
     * Concat section name with the activity name it belongs to
     *
     * @param collection $section_elements
     * @return array
     * @throws coding_exception
     */
    protected static function get_data(collection $section_elements): array {
        $data = [];
        foreach ($section_elements as $section_element) {
            $data[] = get_string(
                'activity_name_with_section_name_and_element_type',
                'mod_perform',
                (object) [
                    'activity_name' => $section_element->section->activity->name,
                    'section_name' => $section_element->section->display_title,
                    'element_type' => $section_element->element->get_element_plugin()->get_name(),
                ]
            );
        }

        return $data;
    }

    /**
     * Concat section name with the activity name it belongs to
     *
     * @param collection $section_elements
     * @return array
     * @throws coding_exception
     */
    protected static function get_warning_data(collection $section_elements): array {
        $items = [];
        foreach ($section_elements as $section_element) {
            $items[] = [
                'item' => get_string(
                    'activity_name_with_section_name_and_element_type',
                    'mod_perform',
                    (object) [
                        'activity_name' => $section_element->section->activity->name,
                        'section_name' => $section_element->section->display_title,
                        'element_type' => $section_element->element->get_element_plugin()->get_name(),
                    ]
                ),
                'url' => (new moodle_url('/mod/perform/manage/activity/section.php', ['section_id' => $section_element->section->id])),
            ];

        }

        return $items;
    }
}
