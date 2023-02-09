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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package totara_criteria
 */

namespace totara_criteria\entity;

use core\orm\entity\repository;
use totara_criteria\filter\criterion_competency;

class criterion_repository extends repository {

    /**
       * Define available default filters
       *
       * @return array
       */
    protected function get_default_filters(): array {
        return [
            'competency' => new criterion_competency(),
        ];
    }

    /**
     * Return all criteria that contains one or more of the requested items
     *
     * @param string $item_type
     * @param int|array $item_ids
     * @return $this
     */
    public function from_item_ids(string $item_type, $item_ids): repository {
        if (!$this->has_join(criterion_item::TABLE, 'criterion_item')) {
            $this->join(criterion_item::TABLE, 'id', 'criterion_id');
        }
        $join_alias = $this->get_join(criterion_item::TABLE)->get_table()->get_alias();

        return $this->where("{$join_alias}.item_type", $item_type)
            ->where("{$join_alias}.item_id", $item_ids);
    }
}
