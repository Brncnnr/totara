<?php
/*
 * This file is part of Totara Learn
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
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package totara_criteria
 */

namespace totara_competency;

use core\orm\query\builder;
use hierarchy_competency\event\evidence_deleted;
use stdClass;
use totara_competency\event\linked_courses_updated;

/**
 * Class linked_courses
 *
 * Helper methods relating to linked courses for competencies.
 */
class linked_courses {

    public const LINKTYPE_OPTIONAL = PLAN_LINKTYPE_OPTIONAL;
    public const LINKTYPE_MANDATORY = PLAN_LINKTYPE_MANDATORY;

    /**
     * Returns array of course records. Each record also includes the linktype,
     * i.e. PLAN_LINKTYPE_OPTIONAL or PLAN_LINKTYPE_MANDATORY.
     *
     * Permissions checks, e.g. ensuring the user can see each course, must be done externally.
     *
     * @param int $competency_id
     * @return stdClass[] keyed by course id.
     */
    public static function get_linked_courses($competency_id) {
        global $DB;

        $linked_courses_records = $DB->get_records_sql(
            'SELECT course.id, course.fullname, cc.linktype
                   FROM {course} course
                   JOIN {comp_criteria} cc
                     ON cc.itemtype = ?
                    AND course.id = cc.iteminstance
                  WHERE cc.competencyid = ?
                  ORDER BY course.id',
            ['coursecompletion', $competency_id]
        );

        return $linked_courses_records;
    }

    /**
     * Returns array of course ids.
     *
     * @param int $competency_id
     * @return int[] keyed by course id.
     */
    public static function get_linked_course_ids($competency_id): array {
        global $DB;

        $linked_course_ids = $DB->get_fieldset_select('comp_criteria',
            'iteminstance',
            'competencyid = :competencyid AND itemtype = :itemtype',
            ['competencyid' => $competency_id, 'itemtype' => 'coursecompletion']
        );

        return $linked_course_ids;
    }

    /**
     * Allows setting of which courses are linked to a given competency.
     *
     * Overwrites previous linked courses for the given competency.
     * Permissions checks, e.g. ensuring the user can see each course, must be done externally.
     *
     * @param int $competency_id
     * @param array $courses Each array element is an array that must contain 'id' and 'linktype'.
     *   'linktype' would be either PLAN_LINKTYPE_OPTIONAL or PLAN_LINKTYPE_MANDATORY.
     */
    public static function set_linked_courses(int $competency_id, array $courses) {
        global $DB, $USER, $CFG;
        require_once($CFG->dirroot . '/totara/plan/lib.php');

        $actions = static::check_items($competency_id, $courses);
        if (empty($actions['to_add']) && empty($actions['to_update']) && empty($actions['to_delete'])) {
            return;
        }

        if (!empty($actions['to_add'])) {
            $DB->insert_records('comp_criteria', $actions['to_add']);
        }

        if (!empty($actions['to_update'])) {
            foreach ($actions['to_update'] as $update_record) {
                $DB->update_record('comp_criteria', $update_record);
            }
        }

        if (!empty($actions['to_delete'])) {
            $delete_ids = array_map(
                function ($record) {
                    return $record->id;
                },
                $actions['to_delete']
            );

            $DB->delete_records_list('comp_criteria', 'id', $delete_ids);

            foreach ($actions['to_delete'] as $record) {
                // This event is triggered for 3rd party backwards compatibility with the hierarchy plugin
                evidence_deleted::create_from_instance($record)->trigger();
            }
        }

        static::update_competency_linked_course_count($competency_id, count($courses));

        $event = linked_courses_updated::create_from_competency($competency_id);
        $event->trigger();
    }

    /**
     * Remove a single linked course from all competencies, update course count and trigger events.
     *
     * @param int $course_id
     */
    public static function remove_course(int $course_id): void {
        global $DB;

        $competency_criteria = static::get_competency_criteria_by_course($course_id);

        $delete_ids = array_keys($competency_criteria);

        $DB->delete_records_list('comp_criteria', 'id', $delete_ids);

        foreach ($competency_criteria as $competency_criterion) {
            static::update_competency_linked_course_count($competency_criterion->competencyid);

            linked_courses_updated::create_from_competency($competency_criterion->competencyid)->trigger();

            // This event is triggered for 3rd party backwards compatibility with the hierarchy plugin
            evidence_deleted::create_from_instance($competency_criterion)->trigger();
        }
    }

    /**
     * Update the link type of a specific linked course
     * @param int $criteria_id
     * @param int $new_linktype
     * @return bool Success/Fail
     */
    public static function update_linktype(int $criteria_id, int $new_linktype): bool {
        global $DB;

        $competency_criteria = $DB->get_record('comp_criteria', ['id' => $criteria_id]);
        if (!$competency_criteria) {
            return false;
        }

        if (!self::linktype_is_valid($new_linktype)) {
            throw new \coding_exception('Invalid linktype');
        }

        if ($competency_criteria->linktype == $new_linktype) {
            return true;
        }

        $competency_criteria->linktype = $new_linktype;
        if ($DB->update_record('comp_criteria', $competency_criteria)) {
            linked_courses_updated::create_from_competency($competency_criteria->competencyid)->trigger();
            return true;
        }

        return false;
    }

