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
* @author Angela Kuznetsova <angela.kuznetsova@totaralearning.com>
* @package block_totara_user_profile
*/

function xmldb_block_totara_user_profile_install() {
    // This is a pattern to install User Profile block on default User Profile Page and user's profile pages
    // Any other blocks installed as default system block can be found in lib/blocklib.php/blocks_add_default_system_blocks()

    $blocksdata = [
        [
            'category' => 'contact',
            'defaultregion' => 'main',
            'defaultweight' => 1,
        ],
        [
            'category' => 'jobassignment',
            'defaultregion' => 'main',
            'defaultweight' => 2,
        ],
        [
            'category' => 'administration',
            'defaultregion' => 'side-post',
            'defaultweight' => 1,
        ],
        [
            'category' => 'mylearning',
            'defaultregion' => 'side-post',
            'defaultweight' => 2,
        ],
        [
            'category' => 'development',
            'defaultregion' => 'side-post',
            'defaultweight' => 3,
        ],
        [
            'category' => 'notifications',
            'defaultregion' => 'side-post',
            'defaultweight' => 4,
        ],
        [
            'category' => 'badges',
            'defaultregion' => 'side-pre',
            'defaultweight' => 1,
        ],
    ];
    $page = new moodle_page();
    $page->set_context(context_system::instance());
    $page->set_pagelayout('mypublic');
    $page->set_pagetype('user-profile');

    foreach ($blocksdata as $blockinfo) {
        $blockconfig = new stdClass();
        $blockconfig->category = $blockinfo['category'];
        $page->blocks->add_region($blockinfo['defaultregion'], false);
        $page->blocks->add_block(
            'totara_user_profile',
            $blockinfo['defaultregion'],
            $blockinfo['defaultweight'],
            0,
            'user-profile',
            1,
            $blockconfig);
    }
}
