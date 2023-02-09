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
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 * @package pathway_criteria_group
 */

use \totara_criteria\criterion;
use \pathway_criteria_group\criteria_group;
use \hierarchy_competency\event\scale_value_deleted;

/**
 * @group totara_competency
 */
class scale_value_test extends \advanced_testcase {

    /**
     * Generated data used for tests.
     * @var array Data stored and used.
     */
    private $data;

    /**
     * Test data Generator.
     * @var \core\testing\generator
    */
    private $generator;
    /**
     * Test competency data Generator.
     * @var \totara_competency\testing\generator
    */
    private $competency_generator;

    /**
     * Test Delete pathway when an unassigned scale value is deleted.
     *
     * @return void
     */
    public function test_deletes_scale_value_pathway(): void {
        $scale_value = $this->data['scale']->values->shift();
        $pathways_deleted = criteria_group::delete_pathways_with_scale_value_id($scale_value->id);

        $this->assertEquals(3, count($pathways_deleted));
        $next_scale_value = $this->data['scale']->values->shift();
        $pathways = criteria_group::get_pathway_count_by_scale_value_id($next_scale_value->id);

        $this->assertEquals(3, $pathways);
    }

    /**
     * Test scale value delete observer is called when deleting scale value.
     *
     * @return void
     */
    public function test_delete_event_observer_is_called(): void {
        $scale_value_id = $this->data['scale']->defaultid;
        $pathways = criteria_group::get_pathway_count_by_scale_value_id($scale_value_id);
        $this->assertEquals(3, $pathways);

        $scale_value = $this->data['scale']->values->find('id', $scale_value_id)->to_array();
        $scale_value_class = (object) $scale_value;
        scale_value_deleted::create_from_instance($scale_value_class)->trigger();

        $pathways = criteria_group::get_pathway_count_by_scale_value_id($scale_value_id);
        $this->assertEquals(0, $pathways);
    }

    /**
     * Setup data set.
     *
     * @return void
     */
    protected function setUp(): void {
        $this->setup_generators();
        $scale_data = [
            'name' => 'Scale value delete scale',
            'description' => 'This scale is created to test deleting un-achieved scale values.',
            'values' => [
                [
                    'name' => 'Value 1',
                    'proficient' => false,
                    'default' => true,
                    'sortorder' => 1,
                ],
                [
                    'name' => 'Value 2',
                    'proficient' => false,
                    'default' => false,
                    'sortorder' => 2,
                ],
                [
                    'name' => 'Value 3',
                    'proficient' => true,
                    'default' => false,
                    'sortorder' => 3,
                ],
            ]
        ];

        $this->set_and_create_scale($scale_data);
        $framework_data = [
            'name' => 'Deletable Scale value framework',
            'description' => 'You should be able to delete this scale value though, it\'s linked to criteria group pathways',
            'scale' => $this->data['scale'],
        ];
        $this->set_and_create_framework($framework_data);

        for ($i = 0; $i < 3; $i++) {
            $competency_data = [
                'name' => "Comp Delete Scale Value $i",
                'competency_framework' => $this->data['competency_framework'],
            ];
            $this->set_and_create_competency($competency_data);
        }

        $prefix = 'Course ';
        for ($i = 0; $i < 2; $i++) {
            $course_data = [
                'shortname' => $prefix . $i,
                'fullname' => $prefix . $i,
                'enablecompletion' => true,
            ];
            $this->set_and_create_course($course_data);
        }
        $course_ids = [];

        foreach ($this->data['courses'] as $course) {
            $course_ids[] = $course->id;
        }

        foreach ($this->data['competency_list'] as $competency) {
            $this->set_and_create_criterion(
                [
                    'criterion_class' => 'coursecompletion',
                    'competency' => $competency,
                    'aggregation_method' => criterion::AGGREGATE_ALL,
                    'item_ids' => $course_ids,
                ]
            );
        }

        /** @var \totara_hierarchy\entity\scale $scale */

        foreach ($this->data['competency_list'] as $competency) {
                $criteria_group_data = [
                    'competency' => $competency,
                    'criterion' => $this->data['criterion_list'][$competency->id],
                ];
                foreach ($this->data['scale']->values as $value) {
                    $criteria_group_data['scale_value'] = $value;
                    $this->set_and_create_criteria_group($criteria_group_data);
                }
        }
    }

