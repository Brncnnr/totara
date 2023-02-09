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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package core_container
 */
namespace core_container;

final class cache_helper {
    /**
     * Rebuilds or resets the cached list of courses/containers activities stored in MUC.
     *
     * \core_container\cache_helper::rebuild_container_cache() must NEVER be called from lib/db/upgrade.php.
     * At the same time course cache may ONLY be cleared using this function in
     * upgrade scripts of plugins.
     *
     * During the bulk operations if it is necessary to reset cache of multiple
     * courses/containers it is enough to call {@link increment_revision_number()} for the
     * table 'course' and field 'cacherev' specifying affected courses in select.
     *
     * Cached course information is stored in MUC core/coursemodinfo and is
     * validated with the DB field {course}.cacherev
     *
     * @param int   $containerid    Id of the container to rebuild, empty means all
     * @param bool  $clearonly      Only clear the cache, gets rebuild automatically on the fly. Recommended to set
     *                              to true to avoid unnecessary multiple rebuilding.
     *
     * @global \moodle_database $DB
     */
    public static function rebuild_container_cache(int $containerid, bool $clearonly = false): void {
        global $DB, $CFG, $COURSE, $SITE;
        require_once("{$CFG->dirroot}/course/format/lib.php");


        // Cannot rebuild cache while upgrading, unless it is clear only.
        if (!$clearonly && !upgrade_ensure_not_running(true)) {
            $clearonly = true;
        }

        // Destroy navigation caches
        \navigation_cache::destroy_volatile_caches();

        if (class_exists('format_base')) {
            // if file containing class is not loaded, there is no cache there anyway
            \format_base::reset_course_cache($containerid);
        }

        /** @var \cache $cachecoursemodinfo */
        $cachecoursemodinfo = \cache::make('core', 'coursemodinfo');

        if (empty($containerid)) {
            increment_revision_number('course', 'cacherev', '');
            $cachecoursemodinfo->purge();

            \course_modinfo::clear_instance_cache();

            // Update global values too.
            $sitecacherev = $DB->get_field('course', 'cacherev', ['id' => SITEID]);
            $SITE->cacherev = $sitecacherev;

            if ($COURSE->id == SITEID) {
                $COURSE->cacherev = $sitecacherev;
            } else {
                $COURSE->cacherev = $DB->get_field('course', 'cacherev', ['id' => $COURSE->id]);
            }

            // Purge all the containers in the factory.
            factory::reset();
        } else {
            // Clearing cache for one course, make sure it is deleted from user request cache as well.
            increment_revision_number('course', 'cacherev', 'id = :id', ['id' => $containerid]);
            $cachecoursemodinfo->delete($containerid);
            \course_modinfo::clear_instance_cache($containerid);

            // Update global values too.
            if ($containerid == $COURSE->id || $containerid == $SITE->id) {
                $cacherev = $DB->get_field('course', 'cacherev', ['id' => $containerid]);
                if ($containerid == $COURSE->id) {
                    $COURSE->cacherev = $cacherev;
                }

                if ($containerid == $SITE->id) {
                    $SITE->cacherev = $cacherev;
                }
            }

            // Reset in the container factory.
            factory::reset($containerid);
        }

        if ($clearonly) {
            return;
        }

        if ($containerid) {
            $select = ['id' => $containerid];
        } else {
            $select = [];

            // this could take a while! MDL-10954
            \core_php_time_limit::raise();
        }

        $rs = $DB->get_recordset("course", $select,'','id,' . join(',', \course_modinfo::$cachedfields));

        // Rebuild cache for each course.
        foreach ($rs as $course) {
            \course_modinfo::build_course_cache($course);
        }

        $rs->close();
    }
}