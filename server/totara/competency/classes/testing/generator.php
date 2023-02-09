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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package totara_competency
 */

namespace totara_competency\testing;

use aggregation_test_aggregation\test_aggregation;
use coding_exception;
use core\entity\cohort;
use core\entity\course;
use core\entity\user;
use hierarchy_organisation\entity\organisation;
use hierarchy_position\entity\position;
use pathway_criteria_group\criteria_group;
use pathway_learning_plan\learning_plan;
use pathway_perform_rating\perform_rating;
use pathway_manual\entity\rating;
use pathway_manual\manual;
use pathway_manual\models\roles\role;
use pathway_manual\models\roles\role_factory;
use pathway_test_pathway\test_pathway;
use stdClass;
use totara_competency\achievement_configuration;
use totara_competency\aggregation_users_table;
use totara_competency\entity\pathway as pathway_entity;
use totara_competency\pathway;
use totara_competency\pathway_factory;
use totara_competency\plugin_types;
use totara_competency\user_groups;
use totara_criteria\criterion;
use totara_criteria\criterion_factory;
use totara_criteria\entity\criterion as criterion_entity;
use totara_hierarchy\entity\competency;
use totara_hierarchy\entity\competency_framework;
use totara_hierarchy\entity\competency_type;
use totara_hierarchy\entity\scale;
use totara_hierarchy\entity\scale_value;

/**
 * Pathway generator.
 *
 * Usage:
 *    $generator = $this->getDataGenerator()->get_plugin_generator('totara_competency');
 */
final class generator extends \core\testing\component_generator {

    /**
     * @var assignment_generator
     */
    protected $assignment_generator;

    /**
     * @var \totara_hierarchy\testing\generator
     */
    protected $hierarchy_generator;

    /**
     * @var \totara_plan\testing\generator
     */
    protected $plan_generator;

    /**************************************************************************
     * Basic competency creation
     **************************************************************************/

    protected function __construct() {
        global $CFG;
        parent::__construct();
        require_once($CFG->dirroot . '/totara/competency/tests/fixtures/test_achievement_detail.php');
        require_once($CFG->dirroot . '/totara/competency/tests/fixtures/test_aggregation.php');
        require_once($CFG->dirroot . '/totara/competency/tests/fixtures/test_pathway.php');
        require_once($CFG->dirroot . '/totara/competency/tests/fixtures/test_pathway_evaluator.php');
        require_once($CFG->dirroot . '/totara/competency/tests/fixtures/test_pathway_evaluator_source.php');
    }

    /**
     * Create a test competency.
     * If the name, framework or scale is not provided, then default values will be used.
     *
     * @param string|null $name
     * @param int|competency_framework|null $framework
     * @param array|null $comp_record
     *
     * @return competency
     */
    public function create_competency($name = null, $framework = null, $comp_record = null): competency {
        if (is_null($framework)) {
            $framework = $this->create_framework();
        }
        if (!is_numeric($framework)) {
            $framework = $framework->id;
        }

        $params = $comp_record ?? [];
        if (!is_null($name)) {
            $params['fullname'] = $name;
        }

        $comp = $this->hierarchy_generator()->create_hierarchy($framework, 'competency', $params);

        return new competency($comp);
    }

    /**
     * Create a test competency framework.
     * If the scale, name or description are not provided, then default values will be used.
     *
     * @param scale|null $scale Competency scale
     * @param string|null $name Framework name
     * @param string|null $description Framework description
     * @param array|null $framework_data Extra framework data
     * @return competency_framework
     */
    public function create_framework(scale $scale = null, string $name = null,
                                     string $description = null, array $framework_data = []): competency_framework {
        if (isset($name)) {
            $framework_data['fullname'] = $name;
        }
        if (isset($description)) {
            $framework_data['description'] = $description;
        }
        if (isset($scale)) {
            $framework_data['scale'] = $scale->id;
        } else {
            // This is a controversial idea
            $framework_data['scale'] = $this->create_scale()->id;
        }

        $framework = $this->hierarchy_generator()->create_framework('competency', $framework_data);
        return new competency_framework($framework, false);
    }

