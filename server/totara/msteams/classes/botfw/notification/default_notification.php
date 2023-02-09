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

use coding_exception;
use core\orm\query\builder;
use core_user;
use totara_msteams\botfw\account\channel_account;
use totara_msteams\botfw\entity\bot;
use totara_msteams\botfw\entity\subscription as subscription_entity;
use totara_msteams\botfw\entity\tenant;
use totara_msteams\botfw\entity\user;
use totara_msteams\botfw\exception\bot_unavailable_exception;
use totara_msteams\botfw\exception\user_not_found_exception;

/**
 * The default implementation of notification.
 */
class default_notification implements notification {
    /**
     * @inheritDoc
     */
    public function subscribe(user $msuser, string $tenant_id, channel_account $bot_account): bool {
        $mstenant = self::load_or_add_tenant($tenant_id);
        $msbot = self::load_bot($bot_account);
        if (!$msbot) {
            throw new bot_unavailable_exception();
        }
        $mssubscription = subscription_entity::repository()->where('msuserid', $msuser->id)->where('mstenantid', $mstenant->id)->one(false);
        if ($mssubscription) {
            return false;
        }
        $mssubscription = new subscription_entity();
        $mssubscription->msbotid = $msbot->id;
        $mssubscription->mstenantid = $mstenant->id;
        $mssubscription->msuserid = $msuser->id;
        $mssubscription->save();
        return true;
    }

    /**
     * @inheritDoc
     */
    public function unsubscribe(user $msuser, string $tenant_id): bool {
        $mstenant = self::load_tenant($tenant_id);
        if (!$mstenant) {
            return false;
        }
        $mssubscription = subscription_entity::repository()->where('msuserid', $msuser->id)->where('mstenantid', $mstenant->id)->one(false);
        if (!$mssubscription) {
            return false;
        }
        $mssubscription->delete();
        return true;
    }

    /**
     * @inheritDoc
     */
    public function get_subscription(user $msuser, string $tenant_id, string $channel_id, string $bot_id): ?subscription {
        $record = self::subscription_builder()
            ->where('m.id', $msuser->id)
            ->where('t.tenant_id', $tenant_id)
            ->where('c.channel_id', $channel_id)
            ->where('b.bot_id', $bot_id)
            ->one(false);
        if (!$record) {
            return null;
        }
        return subscription::from_record($record);
    }

    /**
     * @inheritDoc
     */
    public function get_subscriptions(int $userid): array {
        if (empty($userid)) {
            throw new coding_exception('userid must be set');
        }
        $user = core_user::get_user($userid, 'id', IGNORE_MISSING);
        if (!$user) {
            throw new user_not_found_exception();
        }
        $records = self::subscription_builder()
            ->where('m.userid', $user->id)
            ->get()
            ->all();
        return array_map(function ($record) {
            return subscription::from_record($record);
        }, $records);
    }

    /**
     * @param channel_account $bot_account
     * @return bot|null
     */
    private static function load_bot(channel_account $bot_account): ?bot {
        return bot::repository()->where('bot_id', $bot_account->id)->one(false);
    }

    /**
     * @param string $tenant_id
     * @return tenant|null
     */
    private static function load_tenant(string $tenant_id): ?tenant {
        return tenant::repository()->where('tenant_id', $tenant_id)->one(false);
    }

    /**
     * @param string $tenant_id
     * @return tenant
     */
    private static function load_or_add_tenant(string $tenant_id): tenant {
        $tenant = self::load_tenant($tenant_id);
        if (!$tenant) {
            $tenant = new tenant();
            $tenant->tenant_id = $tenant_id;
            $tenant->save();
        }
        return $tenant;
    }

    /**
     * Return the query builder with joining the following tables:
     * - s: totara_msteams_subscription
     * - m: totara_msteams_user
     * - t: totara_msteams_tenant
     * - b: totara_msteams_bot
     * - c: totara_msteams_channel
     * - u: user
     *
     * @return builder
     */
    private static function subscription_builder(): builder {
        return builder::table('totara_msteams_subscription', 's')
            ->join(['totara_msteams_user', 'm'], 's.msuserid', 'm.id')
            ->join(['totara_msteams_tenant', 't'], 's.mstenantid', 't.id')
            ->join(['totara_msteams_bot', 'b'], 's.msbotid', 'b.id')
            ->join(['totara_msteams_channel', 'c'], 'm.mschannelid', 'c.id')
            ->join(['user', 'u'], 'u.id', 'm.userid')
            ->select(['s.id', 's.conversation_id', 'm.userid', 'u.lang', 'm.teams_id', 'c.channel_id', 't.tenant_id', 'b.id AS msbotid', 'b.bot_id', 'b.bot_name', 'b.service_url']);
    }
}
