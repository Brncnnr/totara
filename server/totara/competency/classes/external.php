<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @package totara_competency
 */

namespace totara_competency;

use container_course\course as container_course;
use context_system;
use core\collection;
use core\entity\course;
use core\format;
use core\orm\query\builder;
use coursecat;
use external_api;
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;
use totara_core\advanced_feature;
use totara_hierarchy\entity\competency;
use totara_hierarchy\entity\competency_framework;
use core\webapi\formatter\field\string_field_formatter;

defined('MOODLE_INTERNAL') || die;

class external extends external_api {

    /**
     * get_pathways
     */
    public static function get_pathways_parameters() {
        return new external_function_parameters(
            [
                'competency_id' => new external_value(PARAM_INT, 'Id of the competency'),
            ]
        );
    }

    public static function get_pathways(int $competency_id) {
        advanced_feature::require('competency_assignment');
        require_capability('totara/hierarchy:viewcompetency', context_system::instance());

        $config = new achievement_configuration(new competency($competency_id));
        return $config->export_pathway_groups();
    }

    public static function get_pathways_returns() {
        return null;
    }

    public static function get_categories_parameters() {
        return new external_function_parameters([]);
    }

    public static function get_categories() {
        advanced_feature::require('competencies');
        require_capability('totara/hierarchy:updatecompetency', context_system::instance());

        global $CFG;
        require_once($CFG->libdir . '/coursecatlib.php');

        $categories = coursecat::make_categories_list();

        $result = [];
        foreach ($categories as $id => $name) {
            $result[] = [
                'id' => $id,
                'fullname' => $name
            ];
        }

        return ['items' => $result];
    }

    public static function get_categories_returns() {
        return new external_single_structure(
            [
                'items' => new external_multiple_structure(
                    new external_single_structure(
                        [
                            'id' => new external_value(PARAM_INT, 'Category id'),
                            'fullname' => new external_value(PARAM_TEXT, 'Category fullname'),
                        ]
                    )
                )
            ]
        );
    }

    /**
     * get_courses
     */
    public static function get_courses_parameters() {
        return new external_function_parameters(
            [
                'filters' => new external_single_structure(
                    [
                        'category' => new external_value(PARAM_INT, 'Filter by category id', VALUE_OPTIONAL, null),
                        'name' => new external_value(PARAM_TEXT, 'Search by username, full name ', VALUE_OPTIONAL, null),
                        'ids' => new external_multiple_structure(
                            new external_value(PARAM_INT, 'ids', VALUE_OPTIONAL),
                            'ids to filter by',
                            VALUE_OPTIONAL
                        ),
                    ]
                ),
                'page' => new external_value(PARAM_INT, 'Not used'),
                'order' => new external_value(PARAM_ALPHANUMEXT, 'Name of column to order by'),
                'direction' => new external_value(PARAM_ALPHA, 'Direction of ordering (either ASC or DESC)'),
            ]
        );
    }

    /**
     * Return list of courses
     * @param array $filters
     * @param int $page
     * @param string $order
     * @param string $direction
     * @return array
     */
    public static function get_courses(array $filters, int $page, string $order, string $direction) {
        advanced_feature::require('competencies');
        require_capability('totara/hierarchy:updatecompetency', context_system::instance());

        global $CFG;
        require_once($CFG->dirroot . '/totara/coursecatalog/lib.php');

        if (!in_array(strtolower($direction), ['asc', 'desc'])) {
            $direction = 'desc';
        }

        // Force ordering by name at this stage. Attribute reserved for uniformity and future use
        if (!empty($order) && $order != 'fullname') {
            // When implementing, ensure that order is correctly validated (e.g. against a list of allowed options).
            throw new \coding_exception('Only ordering on fullname currently implemented for totara_competency_get_courses');
        }

        $order = 'fullname';

        [$totara_visibility_sql, $totara_visibility_params] = totara_visibility_where();

        $data = course::repository()
            ->select(['id', 'shortname', 'fullname', 'visible', 'audiencevisible'])
            ->join(['context', 'ctx'], 'id', 'ctx.instanceid')
            ->filter_by_category($filters['category'] ?? null)
            ->filter_by_name($filters['name'] ?? null)
            ->filter_by_ids($filters['ids'] ?? null)
            ->where(function (builder $builder) {
                $builder->where_null('containertype')
                    ->or_where('containertype', container_course::get_type());
            })
            ->where('id', '!=', SITEID)
            ->where('ctx.contextlevel', '=', CONTEXT_COURSE)
            ->where_raw($totara_visibility_sql, $totara_visibility_params)
            ->order_by($order, $direction)
            ->paginate($page)
            ->to_array();

        $formatter = new string_field_formatter(format::FORMAT_HTML, \context_system::instance());

        $items = [];
        foreach ($data['items'] as $item) {
            $items[] = [
                'id' => $item['id'],
                'shortname' => $formatter->format($item['shortname']),
                'fullname' => $formatter->format($item['fullname'])
            ];
        }
        $data['items'] = $items;
        $data['items_per_page'] = $data['per_page'];

        return $data;
    }

