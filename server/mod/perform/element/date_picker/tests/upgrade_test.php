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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package performelement_date_picker
 */

use core\orm\query\builder;
use core_phpunit\testcase;
use mod_perform\entity\activity\element as element_entity;
use mod_perform\models\activity\element as element_model;
use mod_perform\state\activity\active;
use mod_perform\state\activity\draft;
use performelement_date_picker\date_picker;

/**
 * @group perform
 * @group perform_element
 */
class performelement_date_picker_upgrade_test extends testcase {

    public function test_upgrade(): void {
        global $CFG;

        self::setAdminUser();

        $generator = \mod_perform\testing\generator::instance();

        $active_empty_data = $generator->create_element(
            [
                'plugin_name' => date_picker::get_plugin_name(),
                'title' => 'active date picker empty data',
                'is_required' => true,
                'data' => json_encode(['yearRangeStart' => 2000, 'yearRangeEnd' => 2020]),
            ]
        );

        // Set the data to empty (only previous versions supported this)
        builder::table(element_entity::TABLE)
            ->where('id', $active_empty_data->id)
            ->update(['data' => '{}']);

        $this->add_to_activity($active_empty_data, active::get_code());

        $draft_empty_data = $generator->create_element(
            [
                'plugin_name' => date_picker::get_plugin_name(),
                'title' => 'draft date picker empty data',
                'is_required' => true,
                'data' => json_encode(['yearRangeStart' => 1900, 'yearRangeEnd' => 2050]),
            ]
        );
        // Set the data to empty (only previous versions supported this)
        builder::table(element_entity::TABLE)
            ->where('id', $draft_empty_data->id)
            ->update(['data' => null]);

        $this->add_to_activity($draft_empty_data, draft::get_code());

        // Just in case we already have a date_picker element with the new data in it
        // we want to make sure if the upgrade is run it's not been touched
        $active_existing_data = $generator->create_element(
            [
                'plugin_name' => date_picker::get_plugin_name(),
                'title' => 'active date picker empty data',
                'is_required' => true,
                'data' => json_encode(['yearRangeStart' => 1971, 'yearRangeEnd' => 2071]),
            ]
        );
        $this->add_to_activity($active_existing_data, active::get_code());

        require_once $CFG->dirroot . '/mod/perform/element/date_picker/db/upgradelib.php';
        performelement_date_picker_maintain_active_date_picker_year_ranges();

        $active_empty_data = element_model::load_by_id($active_empty_data->id);
        self::assertEquals([
            'yearRangeStart' => 1900,
            'yearRangeEnd' => 2050,
        ], json_decode($active_empty_data->data, true, 512, JSON_THROW_ON_ERROR));

        $active_existing_data = element_model::load_by_id($active_existing_data->id);
        self::assertEquals([
            'yearRangeStart' => 1971,
            'yearRangeEnd' => 2071,
        ], json_decode($active_existing_data->data, true, 512, JSON_THROW_ON_ERROR));

        $draft_empty_data = element_model::load_by_id($draft_empty_data->id);
        self::assertEquals([
            'yearRangeStart' => 1900,
            'yearRangeEnd' => 2050,
        ], json_decode($draft_empty_data->data, true, 512, JSON_THROW_ON_ERROR));
    }

    private function add_to_activity(element_model $element, int $activity_state): void {
        $generator = \mod_perform\testing\generator::instance();

        $activity = $generator->create_activity_in_container(['activity_status' => $activity_state]);
        $section = $generator->create_section($activity, ['title' => 'Part one']);
        $generator->create_section_element($section, $element);
    }

}