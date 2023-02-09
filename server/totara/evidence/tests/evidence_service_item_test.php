<?php
/**
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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package totara_evidence
 * @category test
 */

use core\entity\user;
use totara_core\phpunit\webservice_utils;

global $CFG;
require_once($CFG->dirroot . '/totara/evidence/tests/evidence_testcase.php');

/**
 * @group totara_evidence
 */
class totara_evidence_service_item_testcase extends totara_evidence_testcase {

    use webservice_utils;

    public static function setUpBeforeClass(): void {
        global $CFG;
        require_once($CFG->dirroot . '/lib/externallib.php');
        require_once($CFG->dirroot . '/webservice/tests/helpers.php');
    }

    /**
     * Test the info service method returns value and handles permissions correctly
     */
    public function test_service_item_info(): void {
        self::setAdminUser();

        $item_count = 3;
        $items = [];

        $this->generator()->create_evidence_type();
        for ($i = 0; $i < $item_count; $i++) {
            $item = [
                'name'    => $i,
                'user_id' => user::logged_in()->id,
            ];
            $item_model = $this->generator()->create_evidence_item($item);
            $items[] = $item_model->get_data();
        }

        foreach ($items as $item) {
            $response = $this->call_webservice_api('totara_evidence_item_info', [
                'id' => $item['id']
            ]);
            $this->assert_webservice_success($response);
            $this->assertEquals($item, $response['data']);
        }

        // Guest is not allowed to view type info
        self::setGuestUser();
        foreach ($items as $item) {
            $response = $this->call_webservice_api('totara_evidence_item_info', [
                'id' => $item['id']
            ]);
            $this->assert_webservice_error($response);
            $this->assert_webservice_has_exception_message('permissions', $response);
        }
    }

    /**
     * Test the delete service method deletes item correctly
     */
    public function test_service_item_delete(): void {
        self::setAdminUser();

        $this->generator()->create_evidence_type(['name' => 0]);
        $item = $this->generator()->create_evidence_item(['type' => 0, 'created_by' => user::logged_in()->id]);

        $this->assertCount(1, $this->items());

        // Guest is not allowed to delete
        self::setGuestUser();
        $response = $this->call_webservice_api('totara_evidence_item_delete', [
            'id' => $item->get_id()
        ]);
        self::setAdminUser();
        $this->assert_webservice_error($response);
        $this->assert_webservice_has_exception_message('permissions', $response);

        // Can't delete item that is in use
        $relation = $this->generator()->create_evidence_plan_relation($item);
        $response = $this->call_webservice_api('totara_evidence_item_delete', [
            'id' => $item->get_id()
        ]);
        $this->assert_webservice_error($response);
        $this->assert_webservice_has_exception_message('currently in use elsewhere', $response);
        $this->assertCount(1, $this->items());
        $relation->delete();

        $response = $this->call_webservice_api('totara_evidence_item_delete', [
            'id' => $item->get_id()
        ]);
        $this->assert_webservice_success($response);
        $this->assertTrue($response['data']);
        $this->assertCount(0, $this->items());
    }

}
