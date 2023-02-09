<?php
/**
 * This file is part of Totara Learn
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package core
 */

namespace core\entity;

use core\orm\entity\entity;
use core\orm\entity\relations\belongs_to;

/**
 * Context entity
 *
 * @property-read int $id ID
 * @property int $contextlevel
 * @property int $instanceid
 * @property string $path
 * @property int $depth
 * @property int $parentid
 * @property int $tenantid
 *
 * @property-read context $parent
 * @property-read tenant $tenant
 *
 * @package core\entity
 */
class context extends entity {

    public const TABLE = 'context';

    /**
     * @return belongs_to
     */
    public function parent(): belongs_to {
        return $this->belongs_to(self::class, 'parentid');
    }

    /**
     * @return belongs_to
     */
    public function tenant(): belongs_to {
        return $this->belongs_to(tenant::class, 'tenantid');
    }

}