    /**
     * Create a test competency scale.
     * If the name, description or scale values are not provided, then default values will be used.
     *
     * @param string|null $name Scale name
     * @param string|null $description Scale description
     * @param array|null $values Array of arrays, like this: [
     *                              ['name' => string, 'proficient' => bool, 'default' => bool, 'sortorder' => int],
     *                          ]
     * @return scale
     */
    public function create_scale(string $name = null, string $description = null, array $values = null): scale {
        $scale_data = [];
        if (isset($name)) {
            $scale_data['name'] = $name;
        }
        if (isset($description)) {
            $scale_data['description'] = $description;
        }

        $scale = $this->hierarchy_generator()->create_scale('comp', $scale_data, $values);
        return new scale($scale, false);
    }

    /**
     * Create a test competency type.
     *
     * @param array|null $type_record
     *
     * @return competency_type
     */
    public function create_type(array $type_record = []): competency_type {
        return new competency_type($this->hierarchy_generator()->create_comp_type($type_record));
    }


    /**************************************************************************
     * Competency pathways
     **************************************************************************/

    /**
     * Create a criteria group pathway
     *
     * @param competency|stdClass|int $competency Competency entity, record or ID
     * @param criterion[]|criterion $criteria Can be multiple or a single pre-defined criterion object(s)
     * @param scale_value|stdClass|int|null $scale_value If not specified, defaults to first scale value for the competency
     * @param int|null $sort_order If not specified, defaults to first scale value for the competency
     *
     * @return criteria_group
     */
    public function create_criteria_group($competency, $criteria, $scale_value = null, int $sort_order = null): criteria_group {
        /** @var criteria_group $instance */
        $instance = $this->create_pathway(criteria_group::class, $competency, $sort_order);

        if (is_null($scale_value)) {
            $scale_value = $competency->scale->sorted_values_high_to_low->first();
        } else if (is_number($scale_value) || !$scale_value instanceof scale_value) {
            $scale_value = new scale_value($scale_value, true, true);
        }
        $instance->set_scale_value($scale_value);

        if (!is_array($criteria)) {
            $criteria = [$criteria];
        }
        foreach ($criteria as $criterion) {
            $instance->add_criterion($criterion);
        }

        $instance->save();

        return criteria_group::fetch($instance->get_id());
    }

    /**
     * Create a manual rating pathway.
     *
     * @param competency|stdClass|int $competency Competency entity, record or ID.
     * @param string[]|null $roles Possible manual rating roles, e.g. [self_role::class, manager::class]. Defaults to all.
     * @param int|null $sort_order Defaults to being sorted last.
     *
     * @return manual
     */
    public function create_manual($competency, array $roles = [], int $sort_order = null): manual {
        /** @var manual $instance */
        $instance = $this->create_pathway(manual::class, $competency, $sort_order);

        if ($roles) {
            $instance->set_roles($roles);
        } else {
            $instance->set_roles(array_map(function (role $role) {
                return $role::get_name();
            }, role_factory::create_all()));
        }

        return $instance->save();
    }

    /**
     * Create a manual rating.
     *
     * Note: If you are wanting the manual rating to be displayed/aggregated,
     *       you need to make sure there is a pathway for the role you specify.
     *
     * @param int|stdClass|competency|manual $competency Competency ID, record or entity, or alternatively a manual pathway.
     * @param int|stdClass|user $subject_user
     * @param int|stdClass|user $rater_user
     * @param role|string $as_role Role class or string e.g. pathway_manual\models\roles\manager::class or 'manager'
     * @param scale_value|int|null $scale_value If not specified, defaults to the first scale value set for the competency
     * @param string|null $comment
     * @param int|null $time Timestamp of when rating was made
     *
     * @return rating
     */
    public function create_manual_rating($competency, $subject_user, $rater_user,
                                         $as_role, $scale_value = null, $comment = null, $time = null): rating {
        $subject_id = isset($subject_user->id) ? $subject_user->id : $subject_user;
        $rater_id = isset($rater_user->id) ? $rater_user->id : $rater_user;

        if (is_a($as_role, role::class, true)) {
            $as_role = new $as_role();
        } else if (!$as_role instanceof role) {
            $as_role = role_factory::create($as_role);
        }

        if ($competency instanceof manual) {
            $competency = $competency->get_competency();
        }

        if (is_null($scale_value)) {
            if (!$competency instanceof competency) {
                $competency = new competency($competency);
            }
            $scale_value = $competency->scale->sorted_values_high_to_low->first();
            $scale_value = $scale_value ? $scale_value->id : null;
        } else if ($scale_value instanceof scale_value) {
            $scale_value = $scale_value->id;
        }

        $competency = isset($competency->id) ? $competency->id : $competency;

        $rating = new rating([
            'competency_id' => $competency,
            'user_id' => $subject_id,
            'scale_value_id' => $scale_value,
            'date_assigned' => $time ?? time(),
            'assigned_by' => $rater_id,
            'assigned_by_role' => $as_role::get_name(),
            'comment' => $comment,
        ]);
        $rating->save();

        (new aggregation_users_table())->queue_for_aggregation($subject_id, $competency);

        return $rating;
    }

