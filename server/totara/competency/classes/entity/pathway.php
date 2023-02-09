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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_competency
 */

namespace totara_competency\entity;

use core\orm\entity\entity;
use core\orm\entity\relations\belongs_to;
use totara_hierarchy\entity\competency;

/**
 * Pathway per competency
 *
 * @property int $competency_id
 * @property int $sortorder
 * @property string $path_type
 * @property int $path_instance_id
 * @property int $status
 * @property int $pathway_modified
 * @property int $valid
 *
 * @property-read competency $competency
 */
class pathway extends entity {

    public const UPDATED_TIMESTAMP = 'pathway_modified';
    public const SET_UPDATED_WHEN_CREATED = true;

    public const TYPE_MANUAL = 'manual';
    public const TYPE_CRITERIA = 'criteria_group';
    public const TYPE_LEARNING_PLAN = 'learning_plan';
    public const TYPE_PERFORM_RATING = 'perform_rating';

    public const TABLE = 'totara_competency_pathway';

    /**
     * Each pathway has a competency associated wit it!
     *
     * @return belongs_to
     */
    public function competency(): belongs_to {
        return $this->belongs_to(competency::class, 'competency_id');
    }

    public static function get_type_name($type): string {
        switch ($type) {
            case self::TYPE_MANUAL:
                return get_string('pluginname', 'pathway_manual');
            case self::TYPE_CRITERIA:
                return get_string('single_value_paths', 'totara_competency');
            case self::TYPE_LEARNING_PLAN:
                return get_string('pluginname', 'pathway_learning_plan');
            case self::TYPE_PERFORM_RATING:
                return get_string('pluginname', 'pathway_perform_rating');
            default:
                return '';
        }
    }
}
