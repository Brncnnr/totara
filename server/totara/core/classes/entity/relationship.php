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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package totara_core
 */

namespace totara_core\entity;

use core\orm\collection;
use core\orm\entity\entity;
use core\orm\entity\relations\has_many;

/**
 * Relationship entity.
 *
 * @property-read int $id
 * @property string $idnumber
 * @property int $sort_order
 * @property int $type
 * @property string $component
 * @property int $created_at
 *
 * @property-read relationship_resolver[]|collection $resolvers
 *
 * @package totara_core\entity
 */
class relationship extends entity {

    public const TABLE = 'totara_core_relationship';

    public const CREATED_TIMESTAMP = 'created_at';

    /**
     * type for core relationship, also called calculated relationship this is being use in core, ex: subject, manager, appraiser
     */
    public const TYPE_STANDARD = 0;

    /**
     * type for manual relationship, this is being used in perform module, ex: peer, customer, mentor, reviewer
     */
    public const TYPE_MANUAL = 1;

    /**
     * The resolvers that are part of this relationship.
     *
     * @return has_many
     */
    public function resolvers(): has_many {
        return $this->has_many(relationship_resolver::class, 'relationship_id');
    }

}
