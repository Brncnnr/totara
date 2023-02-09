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
 * @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com>
 * @package core_orm
 */

namespace core\orm\entity\relations;

use coding_exception;
use core\orm\entity\entity;

/**
 * Class has_one defines one to one relation between entities
 */
class has_one extends one_to_one {

    protected $can_save = true;

    /**
     *  Save has one relation...
     *
     * @param entity|entity[] $children
     * @return one_to_one
     */
    public function save($children) {
        // Let's enforce it to be one, if it has one...
        if ((is_array($children) && count($children) > 1) || $this->exists()) {
            throw new coding_exception('Can not save more than one child for a has_one relation...');
        }

        return parent::save($children);
    }

}
