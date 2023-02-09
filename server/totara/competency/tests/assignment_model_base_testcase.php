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
 * @category test
 */

use totara_competency\entity\assignment as assignment_entity;
use totara_competency\models\assignment as assignment_model;
use totara_competency\user_groups;

defined('MOODLE_INTERNAL') || die();

abstract class assignment_model_base_testcase extends advanced_testcase {

    protected function create_active_user_assignment(int $competency_id, int $user_id): assignment_model {
        $type = assignment_entity::TYPE_ADMIN;
        $user_group_type = user_groups::USER;
        $status = assignment_entity::STATUS_ACTIVE;

        return assignment_model::create($competency_id, $type, $user_group_type, $user_id, $status);
    }

    protected function create_data() {
        $generator = $this->getDataGenerator();
        $data = new class() {
            public $fw1;
            public $user1, $user2;
            public $comp1, $comp2;
        };
        $data->fw1 = $this->generator()->hierarchy_generator()->create_comp_frame([]);

        $data->comp1 = $this->generator()->create_competency(null, $data->fw1->id, [
            'shortname' => 'c-chef',
            'fullname' => 'Chef proficiency',
            'description' => 'Bossing around',
            'idnumber' => 'cook-chef-c',
        ]);

        $data->comp2 = $this->generator()->create_competency(null, $data->fw1->id, [
            'shortname' => 'c-chef',
            'fullname' => 'Chef proficiency',
            'description' => 'Bossing around',
            'idnumber' => 'cook-chef-c',
        ]);

        $data->user1 = $generator->create_user();
        $data->user2 = $generator->create_user();

        return $data;
    }

    /**
     * Get hierarchy specific generator
     *
     * @return \totara_competency\testing\generator
     */
    protected function generator() {
        return \totara_competency\testing\generator::instance();
    }

}