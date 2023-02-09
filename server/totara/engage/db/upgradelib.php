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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_engage
 */
defined('MOODLE_INTERNAL') || die();

use totara_core\advanced_feature;

function totara_engage_create_engage_profile_block(): void {
    // Must have either behat or the feature enabled
    $behat = defined('BEHAT_SITE_RUNNING') && BEHAT_SITE_RUNNING;
    $feature = advanced_feature::is_enabled('engage_resources');
    if (!$behat && !$feature) {
        return;
    }

    // Install the profile block with category as engage.
    $block_config = new stdClass();
    $block_config->category = 'engage';

    $page = new moodle_page();
    $page->set_context(context_system::instance());
    $page->set_pagelayout('mypublic');
    $page->set_pagetype('user-profile');

    $page->blocks->add_region('main', false);
    $page->blocks->add_block(
        'totara_user_profile',
        'main',
        1,
        0,
        'user-profile',
        1,
        $block_config
    );
}

/**
 * @return void
 */
function totara_engage_set_context_id_for_resource(): void {
    global $DB;

    foreach ($DB->get_records_select('engage_resource', 'contextid IS NULL') as $record) {
        if (is_null($record->contextid)) {
            $context_id = (context_user::instance($record->userid))->id;
            $DB->execute(
                'UPDATE {engage_resource} SET contextid = :context_id WHERE id = :id',
                ['context_id' => $context_id, 'id' => $record->id]
            );
        }
    }
}