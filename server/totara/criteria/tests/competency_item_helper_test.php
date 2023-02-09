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
 * @package totara_criteria
 */

use core\orm\collection;
use core\orm\query\table;
use core_phpunit\testcase;
use criteria_childcompetency\childcompetency;
use pathway_manual\models\roles\manager;
use totara_criteria\competency_item_helper;
use totara_criteria\criterion;
use totara_criteria\entity\criteria_item as item_entity;
use totara_criteria\entity\criteria_metadata as metadata_entity;
use totara_criteria\hook\criteria_achievement_changed;
use totara_criteria\hook\criteria_validity_changed;
use totara_hierarchy\entity\scale;

/**
 * @group totara_competency
 */
class totara_criteria_competency_item_helper_testcase extends testcase {

    public function test_achievement_updated() {
        /** @var \totara_competency\testing\generator $competency_generator */
        $competency_generator = $this->getDataGenerator()->get_plugin_generator('totara_competency');
        /** @var \totara_criteria\testing\generator $criteria_generator */
        $criteria_generator = $this->getDataGenerator()->get_plugin_generator('totara_criteria');

        $framework = $competency_generator->create_framework();

        // Create 2 competencies, each with 1 child. First one has childcompetency criteria
        $competencies = [];
        foreach (['Comp A', 'Comp B'] as $name) {
            $competencies[$name] = $competency_generator->create_competency($name, $framework);

            $childname = $name . '-1';
            $competencies[$childname] = $competency_generator->create_competency(
                $childname,
                $framework,
                ['parentid' => $competencies[$name]->id]
            );
        }
        $criterion = $criteria_generator->create_childcompetency(['competency' => $competencies['Comp A']->id]);

        $user = $this->getDataGenerator()->create_user();

        // Now for the test :- We only check that the correct hooks are executed
        $sink = $this->redirectHooks();

        // First for child competency of parent without criteria
        competency_item_helper::achievement_updated($user->id, $competencies['Comp B-1']->id);
        $this->assertSame(0, $sink->count());

        // Parent without criteria
        competency_item_helper::achievement_updated($user->id, $competencies['Comp B']->id);
        $this->assertSame(0, $sink->count());

        // Child competency of parent with criteria
        competency_item_helper::achievement_updated($user->id, $competencies['Comp A-1']->id);
        $this->verify_hook($sink, [$user->id => [$criterion->get_id()]]);
        $sink->clear();

        // Parent with criteria
        competency_item_helper::achievement_updated($user->id, $competencies['Comp A']->id);
        $this->assertSame(0, $sink->count());

        $sink->close();
    }


    public function test_configuration_changed() {
        $event_sink = $this->redirectEvents();
        $hook_sink = $this->redirectHooks();

        /** @var \totara_competency\testing\generator $competency_generator */
        $competency_generator = $this->getDataGenerator()->get_plugin_generator('totara_competency');
        /** @var \totara_criteria\testing\generator $criteria_generator */
        $criteria_generator = $this->getDataGenerator()->get_plugin_generator('totara_criteria');

        $course1 = $this->getDataGenerator()->create_course(['enablecompletion' => true]);
        $course2 = $this->getDataGenerator()->create_course(['enablecompletion' => true]);

        $parent = $competency_generator->create_competency('Comp A');
        $child = $competency_generator->create_competency('Comp A-1', null, ['parentid' => $parent->id]);

        /** @var scale $scale */
        $scale = $parent->scale;
        $non_proficient_scalevalue = $scale->default_value;
        $proficient_scalevalue = $scale->min_proficient_value;

        $parent_criterion = $criteria_generator->create_childcompetency(['competency' => $parent->id]);
        $parent_pw = $competency_generator->create_criteria_group($parent, [$parent_criterion], $non_proficient_scalevalue);
        $this->assertFalse($parent_pw->is_valid());

        $hook_sink->clear();

        // First add a non-proficient pw to child - should have no effect on parent's validity
        $child_criterion = $criteria_generator->create_coursecompletion(['courseids' => [$course1->id]]);
        $child_pw = $competency_generator->create_criteria_group($child, [$child_criterion], $non_proficient_scalevalue);
        $hook_sink->clear();

        // Child competency - still no way for user to become proficient - should not result in validity change
        competency_item_helper::configuration_changed($child->id);
        $this->assertSame(0, $hook_sink->count());

        // Now add a pathway to the child that will allow the user to become proficient
        $child_pw2 = $competency_generator->create_manual($child->id, [manager::class]);

        $hook_sink->clear();
        competency_item_helper::configuration_changed($child->id);

        $hooks = $hook_sink->get_hooks();
        $this->assertSame(1, count($hooks));
        $hook = reset($hooks);
        $this->assertTrue($hook instanceof criteria_validity_changed);
        $this->assertEqualsCanonicalizing([$parent_criterion->get_id()], $hook->get_criteria_ids());

        $hook_sink->close();
        $event_sink->close();
    }


    /**
     * Get the items linked to childcompetency criteria on the specified competency
     * @param int $competency_id
     * @return collection
     */
    private function get_items(int $competency_id): collection {
        $item_type = (new childcompetency())->get_items_type();

        return item_entity::repository()
            ->join((new table(metadata_entity::TABLE))->as('metadata'), 'criterion_id', '=', 'criterion_id')
            ->where('item_type', $item_type)
            ->where('metadata.metakey', criterion::METADATA_COMPETENCY_KEY)
            ->where('metadata.metavalue', $competency_id)
            ->get();
    }

    /**
     * Verify the executed hook
     *
     * @param \core_phpunit\hook_sink $sink
     * @param array $expected_criteria_ids
     */
    private function verify_hook(\core_phpunit\hook_sink $sink, array $expected_criteria_ids) {
        $this->assertSame(1, $sink->count());
        $hooks = $sink->get_hooks();
        /** @var criteria_achievement_changed $hook */
        $hook = reset($hooks);
        $this->assertInstanceOf(criteria_achievement_changed::class, $hook);
        $this->assertEqualsCanonicalizing($expected_criteria_ids, $hook->get_user_criteria_ids());
    }

}
