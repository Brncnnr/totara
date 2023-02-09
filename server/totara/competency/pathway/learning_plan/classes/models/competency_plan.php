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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package pathway_learning_plan
 */

namespace pathway_learning_plan\models;

use core\orm\entity\entity;
use development_plan;
use dp_competency_component;
use pathway_learning_plan\entity\plan_competency_value;
use totara_competency\entity\assignment;
use totara_hierarchy\entity\scale_value;

defined('MOODLE_INTERNAL') || die();

/**
 * For obtaining the latest value made for a competency across a user's learning plans.
 *
 * @package pathway_learning_plan\models
 */
class competency_plan {

    /**
     * @var int[] Plans (IDs) that the competency is linked to.
     */
    protected $plans;

    /**
     * @var plan_competency_value|null
     */
    protected $value;

    public function __construct(int $competency_id, int $user_id) {
        global $CFG;
        require_once($CFG->dirroot . '/totara/plan/component.class.php');
        require_once($CFG->dirroot . '/totara/plan/components/competency/competency.class.php');

        $this->plans = dp_competency_component::get_plans_containing_item($competency_id, $user_id);

        $this->value = plan_competency_value::repository()
            ->where('user_id', $user_id)
            ->where('competency_id', $competency_id)
            ->order_by('date_assigned', 'desc')
            ->first();
    }

    /**
     * Get the competency from the competency assignment.
     *
     * @param int $assignment_id
     * @param int $user_id
     * @return competency_plan
     */
    public static function for_assignment(int $assignment_id, int $user_id): self {
        return new static((new assignment($assignment_id))->competency_id, $user_id);
    }

    /**
     * Get the user's learning plans that include this competency.
     *
     * @return development_plan[]
     */
    public function get_plans(): array {
        global $CFG;
        require_once($CFG->dirroot . '/totara/plan/development_plan.class.php');

        return array_map(function (int $plan_id) {
            return new development_plan($plan_id);
        }, $this->plans);
    }

    /**
     * Get the most recent scale value made for this competency in a learning plan.
     *
     * @return scale_value|entity|null
     */
    public function get_scale_value(): ?scale_value {
        if ($this->value) {
            return $this->value->scale_value;
        }
        return null;
    }

    /**
     * Get the date of the most recent scale rating made for this competency in a learning plan.
     *
     * @return int|null
     */
    public function get_date_assigned(): ?int {
        if ($this->value) {
            return $this->value->date_assigned;
        }
        return null;
    }

}
