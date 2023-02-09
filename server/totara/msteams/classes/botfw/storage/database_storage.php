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

namespace totara_msteams\botfw\storage;

use coding_exception;
use stdClass;
use totara_msteams\botfw\entity\bot;
use totara_msteams\botfw\entity\bot_setting;
use totara_msteams\botfw\entity\user_setting;
use totara_msteams\botfw\storage\traits\storage_bot;

/**
 * A storage class where data is stored in a database.
 */
class database_storage implements storage {
    use storage_bot;

    /**
     * Constructor.
     *
     * @param string $bot_app_id the bot app id
     * @param string $bot_app_secret the bot app secret
     */
    public function __construct(string $bot_app_id = '', string $bot_app_secret = '') {
        $this->initialise_bot($bot_app_id, $bot_app_secret);
    }

    /**
     * @inheritDoc
     */
    public function bot_load(string $key): ?stdClass {
        $set = bot_setting::repository()->load($this->context->get_bot_id(), $key, false);
        if (!$set) {
            return null;
        }
        return @json_decode($set->data);
    }

    /**
     * @inheritDoc
     */
    public function bot_store(string $key, stdClass $data): void {
        $set = bot_setting::repository()->load($this->context->get_bot_id(), $key, false);
        if (!$set) {
            $set = new bot_setting();
            $set->msbotid = bot::repository()->find_by_id($this->context->get_bot_id(), true)->id;
            $set->area = $key;
            $set->timecreated = $set->timecreated ?: time();
        }
        $set->data = json_encode($data, JSON_UNESCAPED_SLASHES);
        $set->timemodified = time();
        $set->save();
    }

    /**
     * @inheritDoc
     */
    public function user_load(int $userid, string $key): ?stdClass {
        if (empty($userid)) {
            throw new coding_exception('userid cannot be zero');
        }
        $setting = user_setting::repository()->load($userid, $key, false);
        $data = null;
        if ($setting && !empty($setting->data)) {
            $data = @json_decode($setting->data);
        }
        return $data;
    }

    /**
     * @inheritDoc
     */
    public function user_store(int $userid, string $key, stdClass $data): void {
        if (empty($userid)) {
            throw new coding_exception('userid cannot be zero');
        }
        $setting = user_setting::repository()->load($userid, $key, false);
        if (!$setting) {
            $setting = new user_setting();
            $setting->userid = $userid;
            $setting->area = $key;
            $setting->timecreated = time();
        }
        $setting->data = json_encode($data, JSON_UNESCAPED_SLASHES);
        $setting->timemodified = time();
        $setting->save();
    }
}
