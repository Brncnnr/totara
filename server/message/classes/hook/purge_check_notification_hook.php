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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package core_message
 */
namespace core_message\hook;

use core\entity\notification;
use totara_core\hook\base;

class purge_check_notification_hook extends base {
    /**
     * @var notification
     */
    private $notification;

    /**
     * Default this field to TRUE, and the watcher can mark this property as
     * FALSE to skip the purging of the notification record.
     * @var bool
     */
    private $to_be_purged;

    /**
     * purge_check_notification_hook constructor.
     * @param notification $notification
     */
    public function __construct(notification $notification) {
        $this->notification = $notification;
        $this->to_be_purged = true;
    }

    /**
     * @return notification
     */
    public function get_notification(): notification {
        return $this->notification;
    }

    /**
     * @return void
     */
    public function mark_skip_purge(): void {
        $this->to_be_purged = false;
    }

    /**
     * @return bool
     */
    public function is_skip_purge(): bool {
        return !$this->to_be_purged;
    }
}