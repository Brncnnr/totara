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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package mod_approval
 */

use core\entity\user;
use mod_approval\model\application\application_submission;
use mod_approval\model\form\form_data;
use mod_approval\model\workflow\stage_type\form_submission;
use mod_approval\model\workflow\workflow_stage;
use mod_approval\model\workflow\workflow_stage_formview;
use mod_approval\rb\display\application_form_response;
use totara_reportbuilder\phpunit\report_testing;

require_once(__DIR__ . '/testcase.php');

/**
 * @group approval_workflow
 */
class mod_approval_rb_application_form_response_testcase extends mod_approval_testcase {
    use report_testing;

    /**
     * Local callback for workflow setup
     *
     * @param $workflow_version
     * @return void
     */
    public function local_workflow_setup($workflow_version): void {
        $form_stage = workflow_stage::create($workflow_version, 'stage 1', form_submission::get_enum());
        workflow_stage_formview::create($form_stage, 'kia', false, false, 'KIA');
        workflow_stage_formview::create($form_stage, 'ora', false, false, 'ORA');
    }

    /**
     * Data provider for test_application_form_response_display
     *
     * @return array
     */
    public function display_function_data_provider(): array {
        $tests = [
            'string' => ['kaha', 'html', 'kaha'],
            'string text' => ['kaha', 'text', 'kaha'],
            'quoted string' => ['"kaha"', 'html', '&#34;kaha&#34;'],
            'quoted string text' => ['"kaha"', 'text', '"kaha"'],
            'XSS' => ['<script>alert(1);</script>', 'html', 'alert(1);'],
            'XSS text' => ['<script>alert(1);</script>', 'text', 'alert(1);'],
            'empty string' => ['', 'html', ''],
            'null' => [null, 'html', ''],
            'false' => [false, 'html', ''],
            'integer' => [42, 'html', '42'],
            'already escaped' => ['&#34;kaha&#34;', 'html', '&#34;kaha&#34;'],
            'already escaped text' => ['&#34;kaha&#34;', 'text', '"kaha"'],
        ];
        shuffle($tests);
        return $tests;
    }

    /**
     * Tests the report builder display function
     *
     * @covers \mod_approval\rb\display\application_form_response::display
     * @dataProvider display_function_data_provider
     */
    public function test_application_form_response_display($value, string $format, string $expected) {
        $submitter_user = new user($this->getDataGenerator()->create_user());
        $this->setAdminUser();
        $application = $this->create_application_for_user('test', [$this, 'local_workflow_setup']);

        $json_obj = new \stdClass();
        $json_obj->kia = $value;
        $json = json_encode($json_obj, JSON_THROW_ON_ERROR | JSON_FORCE_OBJECT | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE, 512);

        // Submit the application.
        $submission = application_submission::create_or_update(
            $application,
            $submitter_user->id,
            form_data::from_json($json)
        );
        $submission->publish($submitter_user->id);

        // Create report.
        $rid = $this->create_report('approval_workflow_applications', 'Test application form responses', false, 1);
        $config = (new rb_config())->set_nocache(true);
        $report = reportbuilder::create($rid, $config, false);

        // Mock objects to use in the display function.
        $column = $this->getMockBuilder('\rb_column')
            ->setConstructorArgs(array('application', 'kia', 'kia', 'id'))
            ->getMock();
        $row = new stdClass();

        // Testing display function.
        $display = application_form_response::display($application->id, $format, $row, $column, $report);
        $this->assertEquals($expected, $display);

        // Reset static cache.
        application_form_response::reset();
    }
}