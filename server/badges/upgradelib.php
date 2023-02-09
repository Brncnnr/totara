<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Contains upgrade and install functions for badges.
 *
 * @package    core_badges
 * @copyright  2019 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Called on install or upgrade to create default list of backpacks a user can connect to.
 * Don't use the global defines from badgeslib because this is for install/upgrade.
 *
 * @return void
 */
function badges_install_default_backpacks() {
    global $DB;

    $record = new stdClass();
    $record->backpackapiurl = 'https://api.badgr.io/v2';
    $record->backpackweburl = 'https://badgr.com';
    $record->apiversion = 2;
    $record->sortorder = 1;
    $record->password = '';

    $bp = $DB->get_record('badge_external_backpack', ['backpackapiurl' => $record->backpackapiurl]);
    if ($bp) {
        $bpid = $bp->id;
    } else {
        $bpid = $DB->insert_record('badge_external_backpack', $record);
    }

    set_config('badges_site_backpack', $bpid);

    // Set external backpack to v2.
    $DB->set_field('badge_backpack', 'externalbackpackid', $bpid);

    // Add in the additional badger backpacks
    $badgr_regions = [
        'https://eu.badgr.com' => 'https://api.eu.badgr.io/v2',
        'https://ca.badgr.com' => 'https://api.ca.badgr.io/v2',
        'https://au.badgr.com' => 'https://api.au.badgr.io/v2',
    ];
    foreach ($badgr_regions as $web => $api) {
        $backpack = new \stdClass();
        $backpack->backpackapiurl = $api;
        $backpack->backpackweburl = $web;
        $backpack->apiversion = 2;
        $backpack->sortorder = 1;
        $backpack->password = '';

        if (!$DB->record_exists_select(
            'badge_external_backpack',
            'backpackapiurl = :api OR backpackweburl = :web',
            compact('api', 'web')
        )) {
            $DB->insert_record('badge_external_backpack', $backpack);
        }
    }
}
