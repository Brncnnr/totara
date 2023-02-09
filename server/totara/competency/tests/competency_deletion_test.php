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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package totara_competency
 */

use core\orm\entity\entity;
use core_phpunit\testcase;
use totara_competency\entity\achievement_via;
use totara_competency\entity\assignment;
use totara_competency\entity\competency_achievement;
use totara_competency\entity\configuration_change;
use totara_competency\entity\configuration_history;
use totara_competency\entity\pathway;
use totara_competency\entity\pathway_achievement;
use totara_competency\entity\scale_aggregation;
use totara_hierarchy\entity\assignment_availability;
use totara_hierarchy\entity\competency as competency_entity;

global $CFG;
require_once($CFG->dirroot . '/totara/hierarchy/prefix/competency/lib.php');

/**
 * Tests that deleting a competency from the hierarchy deletes all child data.
 *
 * @group totara_competency
 */
class totara_competency_competency_deletion_testcase extends testcase {

    /** @var competency_entity */
    private $comp_1;

    /** @var competency_entity */
    private $comp_2;

    protected function setUp(): void {
        $this->comp_1 = $this->generator()->create_competency('A');
        $this->comp_2 = $this->generator()->create_competency('B');
    }

    protected function tearDown(): void {
        $this->comp_1 = null;
        $this->comp_2 = null;
    }

    /**
     * Test self assignment availability options for a competency are deleted too.
     */
    public function test_assign_availability_deleted() {
        $availability_1 = (new assignment_availability(['comp_id' => $this->comp_1->id, 'availability' => 0]))->save();
        $availability_2 = (new assignment_availability(['comp_id' => $this->comp_2->id, 'availability' => 0]))->save();

        $this->assert_exists($availability_1);
        $this->assert_exists($availability_2);

        $this->delete($this->comp_1);

        $this->assert_not_exists($availability_1);
        $this->assert_exists($availability_2);

        $this->delete($this->comp_2);

        $this->assert_not_exists($availability_1);
        $this->assert_not_exists($availability_2);
    }

    /**
     * Test scale aggregation options for a competency are deleted too.
     */
    public function test_scale_aggregation_deleted() {
        $scale_agg_1 = (new scale_aggregation(['competency_id' => $this->comp_1->id, 'type' => 'A']))->save();
        $scale_agg_2 = (new scale_aggregation(['competency_id' => $this->comp_2->id, 'type' => 'B']))->save();

        $this->assert_exists($scale_agg_1);
        $this->assert_exists($scale_agg_2);

        $this->delete($this->comp_1);

        $this->assert_not_exists($scale_agg_1);
        $this->assert_exists($scale_agg_2);

        $this->delete($this->comp_2);

        $this->assert_not_exists($scale_agg_1);
        $this->assert_not_exists($scale_agg_2);
    }

    /**
     * Test configuration log and history for a competency are deleted too.
     */
    public function test_configuration_logs_deleted() {
        $config_change_attributes = [
            'assignment_id' => null,
            'time_changed' => 0,
            'change_type' => 0,
        ];
        $config_change_1 = (new configuration_change(array_merge($config_change_attributes, ['competency_id' => $this->comp_1->id])))
            ->save();
        $config_change_2 = (new configuration_change(array_merge($config_change_attributes, ['competency_id' => $this->comp_2->id])))
            ->save();

        $config_history_attributes = [
            'assignment_id' => null,
            'active_from' => 0,
            'active_to' => 0,
            'configuration' => 0,
        ];
        $config_history_1 = (new configuration_history(array_merge($config_history_attributes, ['competency_id' => $this->comp_1->id])))
            ->save();
        $config_history_2 = (new configuration_history(array_merge($config_history_attributes, ['competency_id' => $this->comp_2->id])))
            ->save();

        $this->assert_exists($config_change_1);
        $this->assert_exists($config_change_2);
        $this->assert_exists($config_history_1);
        $this->assert_exists($config_history_2);

        $this->delete($this->comp_1);

        $this->assert_not_exists($config_change_1);
        $this->assert_not_exists($config_history_1);
        $this->assert_exists($config_change_2);
        $this->assert_exists($config_history_2);

        $this->delete($this->comp_2);

        $this->assert_not_exists($config_change_1);
        $this->assert_not_exists($config_change_2);
        $this->assert_not_exists($config_history_1);
        $this->assert_not_exists($config_history_2);
    }

