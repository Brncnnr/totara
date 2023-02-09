<?php
/**
 * This file is part of Totara Talent Experience Platform
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Cody Finegan <cody.finegan@totara.com>
 * @package totara_useraction
 */

use core_phpunit\testcase;
use totara_useraction\action\action_result;
use totara_useraction\action\factory as action_factory;
use totara_useraction\entity\scheduled_rule as entity;
use totara_useraction\fixtures\mock_action;
use totara_useraction\local\testing\mock_actions;
use totara_useraction\model\scheduled_rule as model;
use totara_useraction\filter\status as filter_status;
use totara_useraction\filter\duration as filter_duration;
use totara_useraction\filter\applies_to as filter_applies_to;
use totara_useraction\model\scheduled_rule\execution_data;

/**
 * Test the model
 *
 * @group totara_useraction
 */
class totara_useraction_scheduled_rule_model_testcase extends testcase {
    use mock_actions;

    /**
     * Simple test of the create, update & delete methods.
     *
     * @return void
     */
    public function test_create_update(): void {
        global $DB;
        $this->inject_mock_actions();

        $duration_input = [
            'source' => filter_duration::ENUM_SUSPENDED,
            'unit' => filter_duration::ENUM_UNIT_MONTHS,
            'value' => 2,
        ];

        // Minimum properties
        $rule = model::create(
            'RuleA',
            mock_action::class,
            filter_status::create_from_input(filter_status::ENUM_SUSPENDED),
            filter_duration::create_from_input($duration_input),
            filter_applies_to::create_from_input(['audiences' => null])
        );

        self::assertInstanceOf(model::class, $rule);
        self::assertNotEmpty($rule->id);
        $rule_id = $rule->id;

        // Check the DB
        $record = $DB->get_record(entity::TABLE, ['id' => $rule_id]);
        self::assertEquals($rule->id, $record->id);
        self::assertEquals($rule->name, $record->name);
        self::assertEmpty($rule->description);
        self::assertTrue(action_factory::is_valid($record->action));

        // Check update
        $rule->update(['description' => 'New Description']);

        $rule = model::load_by_id($rule->id);
        self::assertEquals('New Description', $rule->description);

        // Check delete
        $rule->delete();
        $exists = $DB->record_exists(entity::TABLE, ['id' => $rule_id]);
        self::assertFalse($exists);
        $this->remove_mock_actions();
    }

    /**
     * Test recording action for a user.
     *
     * @return void
     */
    public function test_record_action_for_user(): void {
        global $DB;

        // Create scheduled_rule for history record
        /** @var \totara_useraction\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_useraction');
        $scheduled_rule = $generator->create_scheduled_rule(['id_number' => 'ABC123']);

        $data_generator = $this->getDataGenerator();
        $user = $data_generator->create_user();

        // Make the record_action method accessible so that we can call it.
        $method = new ReflectionMethod($scheduled_rule, 'record_action');
        $method->setAccessible(true);

        /* @var totara_useraction\model\scheduled_rule_history $history */
        $history = $method->invokeArgs(
            $scheduled_rule,
            [
                $user->id,
                action_result::success()
            ]
        );

        self::assertInstanceOf(totara_useraction\model\scheduled_rule_history::class, $history);
        self::assertNotEmpty($history->id);

        // Check the DB
        $record = $DB->get_record(totara_useraction\entity\scheduled_rule_history::TABLE, ['id' => $history->id]);
        self::assertEquals($history->scheduled_rule_id, $scheduled_rule->id);
        self::assertEquals($history->user_id, $record->user_id);
        self::assertEquals($history->created, $record->created);
        self::assertTrue($history->success);
        self::assertEquals(get_class($history->action), $record->action);

        // Check delete
        $history->delete();
        $exists = $DB->record_exists(totara_useraction\entity\scheduled_rule_history::TABLE, ['id' => $history->id]);
        self::assertFalse($exists);
    }

    public function test_execute_inactive_rule(): void {
        $rule = model::create(
            'RuleA',
            mock_action::class,
            filter_status::create_from_input(filter_status::ENUM_SUSPENDED),
            filter_duration::create_from_input([
                'source' => filter_duration::ENUM_SUSPENDED,
                'unit' => filter_duration::ENUM_UNIT_MONTHS,
                'value' => 2,
            ]),
            filter_applies_to::create_from_input(['audiences' => null])
        );

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Scheduled rule is inactive");
        $rule->execute(execution_data::instance());
    }
}