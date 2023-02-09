<?php
/**
 * This file is part of Totara Core
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package mod_contentmarketplace
 */

namespace mod_contentmarketplace\watcher;

use coding_exception;
use core\orm\query\builder;
use mod_contentmarketplace\workflow_manager\create_marketplace_activity;
use totara_core\hook\mod_add;

class mod_add_watcher {

    /**
     * mod_add_watcher constructor.
     */
    private function __construct() {
        // Prevent instantiation.
    }

    /**
     * @param mod_add $hook
     * @return void
     */
    public static function redirect_to_workflow(mod_add $hook): void {
        global $CFG;

        $course = $hook->get_course();
        $module_name = $hook->get_module_name();
        $section_id = $hook->get_section_id();
        if (!empty($module_name) && !is_null($section_id)) {
            // Only redirect for content marketplace modules.
            if ($module_name !== 'contentmarketplace') {
                return;
            }

            // Only redirect if user is allowed to add the module.
            include_once($CFG->dirroot . '/course/modlib.php');
            \can_add_moduleinfo($course, $module_name, $section_id);

            // Get the section record ID.
            $course_section = builder::table('course_sections')
                ->where('course', $course->id)
                ->where('section', $section_id)
                ->select(['id'])
                ->one(true);

            // Redirect to workflow.
            $wm = new create_marketplace_activity();
            $wm->set_params([
                'section_id' => $course_section->id,
            ]);
            if ($wm->workflows_available()) {
                $url = $wm->get_url();
                redirect($url);
            }

            // This point should never be reached. If it is then it means that a workflow
            // is initiated for a marketplace that is not enabled which would be a bug.
            throw new coding_exception("No workflows available for request");
        }
    }

}