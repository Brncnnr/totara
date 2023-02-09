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
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package mod_perform
 */

use core_phpunit\testcase;
use mod_perform\dates\date_offset;
use mod_perform\entity\activity\track;
use mod_perform\models\activity\track as track_model;
use mod_perform\testing\generator;

class mod_perform_repeating_type_upgrade_testcase extends testcase {
    /**
     * Data provider for test_upgrade
     */
    public function td_upgrade(): array {
        return [
            'after creation' => [
                track::SCHEDULE_REPEATING_TYPE_AFTER_CREATION,
                'mod_perform\models\activity\trigger\repeating\after_creation'
            ],
            'after completion' => [
                track::SCHEDULE_REPEATING_TYPE_AFTER_COMPLETION,
                'mod_perform\models\activity\trigger\repeating\after_completion'
            ],
            'after after creation and completion' => [
                track::SCHEDULE_REPEATING_TYPE_AFTER_CREATION_WHEN_COMPLETE,
                'mod_perform\models\activity\trigger\repeating\after_creation_and_completion'
            ]
        ];
    }

    /**
     * @dataProvider td_upgrade
     */
    public function test_upgrade(
        int $repeating_type,
        string $expected
    ): void {
        global $CFG;
        require_once($CFG->dirroot . '/mod/perform/db/upgradelib.php');

        $this->setAdminUser();

        $offset = new date_offset(1, date_offset::UNIT_DAY);

        $track_id = generator::instance()
            ->create_activity_in_container(['create_track' => true])
            ->get_tracks()
            ->map(
                function (track_model $model) use ($repeating_type, $offset): int {
                    $entity = $model->get_entity_copy();

                    $entity->repeating_is_enabled = true;
                    $entity->repeating_type = $repeating_type;
                    $entity->repeating_trigger = null;
                    $entity->repeating_offset = $offset;

                    return $entity->update()->id;
                }
            )
            ->first();

        track::repository()
            ->where('id', $track_id)
            ->get()
            ->map(
                function (track $entity) use ($repeating_type, $offset): void {
                    $this->assertTrue($entity->repeating_is_enabled);
                    $this->assertEquals($repeating_type, $entity->repeating_type);
                    $this->assertEquals($offset, $entity->repeating_offset);
                    $this->assertNull($entity->repeating_trigger);
                }
            );

        mod_perform_upgrade_track_repeating_trigger();

        track::repository()
            ->where('id', $track_id)
            ->get()
            ->map(
                function (track $entity) use ($repeating_type, $offset, $expected): void {
                    $this->assertTrue($entity->repeating_is_enabled);
                    $this->assertEquals($repeating_type, $entity->repeating_type);
                    $this->assertEquals($offset, $entity->repeating_offset);
                    $this->assertEquals($expected, get_class($entity->repeating_trigger));
                }
            );
    }
}
