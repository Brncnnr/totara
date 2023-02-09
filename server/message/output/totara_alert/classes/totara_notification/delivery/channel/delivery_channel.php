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
 * @author  Cody Finegan <cody.finegan@totaralearning.com>
 * @package message_totara_alert
 */

namespace message_totara_alert\totara_notification\delivery\channel;

use message_popup\totara_notification\delivery\channel\delivery_channel as popup_delivery_channel;
use totara_notification\delivery\channel\delivery_channel as base_delivery_channel;

/**
 * Class alert_delivery_channel
 *
 * @package message_totara_alert\totara_notification\delivery\channel
 */
class delivery_channel extends base_delivery_channel {
    /**
     * @inheritDoc
     */
    public static function get_label(): string {
        return get_string('delivery_channel_label', 'message_totara_alert');
    }

    /**
     * @return string|null
     */
    public static function get_parent(): ?string {
        return popup_delivery_channel::get_component();
    }

    /**
     * @return int
     */
    public static function get_display_order(): int {
        return 40;
    }
}