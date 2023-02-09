<?php
/**
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
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 * @package totara_competency
 * @subpackage test
 */

use core\orm\query\builder;
use core_phpunit\testcase;
use totara_competency\entity\assignment as assignment_entity;
use totara_competency\min_proficiency_override_for_assignments;
use totara_core\advanced_feature;
use totara_hierarchy\entity\scale_value;
use totara_webapi\phpunit\webapi_phpunit_helper;

defined('MOODLE_INTERNAL') || die();


/**
 * @see update_min_proficiency_override_for_assignments::resolve()
 * @group totara_competency
 */
class totara_competency_webapi_resolver_mutation_update_min_proficiency_override_for_assignments_testcase extends testcase {

    use webapi_phpunit_helper;

    private const MUTATION = 'totara_competency_update_min_proficiency_override_for_assignments';

    public function test_successful_ajax_call(): void {
        $assignment1 = $this->generator()->assignment_generator()->create_self_assignment();
        /** @var assignment_entity $assignment1 */
        $assignment1 = assignment_entity::repository()->find($assignment1->id);

        $assignment2 = $this->generator()->assignment_generator()->create_self_assignment();
        /** @var assignment_entity $assignment2 */
        $assignment2 = assignment_entity::repository()->find($assignment2->id);

        $assignment3 = $this->generator()->assignment_generator()->create_self_assignment();
        /** @var assignment_entity $assignment3 */
        $assignment3 = assignment_entity::repository()->find($assignment3->id);

        // One and two share the same competency.
        $assignment2->competency_id = $assignment1->competency_id;
        $assignment2->save();

        // Three shares the same framework (through competency).
        $assignment3->competency->frameworkid = $assignment1->competency->frameworkid;
        $assignment3->competency->save();

        /** @var scale_value $new_min_scale_value_id */
        $new_min_scale_value = $assignment1->competency->scale->values->find(function (scale_value $scale_value) {
            return $scale_value->id !== $scale_value->scale->minproficiencyid;
        });

        $user = self::getDataGenerator()->create_user();
        $user_role = builder::table('role')->where('shortname', 'user')->value('id');
        assign_capability('totara/competency:manage_assignments', CAP_ALLOW, $user_role, context_system::instance()->id);

        self::setUser($user);

        $input_args = [
            'scale_value_id' => $new_min_scale_value->id,
            'assignment_ids' => [$assignment1->id, $assignment2->id, $assignment3->id],
        ];

        $result = $this->parsed_graphql_operation(self::MUTATION, ['input' => $input_args]);
        $this->assert_webapi_operation_successful($result);
        $result = $this->get_webapi_operation_data($result)['assignments'];

        self::assertEquals(array_column($result, 'id'), [$assignment1->id, $assignment2->id, $assignment3->id]);
        self::assertEquals($assignment1->refresh()->minproficiencyid, $new_min_scale_value->id);
        self::assertEquals($assignment2->refresh()->minproficiencyid, $new_min_scale_value->id);
        self::assertEquals($assignment3->refresh()->minproficiencyid, $new_min_scale_value->id);


        $input_args = [
            'scale_value_id' => null,
            'assignment_ids' => [$assignment1->id, $assignment2->id, $assignment3->id],
        ];

        $result = $this->parsed_graphql_operation(self::MUTATION, ['input' => $input_args]);
        $this->assert_webapi_operation_successful($result);
        $result = $this->get_webapi_operation_data($result)['assignments'];

        self::assertEquals(array_column($result, 'id'), [$assignment1->id, $assignment2->id, $assignment3->id]);
        self::assertNull($assignment1->refresh()->minproficiencyid);
        self::assertNull($assignment2->refresh()->minproficiencyid);
        self::assertNull($assignment3->refresh()->minproficiencyid);
    }

    public function test_failed_ajax_query(): void {
        $assignment1 = $this->generator()->assignment_generator()->create_self_assignment();
        /** @var assignment_entity $assignment1 */
        $assignment1 = assignment_entity::repository()->find($assignment1->id);

        /** @var scale_value $new_min_scale_value_id */
        $new_min_scale_value = $assignment1->competency->scale->values->find(function (scale_value $scale_value) {
            return $scale_value->id !== $scale_value->scale->minproficiencyid;
        });

        $input_args = [
            'scale_value_id' => $new_min_scale_value->id,
            'assignment_ids' => [$assignment1->id],
        ];

        $user = self::getDataGenerator()->create_user();
        self::setUser($user);

        $result = $this->parsed_graphql_operation(self::MUTATION, ['input' => $input_args]);
        $this->assert_webapi_operation_failed($result, 'Sorry, but you do not currently have permissions to do that (Manage competency assignments)');

        $user_role = builder::table('role')->where('shortname', 'user')->value('id');
        assign_capability('totara/competency:manage_assignments', CAP_ALLOW, $user_role, context_system::instance()->id);

        $feature = 'competency_assignment';
        advanced_feature::disable($feature);
        $result = $this->parsed_graphql_operation(self::MUTATION, ['input' => $input_args]);
        $this->assert_webapi_operation_failed($result, 'Feature competency_assignment is not available.');
        advanced_feature::enable($feature);

        $input_args = [
            'scale_value_id' => 100,
            'assignment_ids' => [$assignment1->id],
        ];

        $result = $this->parsed_graphql_operation(self::MUTATION, ['input' => $input_args]);
        $this->assert_webapi_operation_failed($result, min_proficiency_override_for_assignments::SCALE_VALUE_DOES_NOT_EXIST);
    }

    /**
     * Get hierarchy specific generator
     *
     * @return totara_competency_generator|component_generator_base
     */
    protected function generator() {
        return self::getDataGenerator()->get_plugin_generator('totara_competency');
    }

}
