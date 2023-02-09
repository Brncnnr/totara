<?php
/**
 * This file is part of Totara LMS
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
 * @author Simon Player <simon.player@totaralearning.com>
 * @package core
 */

defined('MOODLE_INTERNAL') || die();

/**
 * @var admin_root $ADMIN
 * @var bool $hassiteconfig
 */

if ($hassiteconfig) {

    /** @var admin_settingpage $adv_features_notifications */
    $adv_features_notifications = $ADMIN->locate('advancedfeatures_notifications');
    if ($adv_features_notifications) {
        $adv_features_notifications->add(
            new admin_setting_configcheckbox(
                'messaging',
                new lang_string('messaging', 'admin'),
                new lang_string('configmessaging', 'admin'),
                1
            )
        );

        $adv_features_notifications->add(
            new admin_setting_configcheckbox(
                'messaginghidereadnotifications',
                new lang_string('messaginghidereadnotifications', 'admin'),
                new lang_string('configmessaginghidereadnotifications', 'admin'),
                0
            )
        );

        $options = array(
            DAYSECS => new lang_string('secondstotime86400'),
            WEEKSECS => new lang_string('secondstotime604800'),
            2620800 => new lang_string('nummonths', 'moodle', 1),
            15724800 => new lang_string('nummonths', 'moodle', 6),
            0 => new lang_string('never')
        );
        $adv_features_notifications->add(
            new admin_setting_configselect(
                'messagingdeletereadnotificationsdelay',
                new lang_string('messagingdeletereadnotificationsdelay', 'admin'),
                new lang_string('configmessagingdeletereadnotificationsdelay', 'admin'),
                604800,
                $options
            )
        );

        $adv_features_notifications->add(
            new admin_setting_configcheckbox(
                'messagingallowemailoverride',
                new lang_string('messagingallowemailoverride', 'admin'),
                new lang_string('configmessagingallowemailoverride', 'admin'),
                0
            )
        );

        $adv_features_notifications->add(
            new admin_setting_configcheckbox(
                'notificationlogs',
                new lang_string('enablenotificationlogs', 'totara_notification'),
                new lang_string('enablenotificationlogs_help', 'totara_notification'),
                1
            )
        );

        $adv_features_notifications->add(
            new admin_setting_configtext(
                'totara_notification_log_days_to_keep',
                new lang_string('totara_notification_log_days_to_keep', 'totara_notification'),
                new lang_string('totara_notification_log_days_to_keep_help', 'totara_notification'),
                30,
                PARAM_INT
            )
        );
    }
}