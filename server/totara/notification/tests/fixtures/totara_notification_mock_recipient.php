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

use totara_notification\recipient\recipient;

class totara_notification_mock_recipient implements recipient {
    /**
     * The key to set to the array event data.
     * @var string
     */
    public const RECIPIENT_IDS_KEY = 'recipient_ids';

    /**
     * @return string
     */
    public static function get_name(): string {
        return 'mock';
    }

    /**
     * @inheritDoc
     */
    public static function get_user_ids(array $data): array {
        if (!isset($data[static::RECIPIENT_IDS_KEY])) {
            return [];
        }

        // Let the php native to evaluate the data type at key RECIPIENT_IDS_KEY
        return $data[static::RECIPIENT_IDS_KEY];
    }
}