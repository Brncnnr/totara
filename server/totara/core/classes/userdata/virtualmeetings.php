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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package totara_core
 */

namespace totara_core\userdata;

use context;
use core\orm\query\builder;
use core\plugininfo\virtualmeeting;
use totara_core\entity\virtual_meeting as virtual_meeting_entity;
use totara_core\entity\virtual_meeting_auth as virtual_meeting_auth_entity;
use totara_userdata\userdata\export;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

/**
 * Export, count and purge personal data related to virtual meeting.
 * Note that auth tokens and configs are not exported to minimise the security risk.
 */
class virtualmeetings extends item {
    /**
     * Get the builder for exportable, countable and purgeable virtualmeeting entries.
     *
     * @param target_user $user
     * @return builder
     */
    protected static function get_builder_virtual_meeting(target_user $user): builder {
        return builder::table(virtual_meeting_entity::TABLE)->where('userid', $user->id);
    }

    /**
     * Get the builder for purgeable virtualmeeting_auth entries.
     *
     * @param target_user $user
     * @return builder
     */
    protected static function get_builder_virtual_meeting_auth(target_user $user): builder {
        return builder::table(virtual_meeting_auth_entity::TABLE)->where('userid', $user->id);
    }

    /**
     * @inheritDoc
     */
    public static function is_purgeable(int $userstatus) {
        return true;
    }

    /**
     * @inheritDoc
     */
    public static function is_countable() {
        return true;
    }

    /**
     * @inheritDoc
     */
    public static function is_exportable() {
        return true;
    }

    /**
     * @inheritDoc
     */
    protected static function purge(target_user $user, context $context) {
        // NOTE: config records are cascade-deleted.
        self::get_builder_virtual_meeting($user)->delete();
        self::get_builder_virtual_meeting_auth($user)->delete();
        return parent::RESULT_STATUS_SUCCESS;
    }

    /**
     * @inheritDoc
     */
    protected static function count(target_user $user, context $context) {
        return self::get_builder_virtual_meeting($user)->count();
    }

    /**
     * @inheritDoc
     */
    protected static function export(target_user $user, context $context) {
        $plugins = virtualmeeting::get_all_plugins();
        $export = new export();
        $export->data['instances'] = self::get_builder_virtual_meeting($user)
            ->select(['id', 'plugin', 'timecreated', 'timemodified'])
            ->map_to(function ($item) use ($plugins) {
                // Append a human-readable plugin name
                if (isset($plugins[$item->plugin])) {
                    $item->provider = $plugins[$item->plugin]->get_name();
                } else {
                    $item->provider = '';
                }
                return $item;
            })
            ->get()
            ->all();
        return $export;
    }
}
