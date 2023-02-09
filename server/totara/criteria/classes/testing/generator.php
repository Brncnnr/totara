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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package totara_criteria
 */

namespace totara_criteria\testing;

use stdClass, coding_exception;
use criteria_childcompetency\childcompetency;
use criteria_coursecompletion\coursecompletion;
use criteria_linkedcourses\linkedcourses;
use criteria_onactivate\onactivate;
use criteria_othercompetency\othercompetency;
use core\entity\course;
use totara_competency\plugin_types;
use totara_criteria\criterion;
use totara_criteria\criterion_factory;
use totara_criteria\entity\criterion as criterion_entity;
use totara_hierarchy\entity\competency;

/**
 * Coursecompletion criterion generator.
 *
 * Usage:
 *     $generator = $this->getDataGenerator()->get_plugin_generator('totara_criteria');
 */
final class generator extends \core\testing\component_generator {

    /**
     * Create a coursecompletion criterion
     *
     *  Data
     *  [
     *      'aggregation' => [
     *          'method' => criterion::AGGREGATE_ALL, // Optional Aggregation method - defaults to ALL
     *          'req_items' => 1, // Optional number of required items. Only used with AGGREGATE_ANY_N. Defaults to 1
     *      ]
     *      'courseids' => [], // Ids of courses to be completed. PHPUnit only.
     *      'number_required' => 'all', // Either 'all' or the number of items required for aggregation. Behat only.
     *      'courses' => [], // Shortnames of courses to be completed. Behat only.
     *      'idnumber' => '...', // ID number of the criterion.
     *  ]
     *
     * @param array $data Criterion data
     * @return criterion
     */
    public function create_coursecompletion(array $data = []) {
        $instance = new coursecompletion();

        $instance->set_idnumber($data['idnumber'] ?? null);
        $this->set_aggregation($instance, $data);
        $this->set_courses($instance, $data);

        $instance->save();

        return $instance;
    }

    /**
     * Create a linkedcourses criterion
     *
     *  Data
     *  [
     *      'aggregation' => [
     *          'method' => criterion::AGGREGATE_ALL, // Optional Aggregation method - defaults to ALL
     *          'req_items' => 1, // Optional number of required items. Only used with AGGREGATE_ANY_N. Defaults to 1
     *      ],
     *      'number_required' => 'all', // Either 'all' or the number of items required for aggregation. Behat only.
     *      'competency' => 1,   // Id of competency whose child competencies should be achieved
     *      'idnumber' => '...', // ID number of the criterion.
     *  ]
     *
     * @param array $data Criterion data
     * @return criterion
     */
    public function create_linkedcourses(array $data = []) {
        $instance = new linkedcourses();

        $instance->set_idnumber($data['idnumber'] ?? null);
        $this->set_aggregation($instance, $data);
        $this->set_competency_id($instance, $data['competency']);

        $instance->save();

        return $instance;
    }

    /**
     * Create an onactivate criterion
     *
     *  Data
     *  [
     *      'competency' => 1,   // Id of competency whose child competencies should be achieved
     *      'idnumber' => '...', // ID number of the criterion.
     *  ]
     *
     * @param array $data
     * @return criterion
     */
    public function create_onactivate(array $data = []) {
        $instance = new onactivate();

        $instance->set_idnumber($data['idnumber'] ?? null);
        $this->set_competency_id($instance, $data['competency']);

        $instance->save();

        // Re-read the instance to ensure all default values are also set
        return $instance;
    }

    /**
     * Create a childcompetency criterion
     *
     *  Data
     *  [
     *      'aggregation' => [
     *          'method' => criterion::AGGREGATE_ALL, // Optional Aggregation method - defaults to ALL
     *          'req_items' => 1, // Optional number of required items. Only used with AGGREGATE_ANY_N. Defaults to 1
     *      ],
     *      'number_required' => 'all', // Either 'all' or the number of items required for aggregation. Behat only.
     *      'competency' => 1,   // Id of competency whose child competencies should be achieved
     *      'idnumber' => '...', // ID number of the criterion.
     *  ]
     *
     * @param array $data Criterion data
     * @return criterion
     */
    public function create_childcompetency(array $data = []) {
        $instance = new childcompetency();

        $instance->set_idnumber($data['idnumber'] ?? null);
        $this->set_aggregation($instance, $data);
        $this->set_competency_id($instance, $data['competency'] ?? null);

        $instance->save();

        // Re-read the instance to ensure all default values are also set
        return $instance;
    }

