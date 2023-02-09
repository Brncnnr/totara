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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Gihan Hewaralalage <gihan.hewaralalage@totaralearning.com>
 * @package totara_evidence
 */

use totara_evidence\entity\evidence_type;

global $CFG;
require_once($CFG->dirroot . '/totara/evidence/tests/evidence_testcase.php');
require_once($CFG->dirroot . '/totara/evidence/db/upgradelib.php');

/**
 * @group totara_evidence
 */
class totara_evidence_remove_deleted_user_evidence_testcase extends totara_evidence_testcase {

    /**
     * Make sure deleted users' evidence has been removed.
     */
    public function test_remove_deleted_user_evidence(): void
    {
        global $DB;
        $generator = $this->generator();
        $data = $this->create_data();

        // Delete user1 (Keep username, email and ID number).
        delete_user($data->user1);
        $this->assertEquals(1, $DB->count_records('user', array('deleted'=>1)));
        $this->assertEquals(0, $DB->count_records('totara_evidence_item', array('user_id'=>$data->user1->id)));

        // Create evidence for deleted user(user1).
        $generator->create_evidence_item_entity([
            'name'       => 'Six',
            'type'       => $data->type,
            'user_id'    => $data->user1->id,
            'created_by' => $data->user2->id
        ]);
        $this->assertEquals(1, $DB->count_records('totara_evidence_item', array('user_id'=>$data->user1->id)));

        // Check user2 evidence before totara_evidence_remove_deleted_user_evidence run.
        $this->assertEquals(3, $DB->count_records('totara_evidence_item', array('user_id'=>$data->user2->id)));

        // Remove only deleted users' evidence
        totara_evidence_remove_deleted_user_evidence();
        $this->assertEquals(0, $DB->count_records('totara_evidence_item', array('user_id'=>$data->user1->id)));

        // Check user2 evidence after totara_evidence_remove_deleted_user_evidence run.
        $this->assertEquals(3, $DB->count_records('totara_evidence_item', array('user_id'=>$data->user2->id)));
    }

    /**
     * Create the data needed for the tests
     *
     * @param bool $create_files true to create files
     * @return object
     */
    protected function create_data($create_files = false): object {
        $data = new class {
            public $user1;
            public $user2;
            public $item1;
            public $item2;

            /**
             * @var evidence_type
             */
            public $type;
        };

        $generator = $this->generator();
        $generator->set_create_files($create_files);

        $data->user1 = (object) self::getDataGenerator()->create_user();
        $data->user2 = (object) self::getDataGenerator()->create_user();

        $data->type = $generator->create_evidence_type_entity([
            'name' => 'Type',
            'field_types' => [
                'text',
                'textarea',
                'file'
            ]
        ]);

        $data->item1 = $generator->create_evidence_item_entity([
            'name'       => 'One',
            'type'       => $data->type,
            'user_id'    => $data->user1->id,
            'created_by' => $data->user2->id
        ]);
        $data->item2 = $generator->create_evidence_item_entity([
            'name'       => 'Two',
            'type'       => $data->type,
            'user_id'    => $data->user2->id,
            'created_by' => $data->user1->id
        ]);
        $data->item3 = $generator->create_evidence_item_entity([
            'name'       => 'Three',
            'type'       => $data->type,
            'user_id'    => $data->user2->id,
            'created_by' => $data->user2->id
        ]);
        $data->item4 = $generator->create_evidence_item_entity([
            'name'       => 'Four',
            'type'       => $data->type,
            'user_id'    => $data->user2->id,
            'created_by' => $data->user2->id
        ]);

        return $data;
    }
}