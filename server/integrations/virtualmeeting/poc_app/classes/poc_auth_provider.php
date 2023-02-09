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
 * @package virtualmeeting_poc_app
 */

namespace virtualmeeting_poc_app;

use core\entity\user;
use core_user;
use moodle_url;
use totara_core\entity\virtual_meeting_auth;
use totara_core\virtualmeeting\exception\auth_exception;
use totara_core\virtualmeeting\plugin\provider\auth_provider;
use totara_core\virtualmeeting\user_auth;

/**
 * PoC auth provider
 * A username containing 'fail' is permanently banned.
 */
class poc_auth_provider implements auth_provider {
    /** @var string */
    private $pluginname;

    /**
     * Constructor.
     *
     * @param string $name substring of plugin name
     */
    public function __construct(string $name) {
        $this->pluginname = 'poc_' . $name;
    }

    /**
     * @inheritDoc
     */
    public function get_authentication_endpoint(): string {
        global $CFG;
        $url = new moodle_url(
            "/integrations/virtualmeeting/{$this->pluginname}/index.php",
            [
                'redirect_uri' => "{$CFG->wwwroot}/integrations/virtualmeeting/auth_callback.php/{$this->pluginname}",
            ]
        );
        return $url->out(false);
    }

    /**
     * @inheritDoc
     */
    public function get_profile(user $user, bool $update): array {
        if (strpos($user->username, 'fail') !== false) {
            throw new auth_exception('you are failed');
        }
        $auth = user_auth::load($this->pluginname, $user, true);
        $user = core_user::get_user_by_username($auth->get_token());
        if (!$user) {
            throw new auth_exception('invalid user');
        }
        return [
            'name' => $user->username,
            'email' => $user->email,
            'friendly_name' => $user->alternatename ?: fullname($user)
        ];
    }

    /**
     * @inheritDoc
     */
    public function authorise(user $user, string $method, array $headers, string $body, array $query_get, array $query_post): void {
        if (strpos($user->username, 'fail') !== false) {
            throw new auth_exception('you are failed');
        }
        user_auth::create_or_replace($this->pluginname, $user, function (virtual_meeting_auth $entity) use ($query_get) {
            $entity->access_token = $query_get['username'];
            $entity->refresh_token = 'T!O!T!A!R!A!Totara!!';
            $entity->timeexpiry = time() + YEARSECS;
            $entity->save();
        });
    }
}