    /**
     * Create a learning plan pathway.
     *
     * @param competency|stdClass|int $competency Competency entity, record or ID.
     * @param int|null $sort_order Defaults to being sorted last.
     *
     * @return learning_plan
     */
    public function create_learning_plan_pathway($competency, int $sort_order = null): learning_plan {
        /** @var learning_plan $instance */
        $instance = $this->create_pathway(learning_plan::class, $competency, $sort_order);
        return $instance->save();
    }

    /**
     * Create a perform rating pathway.
     *
     * @param competency|stdClass|int $competency Competency entity, record or ID.
     * @param int|null $sort_order Defaults to being sorted last.
     *
     * @return learning_plan
     */
    public function create_perform_rating_pathway($competency, int $sort_order = null): perform_rating {
        /** @var perform_rating $instance */
        $instance = $this->create_pathway(perform_rating::class, $competency, $sort_order);
        return $instance->save();
    }

    /**
     * Create a basic pathway, setting the competency and sort order value.
     *
     * @param string $pathway_class Pathway class or plugin name, e.g. manual::class OR 'criteria_group'
     * @param competency|stdClass|int $competency Competency entity, record or ID.
     * @param int|null $sort_order Defaults to being sorted last.
     *
     * @return pathway New instance of the pathway class you specified.
     */
    public function create_pathway(string $pathway_class, $competency, ?int $sort_order = null): pathway {
        if (class_exists($pathway_class)) {
            /** @var pathway $instance */
            $instance = new $pathway_class();
        } else {
            $instance = pathway_factory::create($pathway_class);
        }

        if (!$competency instanceof competency) {
            $competency = new competency($competency, true, true);
        }
        $instance->set_competency($competency);

        if (is_null($sort_order)) {
            $last_sort_order = pathway_entity::repository()->order_by('sortorder', 'desc')->first();
            $sort_order = $last_sort_order ? $last_sort_order->sortorder + 1 : 0;
        }
        $instance->set_sortorder($sort_order);

        return $instance;
    }

    /**
     * Create an achievement configuration.
     */
    public function create_achievement_configuration(competency $competency, string $aggregation_type): achievement_configuration {
        $configuration = new achievement_configuration($competency);
        $configuration->set_aggregation_type($aggregation_type);
        $configuration->save_aggregation();
        return $configuration;
    }

    /**************************************************************************
     * Non-competency related helpers
     **************************************************************************/

    /**
     * Create an individual criterion for use in a pathway
     *
     * @param string $criterion_type Type of criterion, e.g. onactivate::class, linkedcourses::class, coursecompletion::class etc.
     * @param competency|stdClass|int|null $competency Competency entity, record or ID.
     * @param int|null $aggregation_method Aggregation method, either criterion::AGGREGATE_ALL or criterion::AGGREGATE_ANY_N
     * @param int[]|null $items Array of IDs for this criterion - e.g. for coursecompletion, it would be an array of course IDs
     * @param int|null $required_items The number of items that need to be completed for this criteria to be met (ANY_N aggregation)
     *
     * @return criterion
     */
    public function create_criterion(string $criterion_type, $competency = null, int $aggregation_method = criterion::AGGREGATE_ALL,
        array $items = [], int $required_items = 1): criterion {
        /** @var criterion $criterion */
        $criterion = criterion_factory::create($criterion_type);

        if (!is_null($competency)) {
            if (!is_number($competency)) {
                $competency = $competency->id;
            }
            $criterion->set_competency_id($competency);
        }
        $criterion->set_aggregation_method($aggregation_method);
        $criterion->set_aggregation_params(['req_items' => $required_items]);

        $criterion->add_items($items);

        $criterion->save();

        return $criterion;
    }