    /**
     * Link one or more courses to a competency if not yet linked
     * @param int $competency_id
     * @param array $courses Each array element is an array that must contain 'id' and 'linktype'.
     *   'linktype' would be either PLAN_LINKTYPE_OPTIONAL or PLAN_LINKTYPE_MANDATORY.
     */
    public static function add_linked_courses(int $competency_id, array $courses) {
        global $DB;

        $actions = static::check_items($competency_id, $courses);
        if (empty($actions['to_add']) && empty($actions['to_update'])) {
            return;
        }

        if (!empty($actions['to_add'])) {
            $DB->insert_records('comp_criteria', $actions['to_add']);
        }

        if (!empty($actions['to_update'])) {
            foreach ($actions['to_update'] as $update_record) {
                $DB->update_record('comp_criteria', $update_record);
            }
        }

        static::update_competency_linked_course_count($competency_id, count($courses));

        $event = linked_courses_updated::create_from_competency($competency_id);
        $event->trigger();
    }

    /**
     * Remove the specific linked course item
     * @param int $criteria_id
     * @return bool Success/Fail
     */
    public static function remove_linked_course(int $criteria_id): bool {
        global $DB;

        $competency_criterion = $DB->get_record('comp_criteria', ['id' => $criteria_id]);
        if (!$competency_criterion) {
            return false;
        }

        $DB->delete_records('comp_criteria', ['id' => $criteria_id]);
        static::update_competency_linked_course_count($competency_criterion->competencyid);

        linked_courses_updated::create_from_competency($competency_criterion->competencyid)->trigger();

        // This event is triggered for 3rd party backwards compatibility with the hierarchy plugin
        evidence_deleted::create_from_instance($competency_criterion)->trigger();

        return true;
    }

    /**
     * Update the pre-calculated "Linked courses" count field ("evidencecount" in the database).
     *
     * @param int $competency_id
     * @param int $course_count If not supplied it will be calculated from the database
     */
    private static function update_competency_linked_course_count(int $competency_id, int $course_count = null): void {
        global $DB;

        if ($course_count === null) {
            $course_count = static::get_linked_course_count($competency_id);
        }

        $DB->update_record('comp', ['id' => $competency_id, 'evidencecount' => $course_count]);
    }

    /**
     * Calculates the linked course count (rather than simply getting the pre-calculated field)
     *
     * @param int $competency_id
     * @return int
     */
    private static function get_linked_course_count(int $competency_id): int {
        global $DB;

        return $DB->count_records('comp_criteria', ['itemtype' => 'coursecompletion', 'competencyid' => $competency_id]);
    }

    /**
     * Get all comp_criteria rows linked to a particular course
     *
     * @param int $course_id
     * @return array
     */
    private static function get_competency_criteria_by_course(int $course_id): array {
        global $DB;

        return $DB->get_records('comp_criteria', ['itemtype' => 'coursecompletion', 'iteminstance' => $course_id]);
    }

    private static function linktype_is_valid(int $linktype): bool {
        return in_array($linktype, [static::LINKTYPE_OPTIONAL, static::LINKTYPE_MANDATORY]);
    }

    /**
     * Determine which courses to add, update and delete from
     *
     * @param int $competency_id
     * @param array $courses Each array element is an array that must contain 'id' and 'linktype'.
     *   'linktype' would be either PLAN_LINKTYPE_OPTIONAL or PLAN_LINKTYPE_MANDATORY.
     * @return array
     *          'to_add' and 'to_update' => the insert / update comp_criteria records,
     *          'to_delete' => comp_criteria records that exists but are not in the list
     */
    private static function check_items(int $competency_id, array $courses) : array {
        global $USER;

        // Have set iteminstance as the first field so that the results are keyed by the course id.
        $linked_courses_records = builder::table('comp_criteria')
            ->where('itemtype', 'coursecompletion')
            ->where('competencyid', $competency_id)
            ->get()
            ->key_by('iteminstance')
            ->all(true);

        $to_update = [];
        $to_add = [];

        foreach ($courses as $course) {
            // Validate linktype.
            if (!isset($course['linktype'])) {
                if (!isset($course['mandatory'])) {
                    throw new \coding_exception('Linktype or mandatory is required');
                }

                $course['linktype'] = $course['mandatory'] ? static::LINKTYPE_MANDATORY : static::LINKTYPE_OPTIONAL;
            } else if (!static::linktype_is_valid($course['linktype'])) {
                throw new \coding_exception('Invalid linktype');
            }

            if (isset($to_add[$course['id']]) || isset($to_update[$course['id']])) {
                throw new \coding_exception('Duplicate courses');
            }

            if (isset($linked_courses_records[$course['id']])) {
                $linked_courses_record = $linked_courses_records[$course['id']];

                // Check if linktype is same and update if not.
                if ($linked_courses_record->linktype != $course['linktype']) {
                    $linked_courses_record->linktype = $course['linktype'];
                    $linked_courses_record->timemodified = time();
                    $linked_courses_record->usermodified = $USER->id;
                    $to_update[$course['id']] = $linked_courses_record;
                }
            } else {
                $comp_criteria = new stdClass();
                $comp_criteria->competencyid = $competency_id;
                $comp_criteria->itemtype = 'coursecompletion';
                $comp_criteria->iteminstance = $course['id'];
                $comp_criteria->timecreated = time();
                $comp_criteria->timemodified = time();
                $comp_criteria->usermodified = $USER->id;
                $comp_criteria->linktype = $course['linktype'];
                $to_add[$course['id']] = $comp_criteria;
            }

            unset($linked_courses_records[$course['id']]);
        }

        return ['to_add' => $to_add, 'to_update' => $to_update, 'to_delete' => $linked_courses_records];
    }
}
