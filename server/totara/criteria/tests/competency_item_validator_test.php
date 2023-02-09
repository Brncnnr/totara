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

use pathway_manual\models\roles\manager;
use totara_criteria\validators\competency_item_validator;

/**
 * @group totara_competency
 */
class totara_criteria_competency_item_validator_testcase extends advanced_testcase {

    /**
     * Test validate_item
     */
    public function test_validate_item_competency() {
        global $CFG;

        $CFG->enablecompletion = true;

        /** @var \totara_competency\testing\generator $competency_generator */
        $competency_generator = $this->getDataGenerator()->get_plugin_generator('totara_competency');

        /** @var \totara_criteria\testing\generator $criteria_generator */
        $criteria_generator = $this->getDataGenerator()->get_plugin_generator('totara_criteria');

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => true]);
        $criterion = $criteria_generator->create_coursecompletion(['courseids' => [$course->id]]);

        // Competency without criteria
        $competency1 = $competency_generator->create_competency();
        $this->assertFalse(competency_item_validator::validate_item($competency1->id));

        // With criteria not leading to proficiency
        $competency2 = $competency_generator->create_competency();
        $pw2 = $competency_generator->create_criteria_group($competency2, [$criterion], $competency2->scale->default_value);
        $this->assertFalse(competency_item_validator::validate_item($competency2->id));

        // With criteria leading to proficient value
        $competency3 = $competency_generator->create_competency();
        $pw3 = $competency_generator->create_criteria_group($competency3, [$criterion], $competency3->scale->min_proficient_value);
        $this->assertTrue(competency_item_validator::validate_item($competency3->id));

        // With multi value pathway
        $competency4 = $competency_generator->create_competency();
        $pw4 = $competency_generator->create_manual($competency4, [manager::class]);
        $this->assertTrue(competency_item_validator::validate_item($competency4->id));
    }

}