    /**
     * Set completion course completion status for a user in a course.
     *
     * @param stdClass|int $course Course ID or record
     * @param stdClass|int $user User ID or record
     * @param int|null $completion_status One of COMPLETION_STATUS_NOTYETSTARTED, COMPLETION_STATUS_INPROGRESS,
     *                                  COMPLETION_STATUS_COMPLETE, or COMPLETION_STATUS_COMPLETEVIARPL. Defaults to complete.
     */
    public function create_course_enrollment_and_completion($course, $user, ?int $completion_status = null) {
        global $CFG;
        require_once($CFG->dirroot . '/lib/enrollib.php');
        require_once($CFG->dirroot . '/completion/completion_completion.php');

        if (!is_numeric($course)) {
            $course = $course->id;
        }
        if (!is_numeric($user)) {
            $user = $user->id;
        }

        if (!core_enrol_get_all_user_enrolments_in_course($user, $course)) {
            $this->datagenerator->enrol_user($user, $course);
        }

        $completion = new \completion_completion(['course' => $course, 'userid' => $user]);

        if ($completion_status == COMPLETION_STATUS_COMPLETEVIARPL) {
            $completion->rpl = 1;
        }

        if ($completion_status == COMPLETION_STATUS_NOTYETSTARTED) {
            $completion->mark_enrolled();
        } else if ($completion_status == COMPLETION_STATUS_INPROGRESS) {
            $completion->mark_inprogress();
        } else {
            $completion->mark_complete();
        }
    }

    /**
     * Create a learning plan.
     *
     * @param stdClass|int $for_user User the learning plan is for
     * @param array $record Learning plan record data
     *
     * @return \development_plan
     */
    protected function create_learning_plan($for_user, array $record = []): \development_plan {
        if (!is_numeric($for_user)) {
            $for_user = $for_user->id;
        }

        $completed = $record['completed'] ?? false;
        unset($record['completed']);

        $plan = $this->plan_generator()->create_learning_plan(array_merge($record, ['userid' => $for_user]));

        $plan = new \development_plan($plan->id);

        if ($completed) {
            $plan->set_status(DP_PLAN_STATUS_COMPLETE);
        }

        return $plan;
    }

    /**
     * Add a competency with rating to a learning plan.
     *
     * @param \development_plan $plan
     * @param int $competency_id
     * @param int $scale_value_id
     * @param int $date_assigned
     */
    protected function add_learning_plan_competency(\development_plan $plan, int $competency_id,
                                                    int $scale_value_id = null, int $date_assigned = null): void {
        global $CFG;
        require_once($CFG->dirroot . '/totara/plan/components/competency/competency.class.php');

        $this->plan_generator()->add_learning_plan_competency($plan->id, $competency_id);

        if (isset($scale_value_id)) {
            $plan_competency = new \dp_competency_component($plan);
            $details = (object) ['manual' => true, 'date_assigned' => $date_assigned];
            $plan_competency->set_value($competency_id, $plan->userid, $scale_value_id, $details);
        }
    }

    /**
     * Create a learning plan with competencies assigned.
     *
     * @param stdClass|int $for_user User the learning plan is for
     * @param array $competencies Array of [Competency ID => Scale Value ID]
     * @param array $record Learning plan record data
     *
     * @return \development_plan
     */
    public function create_learning_plan_with_competencies($for_user, array $competencies, array $record = []): \development_plan {
        $plan = $this->create_learning_plan($for_user, $record);

        foreach ($competencies as $competency => $scale_value) {
            $this->add_learning_plan_competency($plan, $competency, $scale_value);
        }

        return $plan;
    }


    /**************************************************************************
     * Behat Specific Generators
     **************************************************************************/

    /**
     * @param array $attributes
     */
    public function create_assignment_for_behat(array $attributes = []) {
        $attributes['competency_id'] = self::get_record_id_from_field(competency::TABLE, 'idnumber', $attributes['competency']);
        unset($attributes['competency']);

        $attributes['user_group_id'] = self::get_user_group_id_for_assignment(
            $attributes['user_group_type'],
            $attributes['user_group']
        );
        unset($attributes['user_group']);

        if (isset($attributes['min_proficiency_override']) && $attributes['min_proficiency_override']) {
            $attributes['minproficiencyid'] = self::get_record_id_from_field(
                'comp_scale_values',
                'idnumber',
                $attributes['min_proficiency_override']
            );
            unset($attributes['min_proficiency_override']);
        }

        $this->assignment_generator()->create_assignment($attributes);
    }

