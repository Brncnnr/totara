<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @package pathway_perform_rating
 */

namespace pathway_perform_rating\entity;

use core\entity\user;
use core\orm\entity\entity;
use core\orm\entity\relations\belongs_to;
use core\orm\query\builder;
use mod_perform\entity\activity\activity;
use mod_perform\entity\activity\participant_instance;
use mod_perform\entity\activity\subject_instance;
use mod_perform\models\activity\participant_source;
use totara_core\entity\relationship;
use totara_hierarchy\entity\competency;
use totara_hierarchy\entity\scale_value;

/**
 * Stores the rating received in a performance activity
 *
 * @property int $user_id
 * @property int $competency_id
 * @property int|null $scale_value_id
 * @property int|null $activity_id
 * @property int|null $subject_instance_id
 * @property int|null $rater_user_id
 * @property int|null $rater_relationship_id
 * @property int $created_at
 *
 * @property-read user $user
 * @property-read competency $competency
 * @property-read scale_value|null $scale_value
 * @property-read activity|null $activity
 * @property-read subject_instance|null $subject_instance
 * @property-read user|null $rater_user
 * @property-read relationship|null $rater_relationship
 * @property-read participant_instance|null $participant_instance
 */
class perform_rating extends entity {

    public const TABLE = 'pathway_perform_rating';

    public const CREATED_TIMESTAMP = 'created_at';

    /**
     * Returns user who got rated
     *
     * @return belongs_to
     */
    public function user(): belongs_to {
        return $this->belongs_to(user::class, 'user_id');
    }

    /**
     * Competency for this rating
     *
     * @return belongs_to
     */
    public function competency(): belongs_to {
        return $this->belongs_to(competency::class, 'competency_id');
    }

    /**
     * Scale value for this rating
     *
     * @return belongs_to
     */
    public function scale_value(): belongs_to {
        return $this->belongs_to(scale_value::class, 'scale_value_id');
    }

    /**
     * Associated activity for this rating
     *
     * @return belongs_to
     */
    public function activity(): belongs_to {
        return $this->belongs_to(activity::class, 'activity_id');
    }

    /**
     * Associated subject instance for this rating
     *
     * @return belongs_to
     */
    public function subject_instance(): belongs_to {
        return $this->belongs_to(subject_instance::class, 'subject_instance_id');
    }

    /**
     * The rater for this rating
     *
     * @return belongs_to
     */
    public function rater_user(): belongs_to {
        return $this->belongs_to(user::class, 'rater_user_id');
    }

    /**
     * The rater relationship for this rating
     *
     * @return belongs_to
     */
    public function rater_relationship(): belongs_to {
        return $this->belongs_to(relationship::class, 'rater_relationship_id');
    }

    /**
     * Participant instance for this rating
     *
     * @return belongs_to
     */
    public function participant_instance(): belongs_to {
        $table = '"' . participant_instance::TABLE . '"';
        return $this
            ->belongs_to(participant_instance::class, 'subject_instance_id', 'subject_instance_id')
            ->join([perform_rating::TABLE, 'pr'], function (builder $builder) use ($table) {
                $builder->where_field('rater_relationship_id', "{$table}.core_relationship_id")
                    ->where_field('rater_user_id', "{$table}.participant_id")
                    ->where("{$table}.participant_source", participant_source::INTERNAL)
                    ->where_not_null('subject_instance_id')
                    ->where_not_null('rater_user_id')
                    ->where_not_null('rater_relationship_id');
            });
    }

}
