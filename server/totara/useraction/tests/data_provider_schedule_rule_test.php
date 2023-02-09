<?php
/**
 * This file is part of Totara Learn
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 * @package totara_useraction
 */

use core_phpunit\testcase;
use totara_useraction\data_provider\scheduled_rule as scheduled_rule_data_provider;
use totara_useraction\fixtures\mock_action;
use totara_useraction\filter\status as filter_status;
use totara_useraction\filter\duration as filter_duration;
use totara_useraction\filter\applies_to as filter_applies_to;
use totara_useraction\model\scheduled_rule;

/**
 * @group totara_useraction
 */
class totara_useraction_data_provider_schedule_rule_testcase extends testcase {

    public function test_get_all_active_scheduled_rules() {
        // Create 8 scheduled rules and make 4 active.
        $duration_input = [
            'source' => filter_duration::ENUM_SUSPENDED,
            'unit' => filter_duration::ENUM_UNIT_MONTHS,
            'value' => 2,
        ];
        for ($i = 1; $i <= 4; $i++) {
            scheduled_rule::create(
                "Disabled rule $i",
                mock_action::class,
                filter_status::create_from_input(filter_status::ENUM_SUSPENDED),
                filter_duration::create_from_input($duration_input),
                filter_applies_to::create_from_input(['audiences' => null])
            );
        }
        for ($i = 1; $i <= 4; $i++) {
            scheduled_rule::create(
                "Enabled rule $i",
                "Enabled-rule-$i",
                filter_status::create_from_input(filter_status::ENUM_SUSPENDED),
                filter_duration::create_from_input($duration_input),
                filter_applies_to::create_from_input(['audiences' => null]),
                null,
                null,
                true
            );
        }

        $active_rules = scheduled_rule_data_provider::get_all_active_rules();
        $this->assertCount(4, $active_rules->to_array());

        foreach ($active_rules as $active_rule) {
            $this->assertStringContainsString("Enabled rule", $active_rule->name);
        }
    }
}
