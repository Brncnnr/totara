<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Ning Zhou <ning.zhou@totaralearning.com>
 * @package totara_hierarchy
 */

namespace totara_hierarchy\entity\filters;

use core\orm\entity\filter\filter;
use core\orm\query\builder;
use totara_competency\entity\pathway as pathway_entity;
use totara_competency\pathway;
use totara_hierarchy\entity\competency;

/**
 * Finds all competencies where
 */
class no_achievement_paths extends filter {

    public const TABLE_ALIAS = 'pathway';

    /**
     * @inheritDoc
     */
    public function apply() {
        if ($this->value === 0) {
            return;
        }

        // We want to exclude competencies that have *active* pathways.
        $this->builder->left_join(
            [pathway_entity::TABLE, self::TABLE_ALIAS],
            function (builder $builder) {
                $builder->where_field(competency::TABLE . '.id', self::TABLE_ALIAS . '.competency_id')
                   ->where(self::TABLE_ALIAS . '.status', pathway::PATHWAY_STATUS_ACTIVE);
            }
        )->where(function (builder $builder) {
            $builder->where_null(self::TABLE_ALIAS . '.id');
        });
    }
}