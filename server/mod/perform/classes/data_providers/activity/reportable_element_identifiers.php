<?php
/*
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
 * @author Samantha Jayasinghe <samantha.jayasinghe@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\data_providers\activity;

use core\orm\collection;
use core\orm\query\builder;
use mod_perform\entity\activity\element_identifier as element_identifier_entity;
use mod_perform\entity\activity\element;
use mod_perform\entity\activity\section;
use mod_perform\entity\activity\section_element;
use mod_perform\models\activity\element_identifier as element_identifier_model;

/**
 * Class element_identifier
 * @package mod_perform
 */
class reportable_element_identifiers {

    /**
     * @var collection
     */
    private $items = null;

    /**
     * Returns the element identifier.
     *
     * @return collection|element_identifier_model[] the list of identifiers.
     */
    public function get(): collection {
        if (is_null($this->items)) {
            $this->fetch();
        }
        return $this->items;
    }

    /**
     * Fetches element identifiers from the database and sorts it by id.
     *
     * @return reportable_element_identifiers this object.
     */
    public function fetch(): reportable_element_identifiers {

        $activity_ids = (new reportable_activities())->fetch()->get()->pluck('id');
        $this->items = element_identifier_entity::repository()
            ->as('ei')
            ->select_raw('DISTINCT ei.*')
            ->join([element::TABLE, 'e'], 'e.identifier_id', 'ei.id')
            ->join([section_element::TABLE, 'se'], function (builder $builder) {
                $builder->where(function (builder $builder) {
                    $builder->where('e.parent', null)
                        ->where_field('element_id', 'e.id');
                })->or_where(function (builder $builder) {
                    $builder->where_field('element_id', 'e.parent');
                });
            })
            ->join([section::TABLE, 's'], 's.id', 'se.section_id')
            ->where_in('s.activity_id', $activity_ids)
            ->get()
            ->map_to(element_identifier_model::class);

        return $this;
    }
}