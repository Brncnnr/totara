<?php
/**
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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface\totara_notification;

use mod_facetoface\seminar;
use totara_notification\external_helper;
use totara_notification\resolver\notifiable_event_resolver;

require_once($CFG->dirroot.'/mod/facetoface/notification/lib.php');

class seminar_notification_helper {

    /**
     * Use centralised notifications?
     *
     * @param seminar $seminar
     * @return bool
     */
    public static function use_cn_notifications(seminar $seminar) : bool {
        return !facetoface_site_allows_legacy_notifications()
            || (facetoface_site_allows_legacy_notifications() && $seminar->use_cn_notifications());
    }

    public static function create_seminar_notifiable_event_queue(seminar $seminar, notifiable_event_resolver $resolver) {
        if (self::use_cn_notifications($seminar)) {
            external_helper::create_notifiable_event_queue($resolver);
        }
    }

}