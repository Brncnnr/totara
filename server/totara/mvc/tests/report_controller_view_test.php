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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com>
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_mvc
 */

use totara_mvc\controller;
use totara_mvc\has_report;
use totara_mvc\report_view;
use totara_mvc\view;
use totara_mvc\viewable;
use totara_reportbuilder\event\report_viewed;

defined('MOODLE_INTERNAL') || die();


class totara_mvc_report_controller_view_testcase extends advanced_testcase {

    public function test_view_report() {
        $this->setAdminUser();

        $controller = new class extends controller {
            use has_report;

            protected $url = '/totara/mvc/tests/report_controller_view_test.php';

            protected function setup_context(): context {
                return context_system::instance();
            }

            public function action() {
                $report = $this->load_embedded_report('manage_embedded_reports');

                return (new report_view('totara_mvc/report', $report, true))
                    ->set_back_to(new moodle_url('/back/to/url.php'), 'This is a back link')
                    ->set_title('This is the report title');
            }
        };

        ob_start();

        $controller->process();

        $output = ob_get_clean();

        // Assert that there's a back link
        $this->assertStringContainsString(
            '<a href="https://www.example.com/moodle/back/to/url.php">This is a back link</a>',
            $output
        );
        // Assert that he have the header section
        $this->assertMatchesRegularExpression('/[0-9]+ records? shown/', $output);
        $this->assertMatchesRegularExpression('/\<h2.*\>This is the report title\<\/h2\>/', $output);
        // Assert that we have the filter part
        $this->assertMatchesRegularExpression('/Search by/', $output);
        // Assert that we have the export part
        $this->assertMatchesRegularExpression('/Export as/', $output);
        // Assert that we have a column header
        $this->assertMatchesRegularExpression('/Report Name/', $output);
        // Assert that we have a row in the list
        $this->assertMatchesRegularExpression('/\<td.*\><a href\=\".*\".*\>Manage embedded reports\<\/a\>/', $output);
        // Assert that we have a button to show / hide columns
        $this->assertStringContainsString('Show/Hide Columns', $output);
        // Assert that we have debug information showing up
        $this->assertStringContainsString('SELECT base.id', $output);
    }

    public function test_view_report_triggers_event() {
        $this->setAdminUser();

        $controller = new class extends controller {
            use has_report;

            protected $url = '/totara/mvc/tests/report_controller_view_test.php';

            protected function setup_context(): context {
                return context_system::instance();
            }

            public function action() {
                $report = $this->load_embedded_report('manage_embedded_reports');

                return (new report_view('totara_mvc/report', $report, true))
                    ->set_back_to(new moodle_url('/back/to/url.php'), 'This is a back link')
                    ->set_title('This is the report title');
            }
        };

        $sink = $this->redirectEvents();

        ob_start();
        $controller->process();
        ob_end_clean();

        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = array_shift($events);
        $this->assertInstanceOf(report_viewed::class, $event);
    }

    public function test_view_report_doesnt_trigger_event() {
        $this->setAdminUser();

        $controller = new class extends controller {
            use has_report;

            protected $url = '/totara/mvc/tests/report_controller_view_test.php';

            protected function setup_context(): context {
                return context_system::instance();
            }

            public function action() {
                $report = $this->load_embedded_report('manage_embedded_reports', [], false);

                return (new report_view('totara_mvc/report', $report, true))
                    ->set_back_to(new moodle_url('/back/to/url.php'), 'This is a back link')
                    ->set_title('This is the report title');
            }
        };

        $sink = $this->redirectEvents();

        ob_start();
        $controller->process();
        ob_end_clean();

        $events = $sink->get_events();
        $this->assertCount(0, $events);
    }

    public function test_view_invalid_report() {
        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessageMatches('/Report with name of .* not found in database./');

        $this->setAdminUser();

        $controller = new class extends controller {
            use has_report;

            protected $url = '/totara/mvc/tests/report_controller_view_test.php';

            protected function setup_context(): context {
                return context_system::instance();
            }

            public function action() {
                $report = $this->load_embedded_report('idonotexist');

                return report_view::create_from_report($report)
                    ->set_title('This is the report title');
            }
        };

        $controller->process();
    }

    public function test_saved_search_is_triggered() {
        $_GET['sid'] = 666;

        $this->setAdminUser();

        $controller = new class extends controller {
            use has_report;

            protected $url = '/totara/mvc/tests/report_controller_view_test.php';

            protected function setup_context(): context {
                return context_system::instance();
            }

            public function action() {
                $report = $this->load_embedded_report('manage_embedded_reports');

                return report_view::create_from_report($report)
                    ->set_title('This is the report title');
            }
        };

        $this->expectOutputRegex('/Saved search not found or search is not public/');
        $controller->process();
    }


}
