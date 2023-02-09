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
 * @package totara_competency
 */

use core_phpunit\testcase;
use pathway_manual\models\roles\self_role;
use totara_competency\entity\achievement_via;
use totara_competency\entity\pathway_achievement;
use totara_competency\models\activity_log;
use totara_competency\entity\competency_achievement;
use totara_competency\entity\assignment;
use totara_hierarchy\entity\scale_value;

/**
 * @group totara_competency
 */
class totara_competency_models_activity_log_achieved_via_testcase extends testcase {

    public function test_get_methods() {
        // Dummy values
        $user_id = 100;
        $competency_id = 200;
        $achievement_time = 300;

        /** @var \totara_hierarchy\testing\generator $hierarchy_generator */
        $hierarchy_generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $hierarchy_generator->create_scale(
            'comp',
            [],
            [
                ['name' => 'Great', 'proficient' => 1, 'sortorder' => 1, 'default' => 0],
                ['name' => 'Good', 'proficient' => 1, 'sortorder' => 2, 'default' => 1],
                ['name' => 'Bad', 'proficient' => 0, 'sortorder' => 3, 'default' => 0],
            ]
        );

        $great = scale_value::repository()->where('name', '=', 'Great')->one();

        $assignment = new assignment();
        $assignment->competency_id = $competency_id;
        $assignment->user_group_id = 300;
        $assignment->user_group_type = 'test';
        $assignment->created_by = 400;
        $assignment->save();

        $achievement = new competency_achievement();
        $achievement->user_id = $user_id;
        $achievement->competency_id = $competency_id;
        $achievement->scale_value_id = $great->id;
        $achievement->proficient = 0;
        $achievement->status = 0;
        $achievement->time_created = $achievement_time;
        $achievement->time_status = $achievement_time;
        $achievement->assignment_id = $assignment->id;
        $achievement->save();

        $entry = activity_log\competency_achieved_via::load_by_entity($achievement);

        $this->assertEquals('Criteria met: . Achieved \'Great\' rating.', $entry->get_description());
        $this->assertEquals($achievement_time, $entry->get_date());
        $this->assertEquals($assignment->id, $entry->get_assignment()->get_id());

        // While the *scale value* is in the description. This entry doesn't have return whether or not
        // we're dealing with a proficient value or not. That is the responsibilty of the activity_log/competency_achievement
        // entries.
        $this->assertNull($entry->get_proficient_status());
    }

