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
 * @author  Gihan Hewaralalage <gihan.hewaralalage@totaralearning.com>
 * @package totara_notification
 */

use core_phpunit\testcase;

class rb_notification_delivery_log_embedded_testcase extends testcase {
    /**
     * Prepare mock data for testing.
     */
    protected function setUp(): void {
        parent::setup();
        $this->setAdminUser();

        // Create users.
        $this->user1 = $this->getDataGenerator()->create_user();
    }

    public function test_is_capable() {
        global $DB;

        // Set up report and embedded object for is_capable checks.
        $syscontext = context_system::instance();
        $shortname = 'notification_delivery_log';
        $config = new rb_config();
        $config->set_embeddata([
            'context_id' => $syscontext->id,
        ]);
        $report = reportbuilder::create_embedded($shortname);

        $embeddedobject = $report->embedobj;
        $userid = $this->user1->id;

        // Test admin can access report.
        $this->assertTrue($embeddedobject->is_capable(2, $report), 'admin cannot access report');

        // Test user cannot access report.
        $this->assertFalse($embeddedobject->is_capable($userid, $report), 'user should not be able to access report');

        $roleuser = $DB->get_record('role', array('shortname' => 'user'));

        // Test user with capability can access report.
        assign_capability('totara/notification:auditnotifications', CAP_ALLOW, $roleuser->id, $syscontext);
        $this->assertTrue($embeddedobject->is_capable($userid, $report), 'user with capability totara/appraisal:manageappraisals cannot access report');
    }
}