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
 * @author Simon Coggins <simon.coggins@totaralearning.com>
 * @package mod_perform
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_perform_install() {
    global $CFG;
    require_once($CFG->dirroot . '/totara/core/db/upgradelib.php');

    totara_core_upgrade_create_relationship(['mod_perform\relationship\resolvers\peer'], 'perform_peer', 5, 1, 'mod_perform');
    totara_core_upgrade_create_relationship(['mod_perform\relationship\resolvers\mentor'], 'perform_mentor', 6, 1, 'mod_perform');
    totara_core_upgrade_create_relationship(['mod_perform\relationship\resolvers\reviewer'], 'perform_reviewer', 7, 1, 'mod_perform');
    totara_core_upgrade_create_relationship(['mod_perform\relationship\resolvers\external'], 'perform_external', 9, 1, 'mod_perform');

    // Create activity types.
    mod_perform\util::create_activity_types();
}
