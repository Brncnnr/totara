<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package totara_evidence
 */

namespace totara_evidence\entity;

use core\orm\entity\entity;

/**
 * Evidence type field entity
 *
 * @property-read int $id ID
 * @property int $typeid Evidence type ID
 * @property string $fullname Full name
 * @property string $shortname Short name
 * @property string $datatype Data type
 * @property string $description Description
 * @property int $sortorder Sort order
 * @property bool $hidden Is hidden?
 * @property bool $locked Is locked?
 * @property bool $required Is required?
 * @property bool $forceunique Must be unique?
 * @property string $defaultdata Default field data
 * @property string $param1 Param data 1
 * @property string $param2 Param data 2
 * @property string $param3 Param data 3
 * @property string $param4 Param data 4
 * @property string $param5 Param data 5
 * @property-read evidence_type $type Evidence type
 *
 * @package totara_evidence\entity
 */
class evidence_type_field extends entity {

    /**
     * @var string
     */
    public const TABLE = 'totara_evidence_type_info_field';

    /**
     * Get the type that this field belongs to
     *
     * @return evidence_type
     */
    public function get_type_attribute(): evidence_type {
        return new evidence_type($this->typeid);
    }

}
