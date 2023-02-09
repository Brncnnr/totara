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
 * @author  Qingyang liu <Qingyang.liu@totaralearning.com>
 * @package totara_notification
 */

use totara_core\totara_user as ext_user;
use totara_notification\recipient\recipient;
use totara_notification\recipient\virtual_recipient;

class totara_notification_mock_virtual_recipient implements recipient, virtual_recipient {

    /**
     * @return string
     */
    public static function get_name(): string {
        return 'mock virtual recipients';
    }

    /**
     * @inheritDoc
     */
    public static function get_user_ids(array $data): array {
        return [];
    }

    public static function get_user_objects(array $data): array {
        // Feel free to extend this in future to allow tests to specify external recipients.
        return [
            ext_user::get_external_user('example@test.com')
        ];
    }
}