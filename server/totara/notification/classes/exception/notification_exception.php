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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_notification
 */
namespace totara_notification\exception;

use moodle_exception;

class notification_exception extends moodle_exception {
    /**
     * notification_exception constructor.
     * @param string     $error_code
     */
    private function __construct(string $error_code) {
        parent::__construct($error_code, 'totara_notification');
    }

    /**
     * @return notification_exception
     */
    public static function on_manage(): notification_exception {
        return new static('error_manage_notification');
    }

    /**
     * @return notification_exception
     */
    public static function on_audit(): notification_exception {
        return new static('error_audit_notification');
    }
}