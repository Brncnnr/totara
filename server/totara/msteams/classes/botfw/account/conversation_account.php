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

namespace totara_msteams\botfw\account;

use totara_msteams\botfw\persist_object;

/**
 * @property-read string $id
 * @property-read string $name
 * @property-read string $role
 * @property-read string $aadObjectId
 * @property-read string $tenantId
 * @property-read string $conversationType
 * @property-read boolean $isGroup
 */
class conversation_account extends persist_object {
    /**
     * @var array
     */
    private static $mappers = [
        'id' => 'string',
        'name' => 'string',
        'role' => 'string',
        'aadObjectId' => 'string',
        'tenantId' => 'string',
        'conversationType' => 'string',
        'isGroup' => 'boolean',
    ];

    /**
     * @inheritDoc
     */
    protected static function get_mapper(string $name): string {
        return self::$mappers[$name] ?? 'object';
    }
}
