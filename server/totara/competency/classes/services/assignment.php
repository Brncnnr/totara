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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_competency
 */

namespace totara_competency\services;

use context_system;
use core\format;
use core\orm\collection;
use core\output\notification;
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;
use totara_competency\assignment_create_exception;
use totara_competency\baskets\competency_basket;
use totara_competency\entity;
use totara_competency\models;
use totara_core\advanced_feature;
use totara_core\basket\session_basket;
use core\webapi\formatter\field\string_field_formatter;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/lib/externallib.php');

class assignment extends \external_api {

    /**
     * @return external_function_parameters
     */
    public static function index_parameters(): external_function_parameters {
        return new external_function_parameters(
            [
                'filters' => new external_single_structure(
                    [
                        'text' => new external_value(PARAM_TEXT, 'text search field value', VALUE_OPTIONAL, null),
                        'assignment_type' => new external_multiple_structure(
                            new external_value(PARAM_ALPHAEXT, 'admin, self, etc', VALUE_OPTIONAL),
                            'one or more assignment types',
                            VALUE_OPTIONAL
                        ),
                        'user_group_type' => new external_multiple_structure(
                            new external_value(PARAM_ALPHAEXT, 'position, organisation, cohort, user', VALUE_OPTIONAL),
                            'one or more user group types',
                            VALUE_OPTIONAL
                        ),
                        'framework' => new external_value(PARAM_INT, 'competency framework id', VALUE_OPTIONAL, null),
                        'status' => new external_value(PARAM_INT, 'status, null/0/1', VALUE_OPTIONAL, null),
                        'basket' => new external_value(PARAM_ALPHANUMEXT, 'Search by basket key', VALUE_OPTIONAL, null),
                        'ids' => new external_multiple_structure(
                            new external_value(PARAM_INT, 'ids', VALUE_OPTIONAL),
                            'ids to filter by',
                            VALUE_OPTIONAL
                        ),
                    ],
                    VALUE_REQUIRED
                ),
                'page' => new external_value(PARAM_INT, 'pagination: page to load', VALUE_REQUIRED),
                'order' => new external_value(PARAM_ALPHANUMEXT, 'name of column to order by', VALUE_REQUIRED),
                'direction' => new external_value(PARAM_ALPHA, 'direction of ordering (either ASC or DESC)', VALUE_REQUIRED),
            ]
        );
    }

    /**
     * @param array $filters
     * @param int $page
     * @param string $order_by
     * @param string $order_dir
     * @return array
     */
    public static function index(array $filters, int $page, string $order_by, string $order_dir) {
        advanced_feature::require('competency_assignment');
        if (!has_capability('totara/competency:view_assignments', context_system::instance())) {
            require_capability('totara/competency:manage_assignments', context_system::instance());
        }
        require_capability('moodle/user:viewdetails', context_system::instance());


        $order_dir = (strtolower($order_dir) == 'asc') ? 'ASC' : 'DESC';

        $repository = entity\assignment::repository()
            ->select('*')
            ->with('competency.active_pathways')
            ->with_names()
            ->set_filters($filters);

        // Some specific order requirements
        switch ($order_by) {
            case 'competency_name':
                $order_by = "competency_name $order_dir, type $order_dir, user_group_name $order_dir, status $order_dir, id $order_dir";
                $repository->order_by_raw($order_by);
                break;
            case 'user_group_name':
                $order_by = "user_group_name $order_dir, competency_name $order_dir, type $order_dir, status $order_dir, id $order_dir";
                $repository->order_by_raw($order_by);
                break;
            case 'most_recently_updated':
                $order_by = "updated_at DESC, id ASC";
                $repository->order_by_raw($order_by);
                break;
            default:
                $repository->order_by('id');
                break;
        }

        $assignments = $repository->paginate($page);

        return $assignments->transform(function (entity\assignment $assignment) {
            $assignment = models\assignment::load_by_entity($assignment);
            return self::prepare_assignment_response($assignment);
        })->to_array();
    }

    /**
     * @return null
     */
    public static function index_returns() {
        return null;
    }

    /**
     * @return external_function_parameters
     */
    public static function create_parameters(): external_function_parameters {
        return new external_function_parameters(
            [
                'basket' => new external_value(PARAM_ALPHANUMEXT, 'Basket key to create an assignment from', VALUE_REQUIRED),
                'usergroups' => new external_single_structure(
                    [
                        'user' => new external_multiple_structure(new external_value(PARAM_INT), 'ids', VALUE_OPTIONAL),
                        'cohort' => new external_multiple_structure(new external_value(PARAM_INT), 'ids', VALUE_OPTIONAL),
                        'organisation' => new external_multiple_structure(new external_value(PARAM_INT), 'ids', VALUE_OPTIONAL),
                        'position' => new external_multiple_structure(new external_value(PARAM_INT), 'ids', VALUE_OPTIONAL),
                    ],
                    VALUE_REQUIRED
                ),
                'status' => new external_value(PARAM_INT, 'Assignment activation status (0 - draft, 1 -active)', VALUE_REQUIRED),
            ]
        );
    }