    /**
     * @return null
     */
    public static function get_courses_returns() {
        return new external_single_structure(
            [
                'items' => new external_multiple_structure(
                    new external_single_structure(
                        [
                            'id' => new external_value(PARAM_INT, 'Course id'),
                            'shortname' => new external_value(PARAM_TEXT, 'Course shortname'),
                            'fullname' => new external_value(PARAM_TEXT, 'Course fullname'),
                        ]
                    )
                ),
                'page' => new external_value(PARAM_INT, 'Current page'),
                'pages' => new external_value(PARAM_INT, 'Total number of pages'),
                'items_per_page' => new external_value(PARAM_INT, 'Number of items per page'),
                'next' => new external_value(PARAM_INT, 'Next page number', VALUE_OPTIONAL),
                'prev' => new external_value(PARAM_INT, 'Previous page number', VALUE_OPTIONAL),
                'total' => new external_value(PARAM_INT, 'Total number of items'),
            ]
        );
    }

    public static function get_linked_courses_parameters() {
        return new external_function_parameters(
            [
                'competency_id' => new external_value(PARAM_INT, 'The ID of the competency'),
            ]
        );
    }

    /**
     * Get courses linked to a given competency.
     *
     * @param int $competency_id
     * @return array
     */
    public static function get_linked_courses(int $competency_id) {
        advanced_feature::require('competencies');
        require_capability('totara/hierarchy:viewcompetency', context_system::instance());

        global $CFG;
        require_once($CFG->dirroot . '/totara/plan/lib.php');

        $linked_courses_records = linked_courses::get_linked_courses($competency_id);

        $formatter = new string_field_formatter(format::FORMAT_HTML, \context_system::instance());

        $linked_courses = [];
        foreach ($linked_courses_records as $linked_courses_record) {
            $linked_courses[] = [
                'id' => $linked_courses_record->id,
                'mandatory' => ($linked_courses_record->linktype == linked_courses::LINKTYPE_MANDATORY),
                'fullname' => $formatter->format($linked_courses_record->fullname)
            ];
        }

        return ['items' => $linked_courses];
    }

    public static function get_linked_courses_returns() {
        return new external_function_parameters(
            [
                'items' => new external_multiple_structure(
                    new external_single_structure([
                        'id' => new external_value(PARAM_INT, 'Course id'),
                        'mandatory' => new external_value(PARAM_BOOL, 'Indication whether this is a mandatory course'),
                        'fullname' => new external_value(PARAM_TEXT, 'Full name of courses')
                    ])
                )
            ]
        );
    }

    public static function set_linked_courses_parameters() {
        return new external_function_parameters(
            [
                'competency_id' => new external_value(PARAM_INT, 'The ID of the competency'),
                'courses' => new external_multiple_structure(
                    new external_single_structure([
                        'id' => new external_value(PARAM_INT, 'Course id'),
                        'mandatory' => new external_value(PARAM_BOOL, 'Indication this a mandatory course'),
                    ])
                )
            ]
        );
    }

