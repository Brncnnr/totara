<?php
/*
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
 * Block for displaying user profile details
 *
 * @author Angela Kuznetsova <angela.kuznetsova@totaralearning.com>
 * @package block_totara_user_profile
 */

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

/**
 * Block management step definition
 *
 */
class behat_user_profile_block extends behat_base {

    /**
     * Adds the selected User Profile block to the main region with default weight 2.
     *
     * @Given /^the "(?P<categoryname_string>(?:[^"]|\\")*)" user profile block exists$/
     * @param string $categoryname_string
     */
    public function the_user_profile_block_exists($categoryname) {
        $page = new moodle_page();
        $page->set_context(context_system::instance());
        $page->set_pagelayout('mypublic');
        $page->set_pagetype('user-profile');
        $blockconfig = new stdClass();
        //It takes category name (not a title!) of profile tree as an argument.
        $blockconfig->category = $categoryname;
        $page->blocks->add_region('main', false);
        $page->blocks->add_block(
            'totara_user_profile',
            'main',
            2,
            0,
            'user-profile',
            1,
            $blockconfig);
    }
}
