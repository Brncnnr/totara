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
 * @package core_container
 */
namespace core_container\module;

use core_container\container;

final class helper {
    /**
     * @param string $modname
     * @return void
     */
    public static function include_modulelib(string $modname): void {
        global $CFG;
        $libfile = "{$CFG->dirroot}/mod/{$modname}/lib.php";

        if (!file_exists($libfile)) {
            throw new \moodle_exception('modulemissingcode', '', '', $libfile);
        }

        include_once($libfile);
    }

    /**
     * @param \stdClass $moduleinfo
     * @param container $container
     *
     * @return \stdClass
     */
    public static function prepare_new_cm(\stdClass $moduleinfo, container $container): \stdClass {
        global $DB;

        $newcm = new \stdClass();
        $newcm->course = $container->id;

        if (!isset($moduleinfo->module)) {
            if (!property_exists($moduleinfo, 'modulename')) {
                throw new \coding_exception("Cannot prepare default module data, if modulename is not provided");
            }

            // Query module id if needed.
            $moduleid = $DB->get_field('modules', 'id', ['name' => $moduleinfo->modulename], MUST_EXIST);
            $newcm->module = $moduleid;
        } else {
            $newcm->module = $moduleinfo->module;
        }

        // Not known yet, will be updated later (this is similar to restore code).
        $newcm->instance = 0;

        if (property_exists($moduleinfo, 'visible')) {
            $newcm->visible = $moduleinfo->visible;
            $newcm->visibleold = $moduleinfo->visible;
        } else {
            debugging("There is no 'visible' property set for parameter \$moduleinfo", DEBUG_DEVELOPER);

            // Set default for visible and visible old, which is 1
            $newcm->visible = 1;
            $newcm->visbleold = 1;
        }

        if (isset($moduleinfo->visibleoncoursepage)) {
            $newcm->visibleoncoursepage = $moduleinfo->visibleoncoursepage;
        }
        
        if (isset($moduleinfo->cmidnumber)) {
            $newcm->idnumber = $moduleinfo->cmidnumber;
        }

        $newcm->groupmode = $moduleinfo->groupmode;
        $newcm->groupingid = $moduleinfo->groupingid;

        if (isset($moduleinfo->showdescription)) {
            $newcm->showdescription = $moduleinfo->showdescription;
        } else {
            $newcm->showdescription = 0;
        }

        return $newcm;
    }
}