<?php
/**
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
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 */

defined('MOODLE_INTERNAL') || die();

// Like other files in the db directory this file uses an array.
// The old class name is the key, the new class name is the value.
// The array must be called $renamedclasses.
$renamedclasses = array(
    'totara_competency\entity\assignment_availability' => 'totara_hierarchy\entity\assignment_availability',
    'totara_competency\entity\competency_framework_repository' => 'totara_hierarchy\entity\competency_framework_repository',
    'totara_competency\entity\competency_framework' => 'totara_hierarchy\entity\competency_framework',
    'totara_competency\entity\competency_repository' => 'totara_hierarchy\entity\competency_repository',
    'totara_competency\entity\competency_scale_assignment' => 'totara_hierarchy\entity\scale_assignment',
    'totara_competency\entity\competency_type' => 'totara_hierarchy\entity\competency_type',
    'totara_competency\entity\competency' => 'totara_hierarchy\entity\competency',
    'totara_competency\entity\scale_assignment' => 'totara_hierarchy\entity\scale_assignment',
    'totara_competency\entity\scale_value' => 'totara_hierarchy\entity\scale_value',
    'totara_competency\entity\scale' => 'totara_hierarchy\entity\scale',
    'totara_competency\entity\course' => 'core\entity\course',
    'totara_competency\entity\course_categories' => 'core\entity\course_categories',
    'totara_competency\entity\course_repository' => 'core\entity\course_repository',
    'totara_competency\entities\filters\competency_assignment_status' => 'totara_competency\entity\filters\competency_assignment_status',
    'totara_competency\entities\filters\competency_assignment_type' => 'totara_competency\entity\filters\competency_assignment_type',
    'totara_competency\entities\filters\competency_user_assignment_status' => 'totara_competency\entity\filters\competency_user_assignment_status',
    'totara_competency\entities\filters\competency_user_assignment_type' => 'totara_competency\entity\filters\competency_user_assignment_type',
    'totara_competency\entities\filters\path' => 'totara_competency\entity\filters\path',
    'totara_competency\entities\helpers\hierarchy_crumbtrail_helper' => 'totara_competency\entity\helpers\hierarchy_crumbtrail_helper',
    'totara_competency\entities\achievement_via' => 'totara_competency\entity\achievement_via',
    'totara_competency\entities\assignment' => 'totara_competency\entity\assignment',
    'totara_competency\entities\assignment_availability' => 'totara_hierarchy\entity\assignment_availability',
    'totara_competency\entities\assignment_repository' => 'totara_competency\entity\assignment_repository',
    'totara_competency\entities\competency' => 'totara_hierarchy\entity\competency',
    'totara_competency\entities\competency_achievement' => 'totara_competency\entity\competency_achievement',
    'totara_competency\entities\competency_achievement_repository' => 'totara_competency\entity\competency_achievement_repository',
    'totara_competency\entities\competency_assignment_user' => 'totara_competency\entity\competency_assignment_user',
    'totara_competency\entities\competency_assignment_user_log' => 'totara_competency\entity\competency_assignment_user_log',
    'totara_competency\entities\competency_assignment_user_repository' => 'totara_competency\entity\competency_assignment_user_repository',
    'totara_competency\entities\competency_framework' => 'totara_hierarchy\entity\competency_framework',
    'totara_competency\entities\competency_framework_repository' => 'totara_hierarchy\entity\competency_framework_repository',
    'totara_competency\entities\competency_repository' => 'totara_hierarchy\entity\competency_repository',
    'totara_competency\entities\competency_scale_assignment' => 'totara_hierarchy\entity\scale_assignment',
    'totara_competency\entities\competency_type' => 'totara_hierarchy\entity\competency_type',
    'totara_competency\entities\configuration_change' => 'totara_competency\entity\configuration_change',
    'totara_competency\entities\configuration_history' => 'totara_competency\entity\configuration_history',
    'totara_competency\entities\course' => 'core\entity\course',
    'totara_competency\entities\course_categories' => 'core\entity\course_categories',
    'totara_competency\entities\course_repository' => 'core\entity\course_repository',
    'totara_competency\entities\pathway' => 'totara_competency\entity\pathway',
    'totara_competency\entities\pathway_achievement' => 'totara_competency\entity\pathway_achievement',
    'totara_competency\entities\scale' => 'totara_hierarchy\entity\scale',
    'totara_competency\entities\scale_aggregation' => 'totara_competency\entity\scale_aggregation',
    'totara_competency\entities\scale_assignment' => 'totara_hierarchy\entity\scale_assignment',
    'totara_competency\entities\scale_value' => 'totara_hierarchy\entity\scale_valuescale',
    'pathway_criteria_group\entities\criteria_group' => 'pathway_criteria_group\entity\criteria_group',
    'pathway_criteria_group\entities\criteria_group_criterion' => 'pathway_criteria_group\entity\criteria_group_criterion',
    'pathway_learning_plan\entities\plan_competency_value' => 'pathway_learning_plan\entity\plan_competency_value',
    'pathway_manual\entities\pathway_manual' => 'pathway_manual\entity\pathway_manual',
    'pathway_manual\entities\rating' => 'pathway_manual\entity\rating',
    'pathway_manual\entities\role' => 'pathway_manual\entity\role',

);