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

use totara_mvc\admin_controller;
use totara_mvc\viewable;

defined('MOODLE_INTERNAL') || die();


class totara_mvc_admin_controller_testcase extends advanced_testcase {

    /**
     * @var admin_root
     */
    protected $admin_menu;

    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();
        global $CFG;
        require_once($CFG->libdir.'/adminlib.php');
    }

    protected function setUp(): void {
        $this->setAdminUser();

        // initiate admin menu only once for performance reasons
        $this->admin_menu = admin_get_root(true, false);
        $this->admin_menu->add(
            'root',
            new admin_category(
                'test_admin_category',
                'test admin category'
            )
        );
        $this->admin_menu->add(
            'test_admin_category',
            new admin_externalpage(
                'test_admin_page',
                'test admin title',
                "/admin/index.php"
            )
        );
        parent::setUp();
    }

    protected function tearDown(): void {
        parent::tearDown();

        // We cannot completely reset the menu as it is a singleton
        // but at least we can purge/reset it's content
        $this->admin_menu->purge_children(false);
        $this->admin_menu = null;
    }

    public function test_missing_page_name() {
        $controller = new my_admin_test_controller();

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Missing external page name in controller.');

        $controller->process();
    }

    public function test_external_page() {
        $controller = new class() extends my_admin_test_controller {
            protected $admin_external_page_name = 'test_admin_page';

            public function action() {
                return new class() implements viewable {
                    public function render(): string {
                        return '';
                    }
                };
            }
        };

        $controller->process();

        $page = $controller->get_page();
        $this->assertEquals('admin', $page->pagelayout);
        $this->assertEquals(new moodle_url('/admin/index.php'), $page->url);
    }

    public function test_with_custom_layout() {
        $controller = new class() extends my_admin_test_controller {
            protected $admin_external_page_name = 'test_admin_page';

            protected $layout = 'noblocks';

            public function action() {
                return new class() implements viewable {
                    public function render(): string {
                        return '';
                    }
                };
            }
        };

        $controller->process();

        $page = $controller->get_page();
        $this->assertEquals('noblocks', $page->pagelayout);
    }

    public function test_with_additional_params() {
        $controller = new class() extends my_admin_test_controller {
            protected $admin_external_page_name = 'test_admin_page';

            protected $admin_actual_url = '/course/index.php';

            protected $admin_extra_url_params = [
                'extra1' => 'value1',
                'extra2' => 'value2',
            ];

            public function action() {
                return new class() implements viewable {
                    public function render(): string {
                        return '';
                    }
                };
            }
        };

        $controller->process();

        // Actual url should have been set not the one defined in the menu item
        $url = new moodle_url('/course/index.php', ['extra1' => 'value1', 'extra2' => 'value2']);
        $this->assertEquals($url, $controller->get_page()->url);
    }

}


class my_admin_test_controller extends admin_controller {

    protected function setup_context(): context {
        return context_system::instance();
    }

}