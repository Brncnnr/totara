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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package mod_perform
 */

/**
 * @group totara_reportbuilder
 * @group perform
 */
class mod_perform_report_clone_testcase extends advanced_testcase {

    public function test_can_not_clone_response_export_report(): void {
        global $CFG;
        require_once($CFG->dirroot . '/totara/reportbuilder/lib.php');

        self::setAdminUser();
        $report = reportbuilder::create_embedded('perform_response_export');

        $this->assertTrue((bool) $report->embedded);
        $this->assertFalse($report->embedobj::is_cloning_allowed());
        $this->assertEquals(
            get_string('embedded_perform_response_export_cloning_not_allowed', 'mod_perform'),
            $report->embedobj->get_cloning_not_allowed_message()
        );

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage($report->embedobj->get_cloning_not_allowed_message());
        reportbuilder_clone_report($report, 'not_allowed');
    }

}
