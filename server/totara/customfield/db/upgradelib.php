<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author  Maria Torres <maria.torres@totaralearning.com>
 * @package totara_customfield
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Fix Auth orphaned settings resulted of updating custom profile fields.
 */
function totara_customfield_upgrade_remove_auth_orphaned_settings() {
    global $DB;

    $keyword = '_profile_field_';
    $mapping_config_names = [
        'field_map_profile_field_',
        'field_updatelocal_profile_field_',
        'field_updateremote_profile_field_',
        'field_lock_profile_field_'
    ];

    // Get current custom profile fields shortnames.
    $custom_profile_fields = $DB->get_fieldset_select('user_info_field', 'shortname', "1 = 1");

    // Get all auth plugins.
    $auth_plugins = core_plugin_manager::instance()->get_plugins_of_type('auth');
    foreach ($auth_plugins as $plugin) {
        /** @var \core\plugininfo\auth $plugin */
        $pluginname = 'auth_' . $plugin->name;
        $config = get_config($pluginname);
        foreach ($config as $name => $value) {
            $position = strpos($name, $keyword);
            if ($position !== FALSE) {
                // Let's check it's a setting corresponding to the mapping for custom profile fields.
                $config_name = substr($name, 0, $position+strlen($keyword));
                if (in_array($config_name, $mapping_config_names)) {
                    // If the mapping setting name does not correspond with a current custom profile field, remove it.
                    $cf_shortname = substr($name, strlen($config_name), strlen($name));
                    if (!in_array($cf_shortname, $custom_profile_fields)) {
                        add_to_config_log($name, '', $value, $pluginname);
                        unset_config($name, $pluginname);
                    }
                }
            }
        }
    }
}
