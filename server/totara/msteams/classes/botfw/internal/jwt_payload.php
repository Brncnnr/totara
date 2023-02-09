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

namespace totara_msteams\botfw\internal;

use stdClass;

/**
 * A class that represents the payload part of JWT. See RFC 7519 for more information.
 * **Do not reference this class.**
 *
 * @property string  $iss Issuer
 * @property string  $sub Subject
 * @property string  $aud Audience
 * @property integer $exp Expiration Time
 * @property integer $nbf Not Before
 * @property integer $iat Issued At
 * @property string  $jti JWT ID
 * @property string  $appid [AAD] The application ID of the client
 * @property string  $idp [AAD] The identity provider
 * @property string  $tid [AAD] The tenant ID
 * @property string  $ver [AAD] The version of the token
 * @property string  $serviceUrl
 * @property string  $serviceurl
 */
final class jwt_payload extends stdClass {
}
