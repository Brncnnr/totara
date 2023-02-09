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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package totara_notification
 */

defined('MOODLE_INTERNAL') || die;
use totara_notification\factory\capability_factory;

$ADMIN->add('root', new admin_category('totara_notification', new lang_string('messaging_and_notification', 'totara_notification')));

// Default to our own plugin's generic capabilities.
$notification_setup_capabilities = ['totara/notification:managenotifications', 'totara/notification:auditnotifications'];

// This script can be invoked during the installation. And we would not want to fetch the
// capabilities while the installation/upgrade is running, as it will have to invoke the cache
// metadata, and at this point of time cache metadata might not even be available.
if (!during_initial_install()) {
    // We are allowing the plugins that integrate with notification to add more capabilities
    // for admin setting page.
    $notification_setup_capabilities = array_merge(
        capability_factory::get_manage_capabilities(CONTEXT_SYSTEM),
        capability_factory::get_audit_capabilities(CONTEXT_SYSTEM)
    );
}

$ADMIN->add(
    'totara_notification',
    new admin_externalpage(
        'notifications_setup',
        new lang_string('notifications', 'totara_notification'),
        new moodle_url('/totara/notification/notifications.php'),
        $notification_setup_capabilities
    )
);