    /**
     * Create competency assignments from basket
     *
     * @param string $basket_id
     * @param array $user_groups
     * @param int $status
     * @return array
     */
    public static function create(string $basket_id, array $user_groups, int $status) {
        advanced_feature::require('competency_assignment');
        require_capability('totara/competency:manage_assignments', context_system::instance());
        require_capability('moodle/user:viewdetails', context_system::instance());

        try {
            $basket = new competency_basket($basket_id);
            // If competencies got deleted or hidden show error message
            $diff = $basket->sync();
            if (!empty($diff)) {
                throw new assignment_create_exception('Competency basket out of sync');
            }
            $assignments = (new models\assignment_actions())->create_from_competencies(
                $basket->load(),
                $user_groups,
                entity\assignment::TYPE_ADMIN,
                $status
            );
        } catch (assignment_create_exception $exception) {
            // Gracefully fail those exceptions as those could be caused by edge cases we want to cover
            \core\notification::add(
                get_string('error_create_assignments', 'totara_competency'),
                notification::NOTIFY_ERROR
            );
            return [];
        }

        $basket->delete();

        return $assignments->map(function (models\assignment $assignment) {
            return self::prepare_assignment_response($assignment);
        })->to_array();
    }

    /**
     * @return null
     */
    public static function create_returns() {
        return null;
    }

    /**
     * @return external_function_parameters
     */
    public static function create_from_baskets_parameters(): external_function_parameters {
        return new external_function_parameters(
            [
                'basket' => new external_value(PARAM_ALPHANUMEXT, 'Basket key to create an assignment from', VALUE_REQUIRED),
                'usergroups' => new external_single_structure(
                    [
                        'user' => new external_value(PARAM_ALPHANUMEXT, 'Users basket key', VALUE_OPTIONAL, null),
                        'cohort' => new external_value(PARAM_ALPHANUMEXT, 'Audiences basket key', VALUE_OPTIONAL, null),
                        'organisation' => new external_value(PARAM_ALPHANUMEXT, 'Positions basket key', VALUE_OPTIONAL, null),
                        'position' => new external_value(PARAM_ALPHANUMEXT, 'Organisations basket key', VALUE_OPTIONAL, null),
                    ],
                    VALUE_REQUIRED
                ),
                'status' => new external_value(PARAM_INT, 'Assignment activation status (0 - draft, 1 -active)', VALUE_REQUIRED),
            ]
        );
    }
    /**
     * Create competency assignments from basket
     *
     * @param string $basket_id
     * @param array $user_groups
     * @param int $status
     * @return array
     */
    public static function create_from_baskets(string $basket_id, array $user_groups, int $status) {
        advanced_feature::require('competency_assignment');
        require_capability('totara/competency:manage_assignments', context_system::instance());
        require_capability('moodle/user:viewdetails', context_system::instance());

        try {
            $basket = new competency_basket($basket_id);
            // If competencies got deleted or hidden show error message
            $diff = $basket->sync();
            if (!empty($diff)) {
                throw new assignment_create_exception('Competency basket out of sync');
            }

            $competency_ids = $basket->load();

            // Need to resolve baskets
            $resolved_ids = [];
            // We want to keep it to clear at the end
            $ug_baskets = [];

            $user_group_count = 0;
            foreach ($user_groups as $group => $key) {
                $ug_basket = new session_basket($key);
                $basket_items = $ug_basket->load();
                $user_group_count += count($basket_items);
                $resolved_ids[$group] = $basket_items;
                $ug_baskets[] = $ug_basket;
            }

            $expected_assignments_count = $user_group_count * count($competency_ids);

            $assignments = (new models\assignment_actions())->create_from_competencies(
                $competency_ids,
                $resolved_ids,
                entity\assignment::TYPE_ADMIN,
                $status
            );
        } catch (assignment_create_exception $exception) {
            // Gracefully fail those exceptions as those could be caused by edge cases we want to cover
            \core\notification::add(
                get_string('error_create_assignments', 'totara_competency'),
                notification::NOTIFY_ERROR
            );
            return [];
        }

        $basket->delete();
        foreach ($ug_baskets as $ug_basket) {
            $ug_basket->delete();
        }

        $assignments->transform_to(function (models\assignment $assignment) {
            return self::prepare_assignment_response($assignment);
        });

        self::create_notification($assignments, $expected_assignments_count, $status);

        return $assignments->to_array();
    }

