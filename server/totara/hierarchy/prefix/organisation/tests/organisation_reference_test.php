<?php
/*
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
 * @author Michael Ivanov <michael.ivanov@totaralearning.com>
 * @package hierarchy_organisation
 */

defined('MOODLE_INTERNAL') || die();

use core\exception\unresolved_record_reference;
use core_phpunit\testcase;
use hierarchy_organisation\reference\hierarchy_organisation_record_reference;

/**
 * @group hierarchy_organisation
 */
class hierarchy_organisation_organisation_reference_testcase extends testcase {

    /**
     * @return void
     */
    public function test_find_reference_by_id(): void {
        self::setAdminUser();
        $gen = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $framework = $gen->create_org_frame([]);
        $organisation1 = $gen->create_org([
            'frameworkid' => $framework->id,
            'fullname' => "organisation 1"
        ]);
        $gen->create_pos([
            'frameworkid' => $framework->id,
            'fullname' => "organisation 2"
        ]);

        $reference = new hierarchy_organisation_record_reference();
        $record = $reference->get_record(['id' => $organisation1->id]);
        $this->assertEquals($organisation1->id, $record->id);
    }

    /**
     * @return void
     */
    public function test_find_reference_by_nonexisting_id(): void {
        self::setAdminUser();

        $reference = new hierarchy_organisation_record_reference();
        $this->expectException(unresolved_record_reference::class);
        $reference->get_record(['id' => 912]);
    }

    /**
     * @return void
     */
    public function test_find_reference_by_idnumber(): void {
        self::setAdminUser();
        $gen = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $framework = $gen->create_org_frame([]);
        $gen->create_org([
            'frameworkid' => $framework->id,
            'fullname' => "position 1",
            'idnumber' => 35,
        ]);
        $organisation2 = $gen->create_org([
            'frameworkid' => $framework->id,
            'fullname' => "position 2",
            'idnumber' => 11,
        ]);

        $reference = new hierarchy_organisation_record_reference();
        $record = $reference->get_record(['idnumber' => 11]);
        $this->assertEquals($organisation2->id, $record->id);
        $this->assertEquals($organisation2->idnumber, $record->idnumber);
    }

    /**
     * @return void
     */
    public function test_match_multiple_organisations(): void {
        self::setAdminUser();
        $gen = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $framework = $gen->create_org_frame([]);
        $gen->create_org([
            'frameworkid' => $framework->id,
            'fullname' => "position 1",
            'idnumber' => 35,
        ]);
        $gen->create_org([
            'frameworkid' => $framework->id,
            'fullname' => "position 2",
            'idnumber' => 35,
        ]);

        $reference = new hierarchy_organisation_record_reference();
        $this->expectException(unresolved_record_reference::class);
        $reference->get_record(['idnumber' => 35]);
    }
}
