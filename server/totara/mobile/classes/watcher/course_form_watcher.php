<?php
/*
 * This file is part of Totara LMS
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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package totara_mobile
 */

namespace totara_mobile\watcher;

use core_course\hook\{edit_form_definition_complete, edit_form_save_changes};
use core\orm\query\builder;

defined('MOODLE_INTERNAL') || die();

/**
 * A hook watcher to subscribe to the course related edit form hook.
 *
 * This watcher will try to add mobile compatibility element to the edit_course form. When the form is submitted,
 * the course will be looked up in the totara_mobile_compatible_courses table, and added or removed as necessary.
 */
final class course_form_watcher {
    /**
     * A watcher to add mobile compatibility element into the course.
     *
     * @param edit_form_definition_complete $hook
     * @return void
     */
    public static function add_mobilecompatibility_to_course_form(edit_form_definition_complete $hook): void {
        if (!get_config('totara_mobile', 'enable')) {
            // Do not modify the form if the mobile app is disabled.
            return;
        }

        $form = $hook->form->_form;

        $choices = array();
        $choices['1'] = get_string('yes');
        $choices['0'] = get_string('no');
        $ele = $form->createElement('select', 'totara_mobile_coursecompat', get_string('coursecompatible', 'totara_mobile'), $choices);
        $form->insertElementBefore($ele, 'descriptionhdr');
        $form->addHelpButton('totara_mobile_coursecompat', 'coursecompatible', 'totara_mobile');

        // If there is a course, see if it is compatible or not...
        $default = '';
        if (isset($hook->customdata['course'])) {
            $course = $hook->customdata['course'];
            if (!empty($course->id)) {
                $default = (string) builder::table('totara_mobile_compatible_courses')
                    ->where('courseid', $course->id)
                    ->count();
            }
        }

        // If there is no default set yet, use global default.
        if ($default == '') {
            $default = get_config('totara_mobile', 'coursecompat');
            if ($default == '') {
                $default = '1';
            }
        }

        $form->setDefault('totara_mobile_coursecompat', $default);
    }

    /**
     * A watcher to process mobile compatibility setting when course form is submitted
     *
     * @param edit_form_save_changes $hook
     * @return void
     */
    public static function process_mobilecompatibility_for_course(edit_form_save_changes $hook): void {
        if (!get_config('totara_mobile', 'enable')) {
            // Do not make any changes if the mobile app is disabled.
            return;
        }

        $formdata = $hook->data;

        if (0 == $hook->courseid) {
            // We want null to be included here too.
            debugging("Unable to process mobile compatibility for course without id", DEBUG_DEVELOPER);
            return;
        }

        $iscompat = (string) builder::table('totara_mobile_compatible_courses')
            ->where('courseid', $hook->courseid)
            ->count();

        // Is setting different from current?
        if ($formdata->totara_mobile_coursecompat != $iscompat) {
            if ($formdata->totara_mobile_coursecompat == '1') {
                // Add course to compatibility table.
                builder::table('totara_mobile_compatible_courses')->insert(['courseid' => $hook->courseid]);
            } else {
                // Remove course from compatibility table.
                builder::table('totara_mobile_compatible_courses')
                    ->where('courseid', $hook->courseid)
                    ->delete();
            }
        }
    }
}