    /**
     * @param \core\orm\collection $assignments
     * @param int $expected_assignments_count
     * @param int $status
     */
    private static function create_notification(collection $assignments, int $expected_assignments_count, int $status) {
        advanced_feature::require('competency_assignment');

        $assignment_created = count($assignments);
        $skipped = abs($expected_assignments_count - $assignment_created);

        // the following lines are important to determine if singular or plural confirmation is shown
        $created_singular_plural = $assignment_created > 1 ? 'plural' : 'singular';
        $skipped_singular_plural = $skipped > 1 ? 'plural' : 'singular';

        $string_data = ['created' => $assignment_created, 'skipped' => $skipped];

        if (count($assignments) > 0) {
            // If not all expected assignments where created (duplicates skipped) show different message
            if ($skipped > 0) {
                $confirm_string = sprintf(
                    "confirm_assignment_creation_%s_skipped_%s",
                    $created_singular_plural,
                    $skipped_singular_plural
                );
            } else {
                $confirm_string = sprintf(
                    "confirm_assignment_creation_%s_%s",
                    $status ? 'active' : 'draft',
                    $created_singular_plural
                );
            }
        } else {
            $confirm_string = sprintf("confirm_assignment_creation_none_%s", $skipped_singular_plural);
        }
        \core\notification::add(
            get_string($confirm_string, 'totara_competency', (object)$string_data),
            count($assignments) ? notification::NOTIFY_SUCCESS : notification::NOTIFY_WARNING
        );
    }

    /**
     * @return null
     */
    public static function create_from_baskets_returns() {
        return null;
    }

    /**
     * @return external_function_parameters
     */
    public static function action_parameters(): external_function_parameters {
        return new external_function_parameters(
            [
                'action' => new external_value(
                    PARAM_ALPHANUMEXT,
                    'action like delete, activate, archive',
                    VALUE_REQUIRED
                ),
                'basket' => new external_value(
                    PARAM_ALPHANUMEXT,
                    'Basket key to filter assignments for archived ones. Mutually exclusive with assignment id.',
                    VALUE_REQUIRED
                ),
                'id' => new external_value(
                    PARAM_INT,
                    'Assignment id to archive. Mutually exclusive with basket id.',
                    VALUE_REQUIRED
                ),
                'extra' => new external_single_structure(
                    [
                        'continue_tracking' => new external_value(
                            PARAM_BOOL,
                            'flag if tracking should continue for affected records (only applies for archive)',
                            VALUE_OPTIONAL,
                            false
                        ),
                    ],
                    'options'
                )
            ]
        );
    }

    /**
     * Update assignment
     *
     * @param string $action
     * @param string $basket_key
     * @param int|null $assignment_id
     * @param array $extra
     * @return array
     */
    public static function action(string $action, ?string $basket_key, ?int $assignment_id, array $extra) {
        advanced_feature::require('competency_assignment');
        require_capability('totara/competency:manage_assignments', context_system::instance());
        require_capability('moodle/user:viewdetails', context_system::instance());

        if (is_null($basket_key) && is_null($assignment_id) || !is_null($basket_key) && !is_null($assignment_id)) {
            throw new \coding_exception('You must supply either basket_id or assignment_id, not both of them');
        }

        if (!is_null($basket_key)) {
            $basket = new session_basket($basket_key);
            $ids = $basket->load();
            $basket->delete();
        } else {
            $ids = [$assignment_id];
        }

        if (empty($ids)) {
            return [];
        }

        $continue_tracking = $extra['continue_tracking'] ?? false;

        $model = new models\assignment_actions();

        // Explicitly calling methods here to make it possible to find their usage
        switch ($action) {
            case 'archive':
                $ids = $model->archive($ids, $continue_tracking);
                break;
            case 'activate':
                $ids = $model->activate($ids);
                break;
            case 'delete':
                $ids = $model->delete($ids);
                break;
            default:
                throw new \coding_exception('unknown action for update webservice');
                break;
        }

        return $ids;
    }

    /**
     * @return null
     */
    public static function action_returns() {
        return null;
    }

    /**
     * Prepare assignment response
     *
     * @param models\assignment $assignment
     * @return array
     */
    protected static function prepare_assignment_response(models\assignment $assignment): array {
        global $PAGE;
        // As we use format_string make sure we have the page context set
        $context = context_system::instance();

        $PAGE->set_context($context);
        $string_formatter = new string_field_formatter(format::FORMAT_HTML, $context);
        $plain_string_formatter = new string_field_formatter(format::FORMAT_PLAIN, $context);

        $response = [
            'id' => $assignment->get_field('id'),
            'assignment_type_name' => $assignment->get_type_name(),
            'status_name' => $assignment->get_status_name(),
            'updated_at' => $assignment->get_field('updated_at'),
            'competency_id' => $assignment->get_field('competency_id'),
            'status' => $assignment->get_field('status'),
            'user_group_type' => $assignment->get_field('user_group_type'),
            'user_group_id' => $assignment->get_field('user_group_id'),
        ];

        $response['competency_name'] = $string_formatter->format($assignment->get_competency()->display_name);
        $response['user_group_name'] = $string_formatter->format($assignment->get_user_group_name());
        $response['pathway_warning_message'] = $plain_string_formatter->format($assignment->get_pathway_warning_message());
        $response['min_proficiency_value_name'] = $plain_string_formatter->format($assignment->get_min_value()->name);

        $response['has_default_proficiency_value_override_yes_no'] = '';
        if ($assignment->has_default_proficiency_value_override()) {
            $yes_short = get_string('yes_short', 'totara_competency');
            $response['has_default_proficiency_value_override_yes_no'] = $plain_string_formatter->format($yes_short);
        }

        return $response;
    }

}