    /**
     * We want to make sure that if there are multiple pathways/criteria that contributed to an achievement
     * then they are all displayed correctly.
     */
    public function test_achieved_via_multiple_pathways() {
        /** @var \totara_competency\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_competency');
        /** @var \totara_criteria\testing\generator $criteria_generator */
        $criteria_generator = $this->getDataGenerator()->get_plugin_generator('totara_criteria');

        // Competency and user records required.
        $user = $this->getDataGenerator()->create_user(['firstname' => 'Test', 'lastname' => 'User']);
        $comp = $generator->create_competency();
        $dummy_assignment = (new assignment([
            'type' => 0,
            'competency_id' => $comp->id,
            'user_group_type' => 0,
            'user_group_id' => 0,
            'optional' => 0,
            'status' => 0,
            'created_by' => 0,
            'created_at' => 0,
            'updated_at' => 0,
            'archived_at' => 0,
            'expand' => 0,
        ]))->save();
        $parent_comp = $generator->create_competency();
        $scale_value = scale_value::repository()->select('id')->order_by('id')->first();
        $achievement = (new competency_achievement([
            'competency_id' => $comp->id,
            'user_id' => $user->id,
            'assignment_id' => $dummy_assignment->id,
            'scale_value_id' => $scale_value->id,
            'proficient' => 1,
            'status' => 1,
            'time_created' => 0,
            'time_status' => 0,
            'time_proficient' => 0,
            'time_scale_value' => 0,
            'last_aggregated' => 0,
        ]))->save();

        // Manual rating achievement
        /** @var self_role $rating_role */
        $rating_role = self_role::class;
        $manual_pathway = $generator->create_manual($comp->id, [$rating_role]);
        $manual_rating = $generator->create_manual_rating(
            $comp, $user->id, $user->id, $rating_role, $scale_value
        );
        $manual_pathway_achievement = (new pathway_achievement([
            'pathway_id' => $manual_pathway->get_id(),
            'user_id' => $user->id,
            'scale_value_id' => $scale_value->id,
            'date_achieved' => 0,
            'last_aggregated' => 0,
            'status' => 1,
            'related_info' => json_encode([
                'rating_id' => $manual_rating->id
            ]),
        ]))->save();
        (new achievement_via([
            'comp_achievement_id' => $achievement->id,
            'pathway_achievement_id' => $manual_pathway_achievement->id,
        ]))->save();

        // Learning plan achievement.
        $plan_pathway = $generator->create_learning_plan_pathway($comp->id);
        $plan_pathway_achievement = (new pathway_achievement([
            'pathway_id' => $plan_pathway->get_id(),
            'user_id' => $user->id,
            'scale_value_id' => $scale_value->id,
            'date_achieved' => 0,
            'last_aggregated' => 0,
            'status' => 1,
            'related_info' => '',
        ]))->save();
        (new achievement_via([
            'comp_achievement_id' => $achievement->id,
            'pathway_achievement_id' => $plan_pathway_achievement->id,
        ]))->save();

        // Criteria achievement.
        $criterion = $criteria_generator->create_childcompetency(['competency' => $parent_comp->id]);
        $criteria_pathway = $generator->create_criteria_group(
            $parent_comp->id,
            [$criterion],
            $scale_value->id
        );
        $criteria_plugins = array_keys(core_component::get_plugin_list('criteria'));
        $criteria_pathway_achievement = (new pathway_achievement([
            'pathway_id' => $criteria_pathway->get_id(),
            'user_id' => $user->id,
            'scale_value_id' => $scale_value->id,
            'date_achieved' => 0,
            'last_aggregated' => 0,
            'status' => 1,
            'related_info' => json_encode($criteria_plugins),
        ]))->save();
        (new achievement_via([
            'comp_achievement_id' => $achievement->id,
            'pathway_achievement_id' => $criteria_pathway_achievement->id,
        ]))->save();

        // What we expect to have in the criteria string.
        $expected_criteria_strings = [
            get_string('activity_log_rating_by', 'pathway_manual', [
                'name' => fullname($user),
                'role' => $rating_role::get_display_name(),
            ]),
            get_string('achievement_detail', 'pathway_learning_plan'),
        ];
        foreach ($criteria_plugins as $plugin) {
            $expected_criteria_strings[] = get_string('achievement_via', 'criteria_' . $plugin);
        }

        // There shouldn't be duplicates and only unique strings should be returned.
        $expected_criteria_strings = array_unique($expected_criteria_strings);
        sort($expected_criteria_strings);

        $model = activity_log\competency_achieved_via::load_by_entity($achievement);
        $reflection = new ReflectionClass($model);
        $criteria_strings_method = $reflection->getMethod('get_unique_criteria_met_strings');
        $criteria_strings_method->setAccessible(true);

        $this->assertEquals($expected_criteria_strings, $criteria_strings_method->invoke($model));

        // The actual description string should contain all these strings too!
        $description = $model->get_description();
        foreach ($expected_criteria_strings as $string) {
            $this->assertStringContainsString($string, $description);
        }

        // Check the comment string.
        $this->assertSame('', $model->get_comment());

        $manual_rating->comment = 'Test manual rating comment';
        $manual_rating->save();

        $model = activity_log\competency_achieved_via::load_by_entity($achievement);
        $this->assertSame('Test manual rating comment', $model->get_comment());
    }

}