    /**
     * @param int $competency_id
     * @param array $courses Each array element contain an array with keys 'id' for the course id and
     *   'linktype' which contains either of constants, PLAN_LINKTYPE_OPTIONAL or PLAN_LINKTYPE_MANDATORY.
     */
    public static function set_linked_courses(int $competency_id, $courses) {
        advanced_feature::require('competencies');
        require_capability('totara/hierarchy:updatecompetency', context_system::instance());

        global $CFG;
        require_once($CFG->dirroot . '/totara/coursecatalog/lib.php');

        $incoming_courses = collection::new($courses);
        $incoming_course_ids = $incoming_courses->pluck('id');

        [$totara_visibility_sql, $totara_visibility_params] = totara_visibility_where();
        $visible_course_ids = course::repository()
            ->select(['id'])
            ->where('containertype', container_course::get_type())
            ->filter_by_ids($incoming_course_ids)
            ->where_raw($totara_visibility_sql, $totara_visibility_params)
            ->get()
            ->pluck('id');

        $linkable_courses = $incoming_courses->filter(
            function (array $details) use ($visible_course_ids): bool {
                return in_array($details['id'], $visible_course_ids);
            }
        )->all();

        linked_courses::set_linked_courses($competency_id, $linkable_courses);
    }

    public static function set_linked_courses_returns() {
        return null;
    }

    /** delete_pathways */
    public static function delete_pathways_parameters() {
        return new external_function_parameters(
            [
                'competency_id' => new external_value(PARAM_INT, 'Id of the competency'),
                'pathways' => new external_multiple_structure(
                    new external_single_structure([
                        'type' => new external_value(PARAM_ALPHAEXT, 'Pathway type'),
                        'id' => new external_value(PARAM_INT, 'Pathway id'),
                    ])
                ),
                'actiontime' => new external_value(PARAM_INT, 'Time user initiated the action. It is used to group changes done in single user action together'),
            ]
        );
    }

    public static function delete_pathways(string $competency_id, array $pathways, int $action_time) {
        advanced_feature::require('competency_assignment');
        require_capability('totara/hierarchy:updatecompetency', context_system::instance());

        $config = new achievement_configuration(new competency($competency_id));
        return $config->delete_pathways($pathways, $action_time);
    }

    public static function delete_pathways_returns() {
        return null;
    }


    /**
     * set_overall_aggregation
     */
    public static function set_overall_aggregation_parameters() {
        return new external_function_parameters(
            [
                'competency_id' => new external_value(PARAM_INT, 'Id of the competency'),
                'type' => new external_value(PARAM_ALPHANUMEXT, 'Aggregation type'),
                'actiontime' => new external_value(PARAM_INT, 'Time user initiated the action. It is used to group changes done in single user action together'),
            ]
        );
    }

    public static function set_overall_aggregation(int $competency_id, string $type, int $action_time): string {
        advanced_feature::require('competency_assignment');
        require_capability('totara/hierarchy:updatecompetency', context_system::instance());

        $config = new achievement_configuration(new competency($competency_id));
        $old_type = $config->get_aggregation_type();

        if ($old_type !== $type) {
            $config->set_aggregation_type($type)
                ->save_aggregation($action_time);
        }

        return $config->get_aggregation_type();
    }

    public static function set_overall_aggregation_returns() {
        return new external_value(PARAM_ALPHANUMEXT, 'Aggregation type');
    }

    public static function get_frameworks() {
        advanced_feature::require('competencies');
        require_capability('totara/hierarchy:viewcompetency', context_system::instance());

        $items = competency_framework::repository()
            ->select(['id', 'fullname'])
            ->get()
            ->map(function (competency_framework $framework) {
                return [
                    'id' => $framework->id,
                    'fullname' => format_string($framework->fullname, true, ['context' => context_system::instance()]),
                ];
            })
            ->to_array();

        return ['items' => $items];
    }

    public static function get_frameworks_parameters() {
        return new external_function_parameters([]);
    }

    public static function get_frameworks_returns() {
        return null;
    }

}
