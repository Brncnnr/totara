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
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 * @package totara_competency
 */

use core_phpunit\testcase;
use totara_competency\models\assignment_specific_scale_value;
use totara_hierarchy\entity\scale_value;

/**
 * Class totara_competency_model_assignment_specific_scale_value_testcase
 *
 * @group totara_competency
 */
class totara_competency_model_assignment_specific_scale_value_testcase extends testcase {


    public function test_explicit_proficient_flag_takes_precedence(): void {
        $origin_scale_value = new scale_value();
        $origin_scale_value->proficient = false;

        $assignment_specific_scale_value = new assignment_specific_scale_value($origin_scale_value, true);
        self::assertTrue($assignment_specific_scale_value->proficient);
        self::assertTrue($assignment_specific_scale_value->to_array()['proficient']);

        $origin_scale_value = new scale_value();
        $origin_scale_value->proficient = null;

        $assignment_specific_scale_value = new assignment_specific_scale_value($origin_scale_value, true);
        self::assertTrue($assignment_specific_scale_value->proficient);
        self::assertTrue($assignment_specific_scale_value->to_array()['proficient']);

        $origin_scale_value = new scale_value();
        $origin_scale_value->proficient = true;

        $assignment_specific_scale_value = new assignment_specific_scale_value($origin_scale_value, false);
        self::assertFalse($assignment_specific_scale_value->proficient);
        self::assertFalse($assignment_specific_scale_value->to_array()['proficient']);
    }

    public function test_setting_values_other_than_proficient_is_blocked(): void {
        $origin_scale_value = new scale_value();
        $origin_scale_value->proficient = false;

        $assignment_specific_scale_value = new assignment_specific_scale_value($origin_scale_value, true);
        $assignment_specific_scale_value->proficient = false;

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Attributes can not be set on assignment specific scale values');

        $assignment_specific_scale_value->sortorder = 100;
    }

    public function test_saving_is_blocked(): void {
        $origin_scale_value = new scale_value();
        $origin_scale_value->proficient = false;

        $assignment_specific_scale_value = new assignment_specific_scale_value($origin_scale_value, true);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Assignment specific scale value cannot be refreshed or saved');

        $assignment_specific_scale_value->save();
    }

    public function test_deleting_is_blocked(): void {
        $origin_scale_value = new scale_value();
        $origin_scale_value->proficient = false;

        $assignment_specific_scale_value = new assignment_specific_scale_value($origin_scale_value, true);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Assignment specific scale value cannot be refreshed or saved');

        $assignment_specific_scale_value->delete();
    }

}