    /**
     * Create a othercompetency criterion
     *
     *  Data
     *  [
     *      'aggregation' => [
     *          'method' => criterion::AGGREGATE_ALL, // Optional Aggregation method - defaults to ALL
     *          'req_items' => 1, // Optional number of required items. Only used with AGGREGATE_ANY_N. Defaults to 1
     *      ]
     *      'competencyids' => [], // Ids of competencies to be completed. PHPUnit only.
     *      'number_required' => 'all', // Either 'all' or the number of items required for aggregation. Behat only.
     *      'competencies' => [], // Shortnames of courses to be completed. Behat only.
     *      'idnumber' => '...', // ID number of the criterion.
     *  ]
     *
     * @param array $data Criterion data
     * @return criterion
     */
    public function create_othercompetency(array $data = []) {
        /** @var othercompetency $instance */
        $instance = new othercompetency();

        $instance->set_idnumber($data['idnumber'] ?? null);
        $this->set_aggregation($instance, $data);
        $this->set_competencies($instance, $data);

        $instance->save();

        return $instance;
    }

    /**
     * Create a test criterion
     *
     * @return criterion
     */
    public function create_test_criterion(string $plugin): criterion {
        plugin_types::enable_plugin($plugin, 'criteria', 'totara_criteria');
        $criterion = criterion_factory::create($plugin);
        $criterion->update_items();
        return $criterion;
    }

    /**
     * Creates a criteria item for a course
     *
     * @param stdClass $course
     * @return int the id of the item just generated
     */
    public function create_course_criterion_item(stdClass $course) {
        global $DB;

        $criterion = new criterion_entity();
        $criterion->plugin_type = 'test';
        $criterion->aggregation_method = criterion::AGGREGATE_ALL;
        $criterion->criterion_modified = time();
        $criterion->save();

        $record = new stdClass();
        $record->criterion_id = $criterion->id;
        $record->item_type = 'course';
        $record->item_id = $course->id;
        return $DB->insert_record('totara_criteria_item', $record);
    }

    /**
     * Set the aggregation for a criterion.
     * Intended to be compatible with both unit and behat tests.
     *
     * @param criterion $instance
     * @param array|null $data
     * @throws coding_exception
     */
    private function set_aggregation(criterion $instance, $data) {
        if (isset($data['aggregation'])) {
            // For PHPUnit.
            $instance->set_aggregation_method($data['aggregation']['method'] ?? criterion::AGGREGATE_ALL);
            $instance->set_aggregation_params(['req_items' => $data['aggregation']['req_items'] ?? 1]);
        } else if (isset($data['number_required'])) {
            // For Behat.
            if ($data['number_required'] == 'all') {
                $instance->set_aggregation_method(criterion::AGGREGATE_ALL);
            } else if (is_numeric($data['number_required'])) {
                $instance->set_aggregation_method(criterion::AGGREGATE_ANY_N);
                $instance->set_aggregation_params(['req_items' => $data['number_required']]);
            } else {
                throw new coding_exception('Must specify either a number or \'all\' for number_required when creating criteria.');
            }
        }
    }

    /**
     * Set the competency ID items for a criterion.
     * Intended to be compatible with both unit and behat tests.
     *
     * Note that if an ID number that is just a number is specified, then it will not be resolved (for performance reasons).
     * For behat, make sure your competency ID number is non-numeric.
     *
     * @param criterion $instance
     * @param int|string $competency Competency ID or ID number
     */
    private function set_competency_id(criterion $instance, $competency) {
        global $DB;

        if (empty($competency)) {
            return;
        }

        if (!is_numeric($competency)) {
            $competency = $DB->get_field(competency::TABLE, 'id', ['idnumber' => $competency]);
        }

        $instance->set_competency_id($competency);
    }

    /**
     * Set the course items for a criterion.
     * Intended to be compatible with both unit and behat tests.
     *
     * @param criterion $instance
     * @param array|null $data
     * @throws Exception
     */
    private function set_courses(criterion $instance, $data) {
        if (isset($data['courseids'])) {
            // For PHPUnit.
            $instance->add_items($data['courseids']);
        } else if (isset($data['courses'])) {
            // For Behat.
            $course_shortnames = explode(',', $data['courses']);
            $course_ids = course::repository()
                ->select('id')
                ->where_in('shortname', $course_shortnames)
                ->get()
                ->pluck('id');
            $instance->add_items($course_ids);
        } else {
            // None specified.
            throw new coding_exception("Must specify either courseids or courses when creating {$instance->get_plugin_type()} criteria.");
        }
    }

    /**
     * Set the competency items for a criterion.
     * Intended to be compatible with both unit and behat tests.
     *
     * @param criterion $instance
     * @param array|null $data
     */
    private function set_competencies(criterion $instance, $data) {
        if (isset($data['competencyids'])) {
            $instance->add_items($data['competencyids']);
        } else if (isset($data['competencies'])) {
            $idnumbers = explode(',', $data['competencies']);
            $competency_ids = competency::repository()
                ->select('id')
                ->where_in('idnumber', $idnumbers)
                ->get()
                ->pluck('id');
            $instance->add_items($competency_ids);
        } else {
            // None specified.
            throw new coding_exception("Must specify either competencyids or competencies when creating {$instance->get_plugin_type()} criteria.");
        }
    }
}