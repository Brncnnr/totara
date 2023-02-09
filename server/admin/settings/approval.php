<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author Maria Torres <maria.torres@totaralearning.com>
 * @package mod_approval
 */


use totara_core\advanced_feature;

defined('MOODLE_INTERNAL') || die();
/** @var admin_root $ADMIN */

// Allow approval to be added to the menu without having site:config or mod:config caps

$enabled_plugins = core_plugin_manager::instance()->get_enabled_plugins('mod');
if (isset($enabled_plugins['approval'])) {
    \mod_approval\settings\settings::init_admin_settings($ADMIN);

    if ($hassiteconfig) {
        // Add the on/off switch.
        $experimental_settings = $ADMIN->locate('experimentalsettings');
        $setting = new totara_core_admin_setting_feature_checkbox(
            'enableapproval_workflows',
            new lang_string('enableapproval_workflows', 'mod_approval'),
            new lang_string('enableapproval_workflows_desc', 'mod_approval'),
            advanced_feature::DISABLED
        );
        $setting->set_updatedcallback('purge_all_caches');
        $experimental_settings->add($setting);
    }
}