    /**
     * @param array $attributes
     */
    public function create_criteria_group_pathway_for_behat(array $attributes = []) {
        $competency = self::get_record_id_from_field(competency::TABLE, 'idnumber', $attributes['competency']);
        $scale_value = self::get_record_id_from_field(scale_value::TABLE, 'idnumber', $attributes['scale_value']);

        $aggregation_string = $attributes['aggregation'] ?? '';
        $aggregation = null;
        switch (strtolower(trim($aggregation_string))) {
            case 'any':
                $aggregation = Criterion::AGGREGATE_ANY_N;
                break;

            case 'all':
                $aggregation = Criterion::AGGREGATE_ALL;
                break;

            default:
                $aggregation = '';
        }

        $criteria = criterion_entity::repository()
            ->where_in('idnumber', explode(',', $attributes['criteria']))
            ->get()
            ->map_to(function (criterion_entity $criterion) use ($aggregation): criterion {
                $actual = criterion_factory::fetch_from_entity($criterion);

                return $aggregation
                    ? $actual->set_aggregation_method($aggregation)
                    : $actual;
            })
            ->all();

        $this->create_criteria_group($competency, $criteria, $scale_value, $attributes['sortorder'] ?? null);
    }

    /**
     * @param array $attributes
     */
    public function create_pathway_for_behat(array $attributes = []) {
        $this->create_pathway(
            $attributes['pathway'],
            self::get_record_id_from_field(competency::TABLE, 'idnumber', $attributes['competency']),
            $attributes['sortorder'] ?? null
        )->save();
    }

    /**
     * @param array $attributes
     */
    public function create_learning_plan_pathway_for_behat(array $attributes = []) {
        $this->create_learning_plan_pathway(
            self::get_record_id_from_field(competency::TABLE, 'idnumber', $attributes['competency']),
            $attributes['sortorder'] ?? null
        );
    }

    /**
     * @param array $attributes
     */
    public function create_learning_plan_with_competency_value_for_behat(array $attributes = []) {
        $plan_id = self::get_record_id_from_field('dp_plan', 'name', $attributes['plan']);
        $competency_id = self::get_record_id_from_field(competency::TABLE, 'idnumber', $attributes['competency']);
        $scale_value_id = $attributes['scale_value'] ?
            self::get_record_id_from_field(scale_value::TABLE, 'idnumber', $attributes['scale_value']) :
            null;
        $date_assigned = isset($attributes['date']) ? strtotime($attributes['date']) : null;

        $this->add_learning_plan_competency(new \development_plan($plan_id), $competency_id, $scale_value_id, $date_assigned);
    }

    /**
     * @param array $attributes
     */
    public function create_manual_pathway_for_behat(array $attributes = []) {
        $this->create_manual(
            self::get_record_id_from_field(competency::TABLE, 'idnumber', $attributes['competency']),
            explode(',', $attributes['roles']),
            $attributes['sortorder'] ?? null
        );
    }

    /**
     * @param array $attributes
     */
    public function create_manual_rating_for_behat(array $attributes = []) {
        $this->create_manual_rating(
            self::get_record_id_from_field(competency::TABLE, 'idnumber', $attributes['competency']),
            self::get_record_id_from_field(user::TABLE, 'username', $attributes['subject_user']),
            self::get_record_id_from_field(user::TABLE, 'username', $attributes['rater_user']),
            $attributes['role'],
            self::get_record_id_from_field(scale_value::TABLE, 'idnumber', $attributes['scale_value']),
            $attributes['comment'] ?? null,
            isset($attributes['date']) ? strtotime($attributes['date']) : null
        );
    }

    /**
     * @param array $attributes
     */
    public function create_perform_rating_pathway_for_behat(array $attributes = []) {
        $this->create_perform_rating_pathway(
            self::get_record_id_from_field(competency::TABLE, 'idnumber', $attributes['competency']),
            $attributes['sortorder'] ?? null
        );
    }