    /**
     * Test pathways, achievement and pathway achievement records for a competency are deleted too.
     */
    public function test_pathways_and_achievements_deleted() {
        $path_attributes = [
            'sortorder' => 0,
            'path_type' => 'learning_plan',
            'status' => 0,
            'pathway_modified' => 0,
        ];
        $path_1 = (new pathway(array_merge($path_attributes, ['competency_id' => $this->comp_1->id])))->save();
        $path_2 = (new pathway(array_merge($path_attributes, ['competency_id' => $this->comp_2->id])))->save();

        $path_achievement_attributes = [
            'user_id' => 0,
            'scale_value_id' => 0,
            'date_achieved' => 0,
            'last_aggregated' => 0,
        ];
        $path_achievement_1 = (new pathway_achievement(array_merge($path_achievement_attributes, ['pathway_id' => $path_1->id])))
            ->save();
        $path_achievement_2 = (new pathway_achievement(array_merge($path_achievement_attributes, ['pathway_id' => $path_2->id])))
            ->save();

        $user = $this->getDataGenerator()->create_user();
        $assignment = $this->generator()->assignment_generator()->create_user_assignment($this->comp_1->id, $user->id);
        $another_assignment = $this->generator()->assignment_generator()->create_user_assignment($this->comp_2->id, $user->id);

        $achievement_attributes = [
            'user_id' => $user->id,
            'assignment_id' => $assignment->id,
            'scale_value_id' => 1,
            'proficient' => 1,
            'status' => 0,
            'time_created' => 0,
            'time_status' => 0,
            'time_proficient' => 0,
            'time_scale_value' => 0,
            'last_aggregated' => 0,
        ];
        $achievement_1 = (new competency_achievement(array_merge($achievement_attributes, ['competency_id' => $this->comp_1->id])))
            ->save();
        $achievement_2 = (new competency_achievement(array_merge($achievement_attributes, [
            'competency_id' => $this->comp_2->id,
            'assignment_id' => $another_assignment->id,
        ])))
            ->save();

        $achievement_via_1 = (new achievement_via([
            'comp_achievement_id' => $achievement_1->id,
            'pathway_achievement_id' => $path_achievement_1->id
        ]))->save();
        $achievement_via_2 = (new achievement_via([
            'comp_achievement_id' => $achievement_2->id,
            'pathway_achievement_id' => $path_achievement_2->id
        ]))->save();

        $this->assert_exists($path_1);
        $this->assert_exists($path_achievement_1);
        $this->assert_exists($achievement_1);
        $this->assert_exists($achievement_via_1);
        $this->assert_exists($path_2);
        $this->assert_exists($path_achievement_2);
        $this->assert_exists($achievement_2);
        $this->assert_exists($achievement_via_2);

        $this->delete($this->comp_1);

        $this->assert_not_exists($path_1);
        $this->assert_not_exists($path_achievement_1);
        $this->assert_not_exists($achievement_1);
        $this->assert_not_exists($achievement_via_1);
        $this->assert_exists($path_2);
        $this->assert_exists($path_achievement_2);
        $this->assert_exists($achievement_2);
        $this->assert_exists($achievement_via_2);

        $this->delete($this->comp_2);

        $this->assert_not_exists($path_1);
        $this->assert_not_exists($path_achievement_1);
        $this->assert_not_exists($achievement_1);
        $this->assert_not_exists($achievement_via_1);
        $this->assert_not_exists($path_2);
        $this->assert_not_exists($path_achievement_2);
        $this->assert_not_exists($achievement_2);
        $this->assert_not_exists($achievement_via_2);
    }

    public function test_it_removes_assignments_when_competency_deleted() {
        $comps = $this->generate_competencies();

        $ids = competency_entity::repository()->where('path', 'like_starts_with', '/' . $comps[0]->id)
            ->get()->pluck('id');

        $hierarchy = new competency();
        $hierarchy->delete_hierarchy_item($comps[0]->id);

        $this->assertTrue(assignment::repository()->where('competency_id', $ids)->does_not_exist());

        $this->assertTrue(assignment::repository()->where('competency_id', $comps[3]->id)->exists());
    }

    /**
     * Create a few competencies with knows names to test search
     */
    protected function generate_competencies() {
        $comps = [];

        $fw = $this->generator()->hierarchy_generator()->create_comp_frame([]);
        $type = $this->generator()->hierarchy_generator()->create_comp_type(['idnumber' => 'type1']);

        $comps[] = $comp_one = $this->generator()->create_competency(null, $fw->id, [
            'shortname' => 'acc',
            'fullname' => 'Accounting',
            'description' => 'Counting profits',
            'idnumber' => 'accc',
            'typeid' => $type,
        ]);

        $comps[] = $comp_two = $this->generator()->create_competency(null, $fw->id, [
            'shortname' => 'c-chef',
            'fullname' => 'Chef proficiency',
            'description' => 'Bossing around',
            'idnumber' => 'cook-chef-c',
            'typeid' => $type,
            'parentid' => $comp_one->id,
        ]);

        $comps[] = $comp_three = $this->generator()->create_competency(null, $fw->id, [
            'shortname' => 'des',
            'fullname' => 'Designing interiors',
            'description' => 'Decorating things',
            'idnumber' => 'des',
            'parentid' => $comp_two->id,
            'typeid' => $type,
        ]);

        $comps[] = $comp_four =  $this->generator()->create_competency(null, $fw->id, [
            'shortname' => 'c-baker',
            'fullname' => 'Baking skill-set',
            'description' => 'Baking amazing things',
            'idnumber' => 'cook-baker',
            'typeid' => $type,
        ]);

        // Create an assignment for a competency
        foreach ($comps as $competency) {
            $this->generator()->assignment_generator()->create_user_assignment($competency->id, null, ['type' => assignment::TYPE_ADMIN]);
        }

        return $comps;
    }

    /**
     * Check that the specified entity still exists.
     *
     * @param entity $entity
     */
    private function assert_exists(entity $entity) {
        $this->assertTrue($entity::repository()->where('id', $entity->id)->exists());
    }

    /**
     * Check that the specified entity no longer exists.
     *
     * @param entity $entity
     */
    private function assert_not_exists(entity $entity) {
        $this->assertFalse($entity::repository()->where('id', $entity->id)->exists());
    }

    /**
     * Delete the competency.
     *
     * @param competency_entity $competency
     */
    private function delete(competency_entity $competency) {
        $hierarchy_comp = new competency();
        $hierarchy_comp->delete_hierarchy_item($competency->id);
    }

    /**
     * Get hierarchy specific generator
     *
     * @return \totara_competency\testing\generator
     */
    protected function generator() {
        return $this->getDataGenerator()->get_plugin_generator('totara_competency');
    }
}
