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

namespace totara_criteria\filter;


use core\orm\entity\filter\filter;
use core\orm\query\table;
use totara_criteria\criterion;
use totara_criteria\entity\criteria_metadata as metadata_entity;

class criterion_competency extends filter {

    public function apply() {
        if (!empty($this->value)) {
            if (!$metadata_join = $this->builder->get_join(metadata_entity::TABLE)) {
                $this->builder->join((new table(metadata_entity::TABLE))->as('metadata'), 'id', '=', 'criterion_id');
            }

            $this->builder
                ->where('metadata.metakey', criterion::METADATA_COMPETENCY_KEY)
                ->where('metadata.metavalue', $this->value);
        }
    }

}
