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
 * @author David Curry <david.curry@totaralearning.com>
 * @package core
 * @subpackage my
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/my/lib.php');

/**
 * Test core my lib functions
 */
class core_my_lib_testcase extends advanced_testcase {

    /**
     * Create a mock custom page
     *
     * @param $user the user object for the user customising the page
     * @param int $private Either MY_PAGE_PRIVATE or MY_PAGE_PUBLIC.
     * @param string $pagetype Either my-index or user-profile.
     * @return void
     */
    private function mock_page_customisation($user, $public = MY_PAGE_PUBLIC, $pagetype = 'user-profile') {
        global $DB;

        // Create a custom page.
        $page = new stdClass();
        $page->userid = $user->id;
        $page->name = '__default';
        $page->private = $public;
        $page->sortorder = 0;
        $page->id = $DB->insert_record('my_pages', $page);

        $now = time();
        $context = context_user::instance($user->id);

        // Add a couple of blocks to this custom page.
        for ($i = 0; $i < 2; $i++) {
            $block = new stdClass();
            $block->blockname = 'html';
            $block->parentcontextid = $context->id;
            $block->showinsubcontexts = 0;
            $block->requiredbytheme = 0;
            $block->pagetypepattern = $pagetype;
            $block->subpagepattern = $page->id;
            $block->defaultregion = 'side-pre';
            $block->defaultweight = $i;
            $block->configdata = null;
            $block->commonconfig = null;
            $block->timemodified = $now;
            $block->timecreated = $now;
            $block->id = $DB->insert_record('block_instances', $block);
        }
    }

    /**
     * Test the return value of the my_count_all_custom_pages lib function.
     */
    public function test_my_count_all_custom_pages() {
        $gen = $this->getDataGenerator();
        $user1 = $gen->create_user(['username' => 'user1']);
        $user2 = $gen->create_user(['username' => 'user2']);
        $user3 = $gen->create_user(['username' => 'user3']);

        $this->assertSame(0, my_count_all_custom_pages(MY_PAGE_PUBLIC, 'user-profile'));

        $this->mock_page_customisation($user1, MY_PAGE_PUBLIC, 'user-profile');

        $this->assertSame(1, my_count_all_custom_pages(MY_PAGE_PUBLIC, 'user-profile'));

        $this->mock_page_customisation($user2, MY_PAGE_PUBLIC, 'user-profile');
        $this->mock_page_customisation($user2, MY_PAGE_PRIVATE, 'my-index');

        $this->assertSame(2, my_count_all_custom_pages(MY_PAGE_PUBLIC, 'user-profile'));
        $this->assertSame(1, my_count_all_custom_pages(MY_PAGE_PRIVATE, 'my-index'));

        my_reset_page_for_all_users(MY_PAGE_PUBLIC, 'user-profile');

        $this->assertSame(0, my_count_all_custom_pages(MY_PAGE_PUBLIC, 'user-profile'));
        $this->assertSame(1, my_count_all_custom_pages(MY_PAGE_PRIVATE, 'my-index'));
    }
}
