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
 * A class that represents JWK. See RFC 7515, RFC 7517 and RFC 7518 for more information.
 * https://docs.microsoft.com/en-us/azure/bot-service/rest-api/bot-framework-rest-connector-authentication
 * **Do not reference this class.**
 *
 * @property string   $kty
 * @property string   $use
 * @property string   $kid
 * @property string   $x5t
 * @property string   $n modulus of the RSA key
 * @property string   $e public exponent of the RSA key
 * @property string   $d private exponent of the RSA key
 * @property string[] $x5c
 * @property string[] $endorsements
 */
final class jwk extends stdClass {
}
