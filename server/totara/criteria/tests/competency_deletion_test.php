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
 * @package totara_criteria
 */

use core\orm\entity\entity;
use pathway_criteria_group\entity\criteria_group;
use pathway_criteria_group\entity\criteria_group_criterion;
use totara_criteria\entity\criteria_item;
use totara_criteria\entity\criteria_item_record;
use totara_criteria\entity\criterion;

global $CFG;
require_once($CFG->dirroot . '/totara/hierarchy/prefix/competency/lib.php');

/**
 * @group totara_competency
 */
class totara_criteria_competency_deletion_testcase extends advanced_testcase {

    /**
     * Tests that associated criteria records are deleted upon deletion of a competency.
     */
    public function test_criteria_deleted() {
        /** @var \totara_competency\testing\generator $competency_generator */
        $competency_generator = $this->getDataGenerator()->get_plugin_generator('totara_competency');
        /** @var \totara_criteria\testing\generator $criteria_generator */
        $criteria_generator = $this->getDataGenerator()->get_plugin_generator('totara_criteria');

        $comp_1 = $competency_generator->create_competency();
        $comp_2 = $competency_generator->create_competency();

        $criterion_1 = $criteria_generator->create_coursecompletion(['courseids' => [0]]);
        $criterion_2 = $criteria_generator->create_coursecompletion(['courseids' => [0]]);

        $criterion_item_1 = criteria_item::repository()->where('criterion_id', $criterion_1->get_id())->one();
        $criterion_item_2 = criteria_item::repository()->where('criterion_id', $criterion_2->get_id())->one();

        $criterion_record_attributes = ['user_id' => 0, 'criterion_met' => 0, 'timeevaluated' => 0];
        $criterion_record_1 = (new criteria_item_record(array_merge($criterion_record_attributes, [
            'criterion_item_id' => $criterion_item_1->id
        ])))->save();
        $criterion_record_2 = (new criteria_item_record(array_merge($criterion_record_attributes, [
            'criterion_item_id' => $criterion_item_2->id
        ])))->save();

        $criteria_group_1 = $competency_generator->create_criteria_group($comp_1, $criterion_1);
        $criteria_group_2 = $competency_generator->create_criteria_group($comp_2, $criterion_2);

        $group_entity_1 = new criteria_group($criteria_group_1->get_path_instance_id());
        $group_entity_2 = new criteria_group($criteria_group_2->get_path_instance_id());

        $group_criterion_1 = criteria_group_criterion::repository()->where('criteria_group_id', $group_entity_1->id)->one();
        $group_criterion_2 = criteria_group_criterion::repository()->where('criteria_group_id', $group_entity_2->id)->one();

        $criterion_entity_1 = new criterion($criterion_1->get_id());
        $criterion_entity_2 = new criterion($criterion_2->get_id());

        $this->assert_exists($group_entity_1);
        $this->assert_exists($group_entity_2);
        $this->assert_exists($group_criterion_1);
        $this->assert_exists($group_criterion_2);
        $this->assert_exists($criterion_entity_1);
        $this->assert_exists($criterion_entity_2);
        $this->assert_exists($criterion_item_1);
        $this->assert_exists($criterion_item_2);
        $this->assert_exists($criterion_record_1);
        $this->assert_exists($criterion_record_2);

        $hierarchy_comp = new competency();
        $hierarchy_comp->delete_hierarchy_item($comp_1->id);

        $this->assert_not_exists($group_entity_1);
        $this->assert_not_exists($group_criterion_1);
        $this->assert_not_exists($criterion_entity_1);
        $this->assert_not_exists($criterion_item_1);
        $this->assert_not_exists($criterion_record_1);
        $this->assert_exists($group_entity_2);
        $this->assert_exists($group_criterion_2);
        $this->assert_exists($criterion_entity_2);
        $this->assert_exists($criterion_item_2);
        $this->assert_exists($criterion_record_2);

        $hierarchy_comp->delete_hierarchy_item($comp_2->id);

        $this->assert_not_exists($group_entity_1);
        $this->assert_not_exists($group_entity_2);
        $this->assert_not_exists($group_criterion_1);
        $this->assert_not_exists($group_criterion_2);
        $this->assert_not_exists($criterion_entity_1);
        $this->assert_not_exists($criterion_entity_2);
        $this->assert_not_exists($criterion_item_1);
        $this->assert_not_exists($criterion_item_2);
        $this->assert_not_exists($criterion_record_1);
        $this->assert_not_exists($criterion_record_2);
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

}
