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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_perform
 * @category test
 */

use core\orm\query\builder;
use mod_perform\constants;
use mod_perform\notification\recipient;
use totara_core\entity\relationship as relationship_entity;
use totara_core\relationship\relationship as relationship_model;

require_once(__DIR__ . '/notification_testcase.php');

/**
 * @coversDefaultClass \mod_perform\notification\recipient
 * @group perform
 * @group perform_notifications
 */
class mod_perform_notification_recipient_testcase extends mod_perform_notification_testcase {
    private function get_data(): array {
        $idnumbers = [
            constants::RELATIONSHIP_APPRAISER,
            constants::RELATIONSHIP_MANAGER,
            constants::RELATIONSHIP_MANAGERS_MANAGER,
            constants::RELATIONSHIP_SUBJECT,
            constants::RELATIONSHIP_PEER,
            constants::RELATIONSHIP_REVIEWER,
            constants::RELATIONSHIP_MENTOR,
            constants::RELATIONSHIP_EXTERNAL,
        ];
        $expects_list = [
            'S' => [recipient::STANDARD, [1, 1, 1, 1, 0, 0, 0, 0]],
            'M' => [recipient::MANUAL, [0, 0, 0, 0, 1, 1, 1, 0]],
            'X' => [recipient::EXTERNAL, [0, 0, 0, 0, 0, 0, 0, 1]],
            'S,M' => [recipient::STANDARD | recipient::MANUAL, [1, 1, 1, 1, 1, 1, 1, 0]],
            'S,X' => [recipient::STANDARD | recipient::EXTERNAL, [1, 1, 1, 1, 0, 0, 0, 1]],
            'M,X' => [recipient::MANUAL | recipient::EXTERNAL, [0, 0, 0, 0, 1, 1, 1, 1]],
            'S,M,X' => [recipient::STANDARD | recipient::MANUAL | recipient::EXTERNAL, [1, 1, 1, 1, 1, 1, 1, 1]],
        ];
        return [$idnumbers, $expects_list];
    }

    /**
     * @covers ::is_available
     */
    public function test_is_available() {
        [$idnumbers, $expects_list] = $this->get_data();
        $relationships = relationship_entity::repository()->get()->map_to(relationship_model::class)->key_by('idnumber')->all(true);
        foreach ($expects_list as $what => [$recipients, $expects]) {
            foreach ($idnumbers as $i => $idnumber) {
                $outcome = recipient::is_available($recipients, $relationships[$idnumber]);
                $this->assertEquals($expects[$i], $outcome, "$what: $idnumber");
            }
        }
        try {
            recipient::is_available(0, reset($relationships));
            $this->fail('coding_exception expected');
        } catch (coding_exception $ex) {
            $this->assertStringContainsString('recipients are not set', $ex->getMessage());
        }
    }

    /**
     * @covers ::where_available
     */
    public function test_where_available() {
        [$idnumbers, $expects_list] = $this->get_data();
        foreach ($expects_list as $what => [$recipients, $expects]) {
            $builder = builder::table(relationship_entity::TABLE, 'rel')->map_to(relationship_entity::class);
            recipient::where_available($recipients, $builder, 'rel');
            $relationships = $builder->get()->key_by('idnumber')->map_to(relationship_model::class)->all(true);
            foreach ($idnumbers as $i => $idnumber) {
                $outcome = isset($relationships[$idnumber]);
                $this->assertEquals($expects[$i], $outcome, "$what: $idnumber");
            }
        }
        try {
            $builder = builder::table(relationship_entity::TABLE, 'rel')->map_to(relationship_entity::class);
            recipient::where_available(0, $builder, 'rel');
            $this->fail('coding_exception expected');
        } catch (coding_exception $ex) {
            $this->assertStringContainsString('recipients are not set', $ex->getMessage());
        }
    }
}
