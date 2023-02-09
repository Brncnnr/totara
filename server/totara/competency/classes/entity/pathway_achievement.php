<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package totara_competency
 */

namespace totara_competency\entity;

use core\entity\user;
use core\orm\entity\entity;
use core\orm\entity\relations\belongs_to;
use totara_competency\entity\pathway as pathway_entity;
use totara_competency\pathway;
use totara_hierarchy\entity\scale_value;

/**
 * Class pathway_achievement
 *
 * @property-read int $id ID
 * @property int $user_id
 * @property int $pathway_id
 * @property int $scale_value_id
 * @property int $date_achieved
 * @property int $last_aggregated
 * @property int $status
 * @property string $related_info
 *
 * @property-read bool $has_scale_value Returns true if there's a scale value attached
 * @property-read pathway_entity $pathway
 * @property-read scale_value $scale_value
 * @property-read user $user
 */
class pathway_achievement extends entity {

    public const TABLE = 'totara_competency_pathway_achievement';

    /** @var int Achievement is current */
    public const STATUS_CURRENT = 0;

    /** @var int Achievement is not current */
    public const STATUS_ARCHIVED = 1;

    /**
     * Get the current achievement for given pathway and user
     *
     * @param pathway $pathway
     * @param int $user_id
     * @return pathway_achievement
     */
    public static function get_current(pathway $pathway, int $user_id): pathway_achievement {
        $achievements = static::repository()
            ->where('pathway_id', $pathway->get_id())
            ->where('user_id', $user_id)
            ->where('status', static::STATUS_CURRENT)
            ->order_by('last_aggregated','desc')
            ->get();

        switch ($achievements->count()) {
            case 0:
                $achievement = new pathway_achievement();
                $achievement->pathway_id = $pathway->get_id();
                $achievement->user_id = $user_id;
                $achievement->scale_value_id = null;
                $achievement->status = static::STATUS_CURRENT;
                $achievement->date_achieved = null;
                // It has not been aggregated yet. If we set this to now, we might miss anything that would have
                // prompted aggregation in the recent past, but maybe cron hasn't run since then.
                $achievement->last_aggregated = null;

                return $achievement;
            case 1:
                return $achievements->first();
            default:
                // There's more than one which is not right. But let's not make the whole system stop working because of this.
                debugging('User has multiple current achievements for pathway with id ' . $pathway->get_id());
                // We ordered by last_aggregated and that would be our best guess at the correct one.
                return $achievements->first();
        }
    }

    /**
     * Returns true if there's a scale value attached
     *
     * @return bool
     */
    public function get_has_scale_value_attribute(): bool {
        return $this->scale_value_id !== null;
    }

    /**
     * Sets the attributes for archiving this element.
     *
     * Calls save() internally.
     *
     * @param int|null $aggregation_time Timestamp
     * @return pathway_achievement
     */
    public function archive(?int $aggregation_time = null): pathway_achievement {
        if (is_null($aggregation_time)) {
            $aggregation_time = time();
        }

        $this->last_aggregated = $aggregation_time;
        $this->status = static::STATUS_ARCHIVED;

        $this->save();

        return $this;
    }

    public function pathway(): belongs_to {
        return $this->belongs_to(pathway_entity::class, 'pathway_id');
    }

    public function scale_value(): belongs_to {
        return $this->belongs_to(scale_value::class, 'scale_value_id');
    }

    public function user(): belongs_to {
        return $this->belongs_to(user::class, 'user_id');
    }
}
