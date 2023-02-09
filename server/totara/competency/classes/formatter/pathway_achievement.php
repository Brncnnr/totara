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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com
 * @package totara_competency
 */

namespace totara_competency\formatter;

use core\orm\formatter\entity_formatter;
use totara_competency\entity\pathway_achievement as pathway_achievement_entity;
use core\webapi\formatter\field\date_field_formatter;

/**
 * @property pathway_achievement_entity $object
 */
class pathway_achievement extends entity_formatter {

    protected function get_map(): array {
        return [
            'id' => null,
            'pathway' => null,
            'user' => null,
            'scale_value' => null,
            'date_achieved' => date_field_formatter::class,
            'last_aggregated' => date_field_formatter::class,
            'status' => function ($value) {
                switch ((int) $value) {
                    case pathway_achievement_entity::STATUS_ARCHIVED:
                        return 'ARCHIVED';
                    case pathway_achievement_entity::STATUS_CURRENT:
                        return 'CURRENT';
                    default:
                        throw new \coding_exception('Unknow pathway achievement status \''.$value.'\'');
                }
            },
            'related_info' => null,
            'has_scale_value' => null,
        ];
    }

}