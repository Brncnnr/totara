<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @package pathway_perform_rating
 */

use core\orm\query\builder;
use mod_perform\entity\activity\element;
use mod_perform\entity\activity\participant_section;
use mod_perform\entity\activity\section_relationship;
use mod_perform\entity\activity\subject_instance;
use mod_perform\models\activity\participant_instance as participant_instance_model;
use mod_perform\models\activity\section_element;
use pathway_perform_rating\entity\perform_rating;
use pathway_perform_rating\models\perform_rating as perform_rating_model;
use pathway_perform_rating\testing\generator as pathway_peform_rating_generator;
use mod_perform\testing\generator as perform_generator;
use totara_core\relationship\relationship;

require_once __DIR__.'/perform_rating_base_testcase.php';

/**
 * @group pathway_perform_rating
 * @group totara_competency
 */
class pathway_perform_rating_perform_rating_foreign_keys_testcase extends perform_rating_base_testcase {

    private $data;

    private $rating;

    protected function setUp(): void {
        parent::setUp();
        $this->data = $this->create_data();
        $this->rating = perform_rating_model::create(
            $this->data->competency->id,
            null,
            $this->data->participant_instance1->id,
            $this->data->section_element->id
        );
    }

    protected function tearDown(): void {
        parent::tearDown();
        $this->data = null;
        $this->rating = null;
    }

    public function test_subject_instance_deleted(): void {
        $this->assertEquals(
            $this->data->participant_instance1->subject_instance_id,
            $this->get_rating_entity()->subject_instance_id
        );
        subject_instance::repository()
            ->where('id', $this->data->participant_instance1->subject_instance_id)
            ->delete();
        $this->assertNull($this->get_rating_entity()->subject_instance_id);
    }

    public function test_activity_deleted(): void {
        $activity = $this->rating->activity;
        $activity->delete();
        $this->assertNull($this->get_rating_entity()->activity_id);
        $this->assertNull($this->get_rating_entity()->subject_instance_id);
    }

    public function test_competency_deleted(): void {
        $competency_id = $this->data->competency->id;
        $this->assertEquals($competency_id, $this->get_rating_entity()->competency_id);
        \totara_hierarchy\entity\competency::repository()->where('id', $competency_id)->delete();
        $this->assertNull($this->get_rating_entity());
    }

    private function get_rating_entity(): ?perform_rating {
        return perform_rating::repository()->where('id', $this->rating->id)->one();
    }

}
