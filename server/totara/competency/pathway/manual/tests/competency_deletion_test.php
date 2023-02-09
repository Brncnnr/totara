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
 * @package pathway_manual
 */

use core\orm\entity\entity;
use pathway_manual\entity\pathway_manual;
use pathway_manual\models\roles\self_role;
use totara_competency\entity\pathway;

global $CFG;
require_once($CFG->dirroot . '/totara/hierarchy/prefix/competency/lib.php');

/**
 * @group totara_competency
 */
class pathway_manual_competency_deletion_testcase extends advanced_testcase {

    /**
     * Test that manual pathways, roles and ratings are deleted upon the deletion of their respective competency.
     */
    public function test_manual_pathway_deletion() {
        $user_1 = $this->getDataGenerator()->create_user();
        $user_2 = $this->getDataGenerator()->create_user();

        /** @var \totara_competency\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_competency');

        $comp_1 = $generator->create_competency();
        $comp_2 = $generator->create_competency();

        $comp1_manual_pathway = $generator->create_manual($comp_1, [self_role::class]);
        $comp2_manual_pathway = $generator->create_manual($comp_2, [self_role::class]);

        $rating1 = $generator->create_manual_rating($comp_1, $user_1, $user_1, self_role::class);
        $rating2 = $generator->create_manual_rating($comp_2, $user_2, $user_2, self_role::class);

        $path1_entity = new pathway($comp1_manual_pathway->get_id());
        $path2_entity = new pathway($comp2_manual_pathway->get_id());
        $pathway_manual1_entity = new pathway_manual($path1_entity->path_instance_id);
        $pathway_manual2_entity = new pathway_manual($path2_entity->path_instance_id);
        $role1 = $pathway_manual1_entity->roles()->one();
        $role2 = $pathway_manual2_entity->roles()->one();

        $this->assert_exists($path1_entity);
        $this->assert_exists($path2_entity);
        $this->assert_exists($rating1);
        $this->assert_exists($rating2);
        $this->assert_exists($pathway_manual1_entity);
        $this->assert_exists($pathway_manual2_entity);
        $this->assert_exists($role1);
        $this->assert_exists($role2);

        $hierarchy_comp = new \competency();
        $hierarchy_comp->delete_hierarchy_item($comp_1->id);

        $this->assert_not_exists($path1_entity);
        $this->assert_not_exists($rating1);
        $this->assert_not_exists($pathway_manual1_entity);
        $this->assert_not_exists($role1);
        $this->assert_exists($path2_entity);
        $this->assert_exists($rating2);
        $this->assert_exists($pathway_manual2_entity);
        $this->assert_exists($role2);

        $hierarchy_comp->delete_hierarchy_item($comp_2->id);

        $this->assert_not_exists($path1_entity);
        $this->assert_not_exists($path2_entity);
        $this->assert_not_exists($rating1);
        $this->assert_not_exists($rating2);
        $this->assert_not_exists($pathway_manual1_entity);
        $this->assert_not_exists($pathway_manual2_entity);
        $this->assert_not_exists($role1);
        $this->assert_not_exists($role2);
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
