<?php
/*
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
 * @package pathway_manual;
 */

namespace pathway_manual\entity;

use core\orm\entity\entity;

/**
 * Class role
 * Defines a role that is enabled within a manual rating pathway.
 *
 * @property-read int $id ID
 * @property int $path_manual_id
 * @property string $role
 *
 * @package pathway_manual\entity
 */
class role extends entity {

    public const TABLE = 'pathway_manual_role';

}
