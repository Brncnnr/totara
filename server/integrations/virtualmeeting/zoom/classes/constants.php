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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package virtualmeeting_zoom
 */

namespace virtualmeeting_zoom;

/**
 * Endpoint URIs, scopes, etc.
 *
 * @todo should be configurable in the admin page
 */
final class constants {
    const OAUTH2_AUTH_ENDPOINT = 'https://zoom.us/oauth/authorize';
    const OAUTH2_TOKEN_ENDPOINT = 'https://zoom.us/oauth/token';
    const USERINFO_API_ENDPOINT = 'https://api.zoom.us/v2/users/me';
    const CREATE_MEETING_API_ENDPOINT = 'https://api.zoom.us/v2/users/me/meetings';
    const MEETING_API_ENDPOINT = 'https://api.zoom.us/v2/meetings';
    const MEETING_API_TYPE = 2;
    const MEETING_DATETIME_FORMAT = 'Y-m-d\TH:i:s\Z';
    const HOST_URL_MAX_AGE = MINSECS * 45;
}
