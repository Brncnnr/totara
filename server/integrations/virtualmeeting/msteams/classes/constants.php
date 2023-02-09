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
 * @package virtualmeeting_msteams
 */

namespace virtualmeeting_msteams;

/**
 * Endpoint URIs, scopes, etc.
 *
 * @todo should be configurable in the admin page
 */
final class constants {
    const OAUTH2_AUTH_ENDPOINT = 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize';
    const OAUTH2_TOKEN_ENDPOINT = 'https://login.microsoftonline.com/common/oauth2/v2.0/token';
    const USERINFO_API_ENDPOINT = 'https://graph.microsoft.com/v1.0/me';
    const MEETING_API_ENDPOINT = 'https://graph.microsoft.com/v1.0/me/onlineMeetings';
    const SCOPE_MEETING = 'openid offline_access profile User.Read OnlineMeetings.ReadWrite';
}
