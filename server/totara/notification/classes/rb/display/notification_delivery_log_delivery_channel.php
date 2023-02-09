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
 * @author Gihan Hewaralalage <gihan.hewaralalage@totaralearning.com>
 * @package totara_notification
 */

namespace totara_notification\rb\display;

use message_email\totara_notification\delivery\channel\delivery_channel as email_delivery_channel;
use rb_column;
use reportbuilder;
use stdClass;
use totara_notification\delivery\channel_helper;
use totara_reportbuilder\rb\display\base;

class notification_delivery_log_delivery_channel extends base {

    /**
     * @param string $delivery_channel
     * @param $format
     * @param stdClass $row
     * @param rb_column $column
     * @param reportbuilder $report
     * @return string
     */
    public static function display($delivery_channel, $format, stdClass $row, rb_column $column, reportbuilder $report) {
        $delivery_channel =  is_null($delivery_channel) ? '' : $delivery_channel;
        $extrafields = self::get_extrafields_row($row, $column);
        $address =  is_null($extrafields->address) ? '' : $extrafields->address;

        if ($delivery_channel == 'email') {
            $channel_label = email_delivery_channel::get_label();
            return get_string('delivery_channel_email', 'rb_source_notification_delivery_log', ['address' => $address, 'channel_label' => $channel_label]);
        } else {
            return channel_helper::get_label($delivery_channel);
        }
    }
}