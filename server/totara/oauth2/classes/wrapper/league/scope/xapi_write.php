<?php
/**
 * This file is part of Totara Core
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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_oauth2
 */
namespace totara_oauth2\wrapper\league\scope;

use League\OAuth2\Server\Entities\ScopeEntityInterface;

/**
 * This class is temporary, to ease the needs of validation of the scope
 * that are passed by third parties using xapi statement.
 */
class xapi_write implements ScopeEntityInterface {
    /**
     * @var string
     */
    public const IDENTIFIER = "xapi:write";

    /**
     * @return string
     */
    public function getIdentifier() {
        return "xapi:write";
    }

    /**
     * @return string
     */
    public function jsonSerialize(): string {
        return $this->getIdentifier();
    }
}