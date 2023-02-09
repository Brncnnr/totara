<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package mod_perform
 * @category test
 */

use mod_perform\entity\activity\activity_setting as activity_setting_entity;
use mod_perform\models\activity\activity;
use mod_perform\models\activity\activity_setting;
use mod_perform\models\activity\settings\visibility_conditions\all_responses;
use mod_perform\models\activity\settings\visibility_conditions\own_response;
use mod_perform\state\activity\draft;

/**
 * @coversDefaultClass \mod_perform\models\activity\activity_setting
 *
 * @group perform
 */
class mod_perform_activity_setting_model_testcase extends advanced_testcase {
    /**
     * @covers ::create
     * @covers ::update
     * @covers ::delete
     */
    public function test_crud(): void {
        $activity = $this->create_test_data();

        $name = activity_setting::MULTISECTION;
        $value = "this is a test";

        $setting = activity_setting::create($activity, $name, $value);
        $this->assertGreaterThan(0, $setting->id, 'wrong id');
        $this->assertEquals($name, $setting->name, 'wrong name');
        $this->assertEquals($value, $setting->value, 'wrong value');
        $this->assertEquals($activity->id, $setting->activity->id, 'wrong parent');

        $value = "43423";
        $setting->update($value);
        $this->assertEquals($value, $setting->value, 'wrong value');

        $db_count = activity_setting_entity::repository()
            ->where('activity_id', $activity->id)
            ->count();
        $this->assertEquals(1, $db_count, 'wrong db settings count');

        $setting->delete();

        $db_count = activity_setting_entity::repository()
            ->where('activity_id', $activity->id)
            ->count();
        $this->assertEquals(0, $db_count, 'wrong db settings count');
    }

    /**
     * @covers ::load_by_name
     */
    public function test_load_by_name() {
        $activity = $this->create_test_data();
        $name = activity_setting::VISIBILITY_CONDITION;
        $value = 1;
        activity_setting::create($activity, $name, $value);

        $setting = activity_setting::load_by_name($activity->get_id(), activity_setting::VISIBILITY_CONDITION);
        $this->assertGreaterThan(0, $setting->id, 'wrong id');
        $this->assertEquals($name, $setting->name, 'wrong name');
        $this->assertEquals($value, $setting->value, 'wrong value');
    }

    /**
     * @covers ::load_by_name_or_create
     */
    public function test_load_by_name_or_create() {
        $activity = $this->create_test_data();
        $this->assertEquals(0, $activity->settings->get()->count());

        $setting = activity_setting::load_by_name_or_create($activity->get_id(), activity_setting::VISIBILITY_CONDITION);
        $this->assertEquals(1, $activity->settings->get()->count());

        $this->assertGreaterThan(0, $setting->id, 'wrong id');
        $this->assertEquals(activity_setting::VISIBILITY_CONDITION, $setting->name, 'wrong name');
        $this->assertEquals('', $setting->value);
    }

    /**
     * @covers ::validate
     */
    public function test_visibility_condition_update_invalid_activity_status() {
        $this->setAdminUser();
        $perform_generator = \mod_perform\testing\generator::instance();
        $activity = $perform_generator->create_activity_in_container(["anonymous_responses" => true]);
        $activity->refresh();

        $this->expectExceptionMessage(
            "Can not update visibility condition for activated activity when anonymity is enabled."
        );
        activity_setting::validate($activity, activity_setting::VISIBILITY_CONDITION, all_responses::VALUE);
    }

    /**
     * Test visibility condition value must be all responses closed when anonymity is enabled
     * @covers ::validate
     */
    public function test_incorrect_visibility_condition_should_throw_exception() {
        $this->setAdminUser();
        $perform_generator = \mod_perform\testing\generator::instance();
        $activity = $perform_generator->create_activity_in_container(["anonymous_responses" => true, 'activity_status'=> draft::get_code()]);
        $activity->refresh();

        $this->expectExceptionMessage(
            "Anonymous activities have to be set to show responses after all participants completed their instances."
        );
        activity_setting::validate($activity, activity_setting::VISIBILITY_CONDITION, own_response::VALUE);
    }

    /**
     * @covers ::validate
     */
    public function test_visibility_condition_update_invalid_value() {
        $this->setAdminUser();
        $perform_generator = \mod_perform\testing\generator::instance();
        $activity = $perform_generator->create_activity_in_container();

        $this->expectExceptionMessage(
            "invalid visibility condition value: 5"
        );
        activity_setting::validate($activity, activity_setting::VISIBILITY_CONDITION, 5);
    }

    /**
     * @covers ::create
     */
    public function test_invalid_setting_name(): void {
        $activity = $this->create_test_data();

        $name = "aaa";

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage("invalid activity setting name: $name");
        activity_setting::create($activity, $name, 'abc');
    }

    /**
     * Generates test data.
     *
     * @return activity the test activity.
     */
    private function create_test_data(): activity {
        $this->setAdminUser();

        $perform_generator = \mod_perform\testing\generator::instance();
        return $perform_generator->create_activity_in_container();
    }
}
