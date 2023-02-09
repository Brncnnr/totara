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
 * @author David Curry <david.curry@totaralearning.com>
 * @package totara_core
 * @category user_learning
 */

namespace totara_core\user_learning;

use core\orm\entity\entity;
use stdClass;
use totara_core\data_provider\provider;

/**
 * User learning item interface.
 *
 * @package totara_core
 * @category user_learning
 */
class item_helper {

    /**
     * retrieve all the learning items relevant to a specified user
     *
     * @param int $userid
     * @return \totara_core\user_learning\item_base[]
     */
    public static function get_users_current_learning_items($userid) {
        /** @var \totara_core\user_learning\item_base[] $classes */
        $classes = \core_component::get_namespace_classes('user_learning', 'totara_core\user_learning\item_base');
        /** @var \totara_core\user_learning\item_base[] $items */
        $items = [];
        foreach ($classes as $class) {
            // First up we only want primary user learning items.
            if (!$class::is_a_primary_user_learning_class()) {
                continue;
            }

            /** @var \totara_core\user_learning\item_base[] $classitems */
            $classitems = $class::current($userid);
            $items = array_merge($items, array_values($classitems));
        }

        return $items;
    }

    /**
     * Expands any item specific user learning item data as required
     *
     * @param \totara_core\user_learning\item_base[] $items
     * @return \totara_core\user_learning\item_base[]
     */
    public static function expand_learning_item_specialisations(array $items) {
        foreach ($items as $item) {
            if ($item instanceof \totara_plan\user_learning\item) {
                $courses = $item->get_courses();
                $programs = $item->get_programs();
                $items = array_merge($items, array_values($courses), array_values($programs));
            }
        }
        return $items;
    }

    /**
     * Check if totara_program is the only course enrollment for the user
     *
     * @param int $userid - the id of the user associated with the learning item
     * @param \core_course\user_learning\item $item
     * @return bool
     */
    public static function only_prog_enrol($userid, \core_course\user_learning\item $item) {
        $enrol = core_enrol_get_all_user_enrolments_in_course($userid, $item->id);

        return (count($enrol) === 1 && current($enrol)->enrol === 'totara_program');
    }

