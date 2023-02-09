<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package totara_msteams
 */

namespace totara_msteams\botfw\notification;

use totara_msteams\botfw\account\channel_account;
use totara_msteams\botfw\entity\user;
use totara_msteams\botfw\exception\botfw_exception;

/**
 * An interface to manage notification subscriptions.
 */
interface notification {
    /**
     * Register notification.
     *
     * @param user $msuser A user to subscribe
     * @param string $tenant_id A user's tenant id
     * @param channel_account $bot_account A bot account
     * @return boolean false if a user has already subscribed
     * @throws botfw_exception
     */
    public function subscribe(user $msuser, string $tenant_id, channel_account $bot_account): bool;

    /**
     * Unregister notification.
     *
     * @param user $msuser A user to unsubscribe
     * @param string $tenant_id A user's tenant id
     * @return boolean false if a user has already unsubscribed
     */
    public function unsubscribe(user $msuser, string $tenant_id): bool;

    /**
     * Get the associated subscription record.
     *
     * @param user $msuser user record
     * @param string $tenant_id A user's tenant id
     * @param string $channel_id A channel id
     * @param string $bot_id A bot id
     * @return subscription|null
     */
    public function get_subscription(user $msuser, string $tenant_id, string $channel_id, string $bot_id): ?subscription;

    /**
     * Get the array of subscriptions of a user.
     *
     * @param integer $userid A user's Totara ID
     * @return subscription[]
     */
    public function get_subscriptions(int $userid): array;
}
