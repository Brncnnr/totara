<?php
/*
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
 * @package totara_reportbuilder
 * @subpackage test
 */

use totara_reportbuilder\task\remove_orphaned_embedded_reports;

class totara_reportbuilder_remove_orphaned_embedded_reports_testcase extends advanced_testcase {

    use totara_reportbuilder\phpunit\report_testing;

    /**
     * Make sure that only embedded reports lacking a valid source class are removed and no user reports are removed.
     */
    public function test_removed_orphaned_embedded_reports() {
        global $DB;
        $valid_embedded_report = $this->create_report('courses', 'test1', null, true);
        $valid_user_report = $this->create_report('courses', 'test2');
        $orphaned_embedded_report = $this->create_report('courses', 'test3', null, true);
        $orphaned_user_report = $this->create_report('courses', 'test4');

        $DB->update_record('report_builder', [
            'id' => $orphaned_embedded_report,
            'source' => 'nonexistent_source',
        ]);
        $DB->update_record('report_builder', [
            'id' => $orphaned_user_report,
            'source' => 'nonexistent_source',
        ]);

        ob_start();
        (new remove_orphaned_embedded_reports())->execute();
        $log_message = ob_get_contents();
        ob_end_clean();

        $this->assertEquals(3, $DB->count_records('report_builder'));
        $this->assertTrue($DB->record_exists('report_builder', ['id' => $valid_embedded_report]));
        $this->assertTrue($DB->record_exists('report_builder', ['id' => $valid_user_report]));
        $this->assertTrue($DB->record_exists('report_builder', ['id' => $orphaned_user_report]));
        $this->assertFalse($DB->record_exists('report_builder', ['id' => $orphaned_embedded_report]));
        $this->assertStringContainsString('Removed 1 orphaned embedded reports.', $log_message);
    }

    /**
     * Make sure that ignored report sources aren't also deleted
     */
    public function test_ignored_source_is_kept() {
        global $DB;

        set_config('enablecompetencies', 0);
        $ignored_report = $this->create_report('comp_status_history', 'to_keep', null, true);

        $orphaned_embedded_report = $this->create_report('courses', 'to_delete', null, true);
        $DB->update_record('report_builder', [
            'id' => $orphaned_embedded_report,
            'source' => 'nonexistent_source',
        ]);

        ob_start();
        (new remove_orphaned_embedded_reports())->execute();
        ob_end_clean();

        $this->assertTrue($DB->record_exists('report_builder', ['id' => $ignored_report]));
        $this->assertFalse($DB->record_exists('report_builder', ['id' => $orphaned_embedded_report]));
    }

}
