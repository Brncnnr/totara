<?php
/**
 * This file is part of Totara Core
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package totara_core
 */

use \totara_core\quickaccessmenu\helper;

/**
 * @group totara_core
 */
class totara_core_quickaccessmenu_helper_testcase extends \core_phpunit\testcase {

    public static function setUpBeforeClass(): void {
        global $CFG;
        // Required for admin_get_root used in forceAdminReload.
        require_once($CFG->libdir . '/adminlib.php');
    }

    private function force_admin_reload(): void {
        // This mirrors how the admin quickaccessmenu will call it.
        admin_get_root(true, false);
    }

    public function test_add_quickaction_page_button_guest() {
        $this->setGuestUser();
        $this->force_admin_reload();
        $page = new moodle_page();

        // Edit users does NOT exist in the admins menu.
        helper::add_quickaction_page_button($page, 'editusers', new moodle_url('/test.php'));

        $url = new moodle_url('/user/quickaccessmenu.php');
        $returnurl = helper::get_quickaction_returnurl();
        self::assertInstanceOf(moodle_url::class, $returnurl);
        self::assertSame($url->out(), $returnurl->out());

        self::assertEmpty($page->button);
    }

    public function test_add_quickaction_page_button_authenticated_user() {
        $this->setUser($this->getDataGenerator()->create_user());
        $this->force_admin_reload();
        $page = new moodle_page();

        // Edit users does NOT exist in the admins menu.
        helper::add_quickaction_page_button($page, 'editusers', new moodle_url('/test.php'));

        $url = new moodle_url('/test.php');
        $returnurl = helper::get_quickaction_returnurl();
        self::assertInstanceOf(moodle_url::class, $returnurl);
        self::assertSame($url->out(), $returnurl->out());

        self::assertStringContainsString('Add to admin menu', $page->button);
    }

    public function test_add_quickaction_page_button_admin() {
        $this->setAdminUser();
        $this->force_admin_reload();
        $page = new moodle_page();

        // Edit users exists in the admins menu.
        helper::add_quickaction_page_button($page, 'editusers', new moodle_url('/test.php'));

        $url = new moodle_url('/test.php');
        $returnurl = helper::get_quickaction_returnurl();
        self::assertInstanceOf(moodle_url::class, $returnurl);
        self::assertSame($url->out(), $returnurl->out());

        self::assertStringContainsString('Remove from admin menu', $page->button);
    }

}