    /**
     * Filters the collective user learning items altering the structure as necessary.
     *
     * @param int $userid - The user associated with the learning items
     * @param \totara_core\user_learning\item_base[] $items
     * @param bool $ignore_completed_prog_cert_courses if true is passed we don't filter out completed courses within progs and certs
     * @return \totara_core\user_learning\item_base[]
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function filter_collective_learning_items(
        $userid,
        array $items,
        bool $ignore_completed_prog_cert_courses = false
    ) {
        global $DB, $CFG;

        if (empty($items)) {
            return [];
        }

        // First up we need to remove any courses from the top level that are within a program or certification that
        // is not complete or unavailable.
        $progcertcourses = [];
        foreach ($items as $item) {
            if ($item instanceof \totara_program\user_learning\item || $item instanceof \totara_certification\user_learning\item) {
                $courses = $item->get_courseset_courses(false);
                foreach ($courses as $course) {
                    $progcertcourses[$course->id] = $course;
                }
            }
        }

        // Ensure the list of user learning items is unique.
        $items = self::ensure_distinct_learning_items($items);

        $counts = [];
        if (!empty($CFG->gradebookroles)) {
            // Gets all course where a user has an active enrolment and is assigned a gradeable role.
            // There is a little gotcha here - we are only looking at roles assigned via an enrolment
            // and not roles that have been assigned manually within the course.
            $gradebookroles = explode(",", $CFG->gradebookroles);
            $userscourses = enrol_get_all_users_courses($userid, true);
            if (!empty($userscourses)) {
                list($gradebookrolesinsql, $gradebookrolesinparams) = $DB->get_in_or_equal($gradebookroles, SQL_PARAMS_NAMED);
                list($courseinsql, $courseinparams) = $DB->get_in_or_equal(array_keys($userscourses), SQL_PARAMS_NAMED);
                $sql = "SELECT c.instanceid AS courseid, COUNT(ra.id) AS gradeablecount
                        FROM {role_assignments} ra
                        JOIN {context} c ON c.id = ra.contextid
                        WHERE c.instanceid {$courseinsql}
                        AND c.contextlevel = :coursecontext
                        AND ra.roleid {$gradebookrolesinsql}
                        AND ra.userid = :userid
                    GROUP BY c.instanceid";
                $params = array_merge($gradebookrolesinparams, $courseinparams, ['userid' => $userid, 'coursecontext' => CONTEXT_COURSE]);
                $counts = $DB->get_records_sql_menu($sql, $params);
            }
        }

        // Now make the necessary manipulations
        foreach ($items as $key => $item) {
            if ($item instanceof \core_course\user_learning\item) {
                // Remove courses that are part of progs or certifications.
                if (array_key_exists($item->id, $progcertcourses)) {
                    unset($items[$key]);
                    continue;
                }

                if (empty($counts[$item->id]) && (!$item->has_owner() || !($item->get_owner() instanceof \totara_plan\user_learning\item))) {
                    // The user does not hold a gradeable role and this course is not part of a plan.
                    unset($items[$key]);
                    continue;
                }
            }

            // Remove completed courseset courses.
            if ($ignore_completed_prog_cert_courses === false && method_exists($item, 'remove_completed_courses')) {
                $item->remove_completed_courses();
            }

            // Remove progs/certs that have no coursesets.
            if (method_exists($item, 'get_coursesets')) {
                if (empty($item->get_coursesets())) {
                    unset($items[$key]);
                }
            }
        }

        return $items;
    }

    /**
     *
     * @param \totara_core\user_learning\item_base[] $items
     * @return \totara_core\user_learning\item_base[]
     */
    public static function ensure_distinct_learning_items(array $items) {
        // First iterate over the items and ensure no item appears twice.
        $instances = [];
        foreach ($items as $key => $item) {
            $component = $item->get_component();
            $type = $item->get_type();

            if (!isset($instances[$component][$type][$item->id])) {
                $instances[$component][$type][$item->id] = $key;
            } else {
                // There are two and they are not the same :(
                $oldisprimary = $items[$instances[$component][$type][$item->id]]->is_primary_user_learning_item();
                $newisprimary = $item->is_primary_user_learning_item();

                // Special case for plan courses (as they are a secondary item that we want to show instead of a primary item).
                // This is so that the due date for the plan course is shown when available.
                if ($item instanceof \totara_plan\user_learning\course ||
                    $items[$instances[$component][$type][$item->id]] instanceof \totara_plan\user_learning\course) {
                    // If the item is a plan course then use it.
                    if ($item instanceof \totara_plan\user_learning\course) {
                        unset($items[$instances[$component][$type][$item->id]]);
                        $instances[$component][$type][$item->id] = $key;
                    } else {
                        unset($items[$key]);
                    }
                } else {
                    if ($oldisprimary && $newisprimary) {
                        // We should never ever be here!
                        debugging('Two primary user learning instance with matching identifiers found - this should never happen.', DEBUG_DEVELOPER);
                        // Unset this one just so we can progress.
                        unset($items[$key]);
                    } else if ($newisprimary) {
                        // The new item is primary and the old is not, unset the old.
                        unset($items[$instances[$component][$type][$item->id]]);
                        $instances[$component][$type][$item->id] = $key;
                    } else {
                        // The old is primary and the new is not, unset the new.
                        unset($items[$key]);
                    }
                }
            }
        }
        return $items;
    }

    /**
     * @param string $type
     * @param entity $entity
     *
     * @return item_base|null
     */
    public static function create(string $type, int $user_id, entity $entity): ?item_base {
        return self::create_from_record($type, $user_id, $entity->to_record());
    }

    /**
     * @param string $type
     * @param int $user_id
     * @param stdClass $record
     *
     * @return item_base|null
     */
    public static function create_from_record(string $type, int $user_id, stdClass $record): ?item_base {
        $classes = \core_component::get_namespace_classes(
            'user_learning',
            'totara_core\user_learning\item_base'
        );

        foreach ($classes as $class) {
            if (strpos($class, "$type\\user_learning\\item")) {
                /** @var item_base $class */
                return $class::create($user_id, $record);
            }
        }

        return null;
    }

    /**
     * @param string $type
     * @return provider|null
     */
    public static function get_data_provider(string $type): ?provider {
        $classes = \core_component::get_namespace_classes(
            'user_learning',
            'totara_core\user_learning\item_base'
        );

        foreach ($classes as $class) {
            if (strpos($class, "$type\\user_learning\\item") !== false) {
                /** @var item_base $class */
                return $class::get_data_provider();
            }
        }

        return null;
    }

}