    /**
     * @param array $attributes
     */
    public function create_course_enrollment_and_completion_for_behat(array $attributes = []) {
        $this->create_course_enrollment_and_completion(
            self::get_record_id_from_field(course::TABLE, 'shortname', $attributes['course']),
            self::get_record_id_from_field(user::TABLE, 'username', $attributes['user']),
            $attributes['status'] ?? null
        );
    }

    /**
     * @param array $attributes
     */
    public function create_linked_course_for_behat(array $attributes = []) {
        global $DB, $USER;
        $DB->insert_record('comp_criteria', [
            'competencyid' => self::get_record_id_from_field(competency::TABLE, 'idnumber', $attributes['competency']),
            'itemtype' => 'coursecompletion',
            'iteminstance' => self::get_record_id_from_field(course::TABLE, 'shortname', $attributes['course']),
            'timecreated' => time(),
            'timemodified' => time(),
            'usermodified' => $USER ? $USER->id : get_admin()->id,
            'linktype' => (bool) ($attributes['mandatory'] ?? 0),
        ]);
    }

    /**
     * Create an achievement configuration.
     */
    public function create_achievement_configuration_for_behat(array $attributes = []) {
        global $DB;
        $competency_id = $DB->get_field(competency::TABLE, 'id', ['idnumber' => $attributes['competency']]);
        $competency = new competency($competency_id);
        $this->create_achievement_configuration($competency, $attributes['aggregation_type']);
    }


    /**************************************************************************
     * Testing-specific classes
     **************************************************************************/

    /**
     * Create a test pathway
     *
     * @param competency|null $competency
     * @return test_pathway|pathway
     */
    public function create_test_pathway(?competency $competency = null): pathway {
        plugin_types::enable_plugin('test_pathway', 'pathway', 'totara_competency');

        $pathway = new test_pathway();
        if (!is_null($competency)) {
            $pathway->set_competency($competency);

            $pathway->save();
        }

        return $pathway;
    }

    /**
     * Create a test aggregation type.
     *
     * @return test_aggregation
     */
    public function create_test_aggregation(): test_aggregation {
        return new test_aggregation();
    }


    /**************************************************************************
     * Internal Helpers
     **************************************************************************/

    /**
     * Get an instance of assignment specific generator
     *
     * @return assignment_generator
     */
    public function assignment_generator() {
        if (is_null($this->assignment_generator)) {
            $this->assignment_generator = new assignment_generator($this);
        }

        return $this->assignment_generator;
    }

    /**
     * Get the generator used for hierarchies.
     *
     * @return \totara_hierarchy\testing\generator
     */
    public function hierarchy_generator(): \totara_hierarchy\testing\generator {
        return \totara_hierarchy\testing\generator::instance();
    }

    /**
     * Get the generator used for learning plans.
     *
     * @return \totara_plan\testing\generator
     */
    public function plan_generator(): \totara_plan\testing\generator {
        return \totara_plan\testing\generator::instance();
    }

    /**
     * We want to get the ID for a record based upon a unique, human readable identifier. Used for behat.
     *
     * @param string $table
     * @param string $field
     * @param string $identifier
     * @return int
     */
    private static function get_record_id_from_field(string $table, string $field, string $identifier): int {
        global $DB;
        return $DB->get_field($table, 'id', [$field => $identifier]);
    }

    /**
     * Get the appropriate group ID for the specified group type and specified identifer.
     *
     * @param string $user_group_type
     * @param string $group_identifier
     * @return int
     */
    private static function get_user_group_id_for_assignment(string $user_group_type, string $group_identifier): int {
        switch ($user_group_type) {
            case user_groups::USER:
                return self::get_record_id_from_field(user::TABLE, 'username', $group_identifier);
            case user_groups::ORGANISATION:
                return self::get_record_id_from_field(organisation::TABLE, 'idnumber', $group_identifier);
            case user_groups::POSITION:
                return self::get_record_id_from_field(position::TABLE, 'idnumber', $group_identifier);
            case user_groups::COHORT:
                return self::get_record_id_from_field(cohort::TABLE, 'idnumber', $group_identifier);
            default:
                throw new coding_exception('Invalid user group specified: ' . $user_group_type);
        }
    }

}
