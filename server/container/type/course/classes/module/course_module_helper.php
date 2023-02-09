<?php
/*
 * This file is part of Totara LMS
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
 * @package container_course
 */
namespace container_course\module;

use container_course\course;
use core_availability\tree;
use core_container\module\helper;

final class course_module_helper {
    /**
     * Preparing default data for $moduleinfo that is going to be added into the container.
     *
     * @param \stdClass $moduleinfo
     * @return \stdClass
     */
    public static function set_moduleinfo_defaults(\stdClass $moduleinfo): \stdClass {
        $moduleinfo = clone $moduleinfo;

        if (empty($moduleinfo->coursemodule)) {
            $moduleinfo->instance = '';
            $moduleinfo->coursemodule = '';
        } else {
            // Update.
            $cm = get_coursemodule_from_id('', $moduleinfo->coursemodule, 0, false, MUST_EXIST);
            $moduleinfo->instance = $cm->instance;
            $moduleinfo->coursemodule = $cm->id;
        }

        // For safety.
        $moduleinfo->modulename = clean_param($moduleinfo->modulename, PARAM_PLUGIN);

        if (!isset($moduleinfo->groupingid)) {
            $moduleinfo->groupingid = 0;
        }

        if (!isset($moduleinfo->name)) {
            // Label.
            $moduleinfo->name = $moduleinfo->modulename;
        }

        if (!isset($moduleinfo->completion)) {
            $moduleinfo->completion = COMPLETION_DISABLED;
        }

        if (!isset($moduleinfo->completionview)) {
            $moduleinfo->completionview = COMPLETION_VIEW_NOT_REQUIRED;
        }

        if (!isset($moduleinfo->completionexpected)) {
            $moduleinfo->completionexpected = 0;
        }

        // Convert the 'use grade' checkbox into a grade-item number: 0 if checked, null if not.
        // TOTARA CHANGE: to allow restricted access on completion+passgrade requirements.
        $completionusegrade = isset($moduleinfo->completionusegrade) && $moduleinfo->completionusegrade;
        $completionpass = isset($moduleinfo->completionpass) && $moduleinfo->completionpass;

        if ($completionusegrade || $completionpass) {
            $moduleinfo->completiongradeitemnumber = 0;
        } else {
            $moduleinfo->completiongradeitemnumber = null;
        }

        if (!isset($moduleinfo->conditiongradegroup)) {
            $moduleinfo->conditiongradegroup = [];
        }

        if (!isset($moduleinfo->conditionfieldgroup)) {
            $moduleinfo->conditionfieldgroup = [];
        }

        return $moduleinfo;
    }

    /**
     * Prepare default data of the record for table {course_module}.
     *
     * @param \stdClass $moduleinfo
     * @param course    $course
     *
     * @return \stdClass
     */
    public static function prepare_new_cm(\stdClass $moduleinfo, course $course): \stdClass {
        global $CFG;
        $newcm = helper::prepare_new_cm($moduleinfo, $course);

        $record = $course->to_record();
        $completion = new \completion_info($record);

        if ($completion->is_enabled()) {
            $newcm->completion = $moduleinfo->completion;
            $newcm->completiongradeitemnumber = $moduleinfo->completiongradeitemnumber;
            $newcm->completionview = $moduleinfo->completionview;
            $newcm->completionexpected = $moduleinfo->completionexpected;
        }

        if (!empty($CFG->enableavailability)) {
            // This code is used both when submitting the form, which uses a long
            // name to avoid clashes, and by unit test code which uses the real
            // name in the table.
            $newcm->availability = null;
            if (property_exists($moduleinfo, 'availabilityconditionsjson')) {
                if ($moduleinfo->availabilityconditionsjson !== '') {
                    $newcm->availability = $moduleinfo->availabilityconditionsjson;
                }
            } else if (property_exists($moduleinfo, 'availability')) {
                $newcm->availability = $moduleinfo->availability;
            }
            // If there is any availability data, verify it.
            if ($newcm->availability) {
                $tree = new tree(json_decode($newcm->availability));
                // Save time and database space by setting null if the only data
                // is an empty tree.
                if ($tree->is_empty()) {
                    $newcm->availability = null;
                }
            }
        }

        return $newcm;
    }
}