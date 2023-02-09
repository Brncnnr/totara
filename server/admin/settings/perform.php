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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package mod_perform
 */


defined('MOODLE_INTERNAL') || die();

/** @var admin_root $ADMIN */
/** @var context_system $systemcontext */

// TODO This is just a preliminary code to allow perform to be added to the menu without having site:config or mod:config caps -
//      see TL-24292 for more details

$is_perform_installed = (core_component::get_component_directory('mod_perform') !== null);
$is_perform_enabled = totara_core\advanced_feature::is_enabled('performance_activities');

// Make sure for now that this does not fall over if the plugin would not exist
if ($is_perform_installed && $is_perform_enabled) {
    $ADMIN->add(
        'root',
        new admin_category('performactivities', new lang_string('performactivities', 'admin')),
        'appraisals'
    );

    \mod_perform\settings::init_public_settings($ADMIN);
}
