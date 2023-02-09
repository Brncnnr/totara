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

// This file keeps track of upgrades to
// the user_profile block


function xmldb_block_totara_user_profile_upgrade($oldversion, $block) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    // Totara 13.0 release line.

    if ($oldversion < 2020102300) {
        // Does the user profile currently show badges?
        $badgesshown = false;
        $blockinsts = $DB->get_records('block_instances', ['blockname' => 'totara_user_profile', 'pagetypepattern' => 'user-profile']);
        foreach ($blockinsts as $blockinst) {
            $block = block_instance('totara_user_profile', $blockinst);
            if ($block->config->category == 'badges') {
                $badgesshown = true;
                break;
            }
        }

        // Badges not currently shown so add then to the profile.
        if (!$badgesshown) {
            $blockinfo = [
                'category' => 'badges',
                'defaultregion' => 'side-pre',
                'defaultweight' => '1',
            ];
            $page = new moodle_page();
            $page->set_context(context_system::instance());
            $page->set_pagelayout('mypublic');
            $page->set_pagetype('user-profile');

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

        upgrade_plugin_savepoint(true, 2020102300, 'block', 'totara_user_profile');
    }

    if ($oldversion < 2022072600) {
        // Does the user profile currently show notifications?
        $notifs_shown = false;
        $blockinsts = $DB->get_records('block_instances', ['blockname' => 'totara_user_profile', 'pagetypepattern' => 'user-profile']);
        foreach ($blockinsts as $blockinst) {
            $block = block_instance('totara_user_profile', $blockinst);
            if ($block->config->category == 'notifications') {
                $notifs_shown = true;
                break;
            }
        }

        // Badges not currently shown so add then to the profile.
        if (!$notifs_shown) {
            $blockinfo = [
                'category' => 'notifications',
                'defaultregion' => 'side-post',
                'defaultweight' => '4',
            ];
            $page = new moodle_page();
            $page->set_context(context_system::instance());
            $page->set_pagelayout('mypublic');
            $page->set_pagetype('user-profile');

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
                $blockconfig
            );
        }

        upgrade_plugin_savepoint(true, 2022072600, 'block', 'totara_user_profile');
    }

    return true;
}
