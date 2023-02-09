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
* @author Chris Snyder <chris.snyder@totaralearning.com>
* @package block_settings
*/

function xmldb_block_settings_install() {
    // Install the settings block in the correct region for the default user profile page.
    $page = new moodle_page();
    $page->blocks->add_region('side-pre', false);
    $page->blocks->add_block('settings', 'side-pre', 0, 1, '*');
}