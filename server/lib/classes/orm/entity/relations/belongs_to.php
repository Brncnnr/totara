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

use core\orm\entity\entity;

/**
 * Class belongs_to defines one to one relation between entities
 * It is an inverse of has_one relationship
 */
class belongs_to extends one_to_one {

    /**
     * Associate a related entity
     *
     * @param entity $entity
     * @return $this
     */
    public function associate(entity $entity) {
        if (!$entity->exists() || is_null($entity->get_attribute($this->get_foreign_key()))) {
            throw new \coding_exception('Entity to associate must exist and its key must not be null!');
        }

        $this->entity->set_attribute($this->key, $entity->get_attribute($this->get_foreign_key()));

        return $this;
    }

    /**
     * Dissociate a related entity
     *
     * @return $this
     */
    public function disassociate() {
        $this->entity->set_attribute($this->key, null);

        return $this;
    }

}
