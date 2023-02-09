<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 * @package totara_customfield
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Totara customfield event handler.
 */
class totara_customfield_observer {
    /**
     * Triggered via course_deleted event.
     * - Removes course customfield data
     *
     * @param \core\event\course_deleted $event
     * @return bool true on success
     */
    public static function course_deleted(\core\event\course_deleted $event) {
        global $DB;
        $DB->get_records('course_info_data', array('courseid' => $event->objectid));

        $fields = $DB->get_fieldset_select(
            'course_info_data',
            'id',
            "courseid = :courseid",
            array('courseid' => $event->objectid)
        );

        if (!empty($fields)) {
            list($sqlin, $paramsin) = $DB->get_in_or_equal($fields);
            $DB->delete_records_select('course_info_data_param', "dataid {$sqlin}", $paramsin);
            $DB->delete_records_select('course_info_data', "id {$sqlin}", $paramsin);
        }

        return true;
    }

    /**
     * Triggered via program_deleted event.
     * - Removes program customfield data
     *
     * @param \totara_program\event\program_deleted $event
     * @return bool true on success
     */
    public static function program_deleted(\totara_program\event\program_deleted $event) {
        global $DB;

        $fields = $DB->get_fieldset_select(
            'prog_info_data',
            'id',
            "programid = :programid",
            array('programid' => $event->objectid)
        );

        if (!empty($fields)) {
            list($sqlin, $paramsin) = $DB->get_in_or_equal($fields);
            $DB->delete_records_select('prog_info_data_param', "dataid {$sqlin}", $paramsin);
            $DB->delete_records_select('prog_info_data', "id {$sqlin}", $paramsin);
        }

        return true;
    }

    /**
     * Triggered via profilefield_updated event.
     * Updates auth data mapping settings when a custom profile field is updated.
     *
     * @param \totara_customfield\event\profilefield_updated $event
     * @return bool true on success
     */
    public static function update_auth_settings(\totara_customfield\event\profilefield_updated $event) {
        $eventinfo = $event->get_info();

        if (!empty($eventinfo->oldshortname) && $eventinfo->oldshortname != $eventinfo->shortname) {
            $config_names = [
                'field_map_profile_field_',
                'field_updatelocal_profile_field_',
                'field_updateremote_profile_field_',
                'field_lock_profile_field_'
            ];

            $auth_plugins = core_plugin_manager::instance()->get_plugins_of_type('auth');
            foreach ($auth_plugins as $plugin) {
                /** @var \core\plugininfo\auth $plugin */
                $pluginname = 'auth_' . $plugin->name;
                foreach ($config_names as $config_name) {
                    // Get value of existing setting.
                    $configvalue = get_config($pluginname, $config_name . $eventinfo->oldshortname);
                    if ($configvalue !== FALSE) {
                        add_to_config_log($config_name . $eventinfo->oldshortname, '', $configvalue, $pluginname);
                        unset_config($config_name . $eventinfo->oldshortname, $pluginname);
                        set_config($config_name . $eventinfo->shortname, $configvalue, $pluginname);
                    }
                }
            }
        }

        return true;
    }
}
