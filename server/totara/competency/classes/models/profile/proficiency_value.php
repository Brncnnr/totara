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
 * @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com
 * @package totara_competency
 */

namespace totara_competency\models\profile;

use coding_exception;
use totara_competency\entity\assignment;
use totara_competency\entity\competency_achievement;
use totara_competency\models\assignment as assignment_model;
use totara_hierarchy\entity\scale;
use totara_hierarchy\entity\scale_value;

/**
 * Class proficiency value model
 *
 * This model represents a relative proficiency value
 *
 * @property int $id
 * @property string $name
 * @property float $percentage
 * @property bool $proficient
 * @property int $scale_id
 */
class proficiency_value {

    /**
     * Optional as we want to support an empty achievement
     *
     * @var assignment|null
     */
    protected $assignment;

    /**
     * Current achievement scale value id
     *
     * @var int
     */
    protected $id;

    /**
     * Current achievement scale value name
     *
     * @var string
     */
    protected $name;

    /**
     * A flag whether the current value is proficient
     *
     * @var bool
     */
    protected $proficient;

    /**
     * Relative percentage for the current proficient value
     * Where 0% is no value (value not achieved)
     * and a 100% is the highest value on the scale
     *
     * @var float
     */
    protected $percentage;

    /**
     * Related scale id for a given proficient value
     *
     * @var int
     */
    protected $scale_id;

    /**
     * Array of attributes publicly available on the model
     *
     * @var array
     */
    protected $public_attributes = [
        'id',
        'name',
        'proficient',
        'percentage',
        'scale_id'
    ];

    /**
     * proficiency_value constructor.
     *
     * @param assignment|null $assignment $assignment
     */
    public function __construct(?assignment $assignment = null) {
        $this->assignment = $assignment;
    }

    /**
     * Create my proficiency value based on a competency assignment for a user and a given timestamp.
     *
     * @param assignment $assignment
     * @param int $user_id the user we want to get the achievement for
     * @param int $achieved_at filter achievement on certain timestamp
     * @return proficiency_value
     */
    public static function value_at_timestamp(assignment $assignment, int $user_id, int $achieved_at): self {
        // If a timestamp was given get the last achievement before that
        $achievement = $assignment->achievements()
            ->where('user_id', $user_id)
            ->order_by('time_created', 'desc')
            ->order_by('id', 'desc')
            ->where('time_created', '<=', $achieved_at)
            ->first();

        return self::get_value_for_achievement($assignment, $achievement);
    }

    /**
     * Create my proficiency value based on a competency assignment
     * Note, the assignment that you supply MUST have current_achievement relation pre-loaded,
     * otherwise it doesn't make sense and will return you a value of the first random user on the assignment
     *
     * @param assignment $assignment
     * @return proficiency_value
     */
    public static function my_value(assignment $assignment): proficiency_value {
        if (!$assignment->relation_loaded('current_achievement')) {
            throw new coding_exception(
                'You must preload "current_achievement" relation with a user filter included, otherwise it does not make sense...'
            );
        }

        return self::get_value_for_achievement($assignment, $assignment->current_achievement);
    }

    /**
     * Build instance from given assignment and achievement
     *
     * @param assignment $assignment
     * @param competency_achievement|null $achievement
     * @return proficiency_value
     */
    private static function get_value_for_achievement(assignment $assignment, ?competency_achievement $achievement): self {
        // We need to check that the achievement actually has a value as it could not have it yet
        if ($achievement && $achievement->value) {
            $value = new static($assignment);
            $value->scale_id = $assignment->competency->scale->id;
            $value->id = $achievement->value->id;
            $value->name = $achievement->value->name;
            // Get the proficient flag from the achievement directly, not from the scale_value, because it can
            // be overridden per assignment.
            $value->proficient = (bool)$achievement->proficient;
            $value->percentage = static::calculate_scale_value_percentage(
                $achievement->value,
                $assignment->competency->scale
            );

            return $value;
        }

        return self::empty_value($assignment);
    }

    /**
     * Returns an instance representing an empty achievement
     *
     * @param assignment|null $assignment
     * @return proficiency_value
     */
    public static function empty_value(?assignment $assignment = null): self {
        $value = new static($assignment);

        if ($assignment) {
            $value->scale_id = $assignment->competency->scale->id;
        } else {
            $value->scale_id = 0;
        }

        $value->id = 0; // It's a pseudo value, with no actual record in the db
        $value->name = get_string('no_value_achieved', 'totara_competency');
        $value->proficient = false; // No value is always not proficient
        $value->percentage = 0; // No value is always 0 percent.

        return $value;
    }

    /**
     * Create a minimum proficient value of the competency scale based on assignment
     *
     * @param assignment $assignment
     * @return proficiency_value
     */
    public static function min_value(assignment $assignment): proficiency_value {
        $value = new static($assignment);

        $assignment_model = assignment_model::load_by_entity($assignment);
        if ($assignment_model->has_default_proficiency_value_override()) {
            $value_entity =  $assignment->min_proficient_value_override;
        } else {
            $value_entity = $assignment->competency->scale->min_proficient_value;
        }

        $value->id = $value_entity->id;
        $value->name = $value_entity->name;
        $value->scale_id = $value_entity->scaleid;
        $value->proficient = true; // This is a min proficient value, so always proficient :)
        $value->percentage = static::calculate_scale_value_percentage(
            $value_entity,
            $assignment->competency->scale
        );

        return $value;
    }

    /**
     * Calculate a percentage of a scale value relative to the scale including no value
     *
     * @param scale_value $value Scale value to calculate relative percentage
     * @param scale $scale Scale to calculate
     * @return float
     */
    protected static function calculate_scale_value_percentage(scale_value $value, scale $scale): float {
        $pos = $scale->values->reduce(function ($pos, $scale_value) use ($value) {
            if ($value->sortorder <= $scale_value->sortorder) {
                $pos += 1;
            }
            return $pos;
        }, 0);

        return round($pos / count($scale->values) * 100);
    }

    /**
     * Return whether publicly available attribute is set on the model
     *
     * @param $name
     * @return bool
     */
    public function __isset($name): bool {
        return in_array($name, $this->public_attributes);
    }

    /**
     * Get publicly available attribute
     *
     * @param $name
     * @return mixed|null
     */
    public function __get($name) {
        // Having ?? automatically triggers isset check and returns only publicly available attributes
        return $this->{$name} ?? null;
    }
}