    /**
     * Sets up generators.
     *
     * @return void
     */
    private function setup_generators(): void {
        $this->generator = $this->getDataGenerator();
        $this->competency_generator = $this->generator->get_plugin_generator('totara_competency');
    }

    /**
     * Setup Scale Data.
     * @param array $data {
     *      Scale data params passed
     *      @type string $name Scale Name.
     *      @type string $description Scale Description.
     *      @type array $values {
     *      List of Scale Values
     *          @type array {
     *              Single Scale Value Array
     *              @type string $name Value Name.
     *              @type string $proficient Does value mark proficiency.
     *              @type bool $default Is value default value.
     *              @type string $sortoder Sort order.
     *          }
     *      }
     * }
     * @return void
     */
    private function set_and_create_scale(array $data): void {
        if (empty($data['name'] || $data['values'])) {
            throw new coding_exception('Name or Values for scale not defined');
        }
        $this->data['scale'] = $this->competency_generator->create_scale(
            $data['name'],
            $data['description'],
            $data['values']
        );
    }

    /**
     * Set and create Framework.
     *
     * @param array $data
     *
     * @return void
     */
    private function set_and_create_framework(array $data): void {
        $this->data['competency_framework'] = $this->competency_generator->create_framework(
            $data['scale'],
            $data['name'],
            $data['description']
        );
    }

    /**
     * Set and create competency.
     *
     * @param array $data
     *
     * @return void
     */
    private function set_and_create_competency(array $data): void {
        if (!isset($this->data['competency_list'])) {
            $this->data['competency_list'] = [];
        }
        $this->data['competency_list'][] = $this->competency_generator->create_competency(
            $data['name'],
            $data['competency_framework']
        );
    }

    /**
     * Set and create courses.
     *
     * @param array $data
     *
     * @return void
     */
    private function set_and_create_course(array $data): void {
        if (!isset($this->data['courses'])) {
            $this->data['courses'] = [];
        }
        $this->data['courses'][] = $this->generator->create_course($data);
    }

    /**
     * Set and create totara criterions.
     *
     * @param array $data
     *
     * @return void
     */
    private function set_and_create_criterion(array $data): void {
        $data['required_items'] = $data['required_items'] ?? 1;
        $data['aggregation_method'] = $data['aggregation_method'] ?? criterion::AGGREGATE_ALL;

        if (!isset($this->data['criterion_list'])) {
            $this->data['criterion_list'] = [];
        }

        $this->data['criterion_list'][$data['competency']->id] = $this->competency_generator->create_criterion(
            $data['criterion_class'],
            $data['competency'],
            $data['aggregation_method'],
            $data['item_ids'],
            $data['required_items']
        );
    }

    /**
     * Set and create a criteria group.
     *
     * @param array $data
     *
     * @return void
     * @throws coding_exception
     */
    private function set_and_create_criteria_group(array $data): void {
        $this->validate_create_criteria_group_input($data);

        if (!isset($this->data['criteria_group_list'])) {
            $this->data['criteria_group_list'] = [];
        }
        $this->data['criteria_group_list'][] = $this->competency_generator->create_criteria_group(
            $data['competency'],
            $data['criterion'],
            $data['scale_value']
        );
    }

    /**
     * Validate criteria group creation input.
     *
     * @param array $data
     *
     * @return void
     *
     * @throws coding_exception
     */
    private function validate_create_criteria_group_input(array $data): void {
        if ($data['competency'] instanceof competency) {
            throw new coding_exception('Competency should of class type competency');
        }

        if (!is_array($data['criterion']) && !$data['criterion'] instanceof criterion) {
            throw new coding_exception('criterion should of class type criterion');
        }

        if (is_array($data['criterion'])) {
            foreach ($data['criterion'] as $criterion) {
                if (!$criterion instanceof criterion) {
                    throw new coding_exception('criterion should of class type criterion');
                }
            }
        }
    }

    /**
     * Empties the class properties.
     *
     * @return void
     */
    protected function tearDown(): void {
        $this->data = null;
        $this->generator = null;
        $this->competency_generator = null;
    }
}
