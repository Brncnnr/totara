<?php
/**
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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package message_msteams
 */

/**
 * Install the Microsoft Teams message processor.
 */
function xmldb_message_msteams_install(){
    global $DB;

    $provider = new stdClass();
    $provider->name  = 'msteams';

    // Register new message processor if it is not registered already.
    if (!$DB->record_exists('message_processors', ['name' => $provider->name])) {
        $DB->insert_record('message_processors', $provider);
    }

    return true;
}
