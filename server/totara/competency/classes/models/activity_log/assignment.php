<?php
/**
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
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @package totara_competency
 */

namespace totara_competency\models\activity_log;

use coding_exception;
use core\orm\entity\entity as core_entity;
use stdClass;
use totara_competency\entity\assignment as assignment_entity;
use totara_competency\entity\competency_assignment_user_log;
use totara_competency\models\activity_log;
use totara_competency\user_groups;

class assignment extends activity_log {

    /**
     * Load an instance of this model using data from the entity passed in.
     *
     * @param core_entity $entity
     * @return activity_log
     */
    public static function load_by_entity(core_entity $entity): activity_log {
        if (!($entity instanceof competency_assignment_user_log)) {
            throw new coding_exception('Invalid entity', 'Entity must be instance of competency_assignment_user_log');
        }

        return (new assignment())->set_entity($entity);
    }

    /**
     * Timestamp of the date corresponding to this data.
     *
     * @return int
     */
    public function get_date(): int {
        return $this->get_entity()->created_at;
    }

    /**
     * @return string
     */
    public function get_assignment_action(): string {
        return $this->get_entity()->action_name;
    }

    /**
     * Gets the human-readable description for an assignment log type instance.
     *
     * @return string
     */
    public function get_description(): string {
        $assignment_model = $this->get_assignment();
        $assignment_entity = $assignment_model->get_entity();
        switch ($this->get_entity()->action) {
            case competency_assignment_user_log::ACTION_ASSIGNED:
                switch ($assignment_entity->type) {
                    case assignment_entity::TYPE_SYSTEM:
                        return get_string('activity_log_assigned_continuous', 'totara_competency');
                    case assignment_entity::TYPE_LEGACY:
                        return get_string('assignment_type_legacy', 'totara_competency');
                    case assignment_entity::TYPE_SELF:
                        return get_string('activity_log_assigned_self', 'totara_competency');
                    case assignment_entity::TYPE_ADMIN:
                        // Use the models user group loading functionality
                        $user_group = $assignment_model->get_user_group();
                        $name = $user_group->get_name();
                        $a = new stdClass();

                        switch ($user_group->get_type()) {
                            case user_groups::USER:
                                $a->assigner_name = fullname((object)$assignment_entity->assigner->to_array());
                                return get_string('activity_log_assigned_admin', 'totara_competency', $a);
                            case user_groups::COHORT:
                                $a->audience_name = $name;
                                return get_string('activity_log_assigned_audience', 'totara_competency', $a);
                            case user_groups::POSITION:
                                $a->position_name = $name;
                                return get_string('activity_log_assigned_position', 'totara_competency', $a);
                            case user_groups::ORGANISATION:
                                $a->organisation_name = $name;
                                return get_string('activity_log_assigned_organisation', 'totara_competency', $a);
                            default:
                                throw new coding_exception(
                                    'Invalid type',
                                    'Assignment group type not found: '. $assignment_entity->user_group_type
                                );
                        }
                        break;
                    case assignment_entity::TYPE_OTHER:
                        $a = new stdClass();
                        $a->assigner_name = $assignment_model->get_reason_assigned();
                        return get_string('activity_log_assigned_other', 'totara_competency', $a);
                    default:
                        throw new coding_exception(
                            'Invalid type',
                            'Invalid assignment type: ' . $this->get_assignment()->get_type_name()
                        );
                }
                break;
            case competency_assignment_user_log::ACTION_UNASSIGNED_USER_GROUP:
            case competency_assignment_user_log::ACTION_UNASSIGNED_ARCHIVED:
                switch ($assignment_entity->type) {
                    case assignment_entity::TYPE_SYSTEM:
                        return get_string('activity_log_unassigned_continuous', 'totara_competency');
                    case assignment_entity::TYPE_SELF:
                        return get_string('activity_log_unassigned_self', 'totara_competency');
                    case assignment_entity::TYPE_ADMIN:
                        // Use the models user group loading functionality
                        $user_group = $assignment_model->get_user_group();
                        $name = $user_group->get_name();
                        $a = new stdClass();

                        switch ($user_group->get_type()) {
                            case user_groups::USER:
                                $a->assigner_name = fullname((object)$assignment_entity->assigner->to_array());
                                return get_string('activity_log_unassigned_admin', 'totara_competency', $a);
                            case user_groups::COHORT:
                                $a->audience_name = $name;
                                return get_string('activity_log_unassigned_audience', 'totara_competency', $a);
                            case user_groups::POSITION:
                                $a->position_name = $name;
                                return get_string('activity_log_unassigned_position', 'totara_competency', $a);
                            case user_groups::ORGANISATION:
                                $a->organisation_name = $name;
                                return get_string('activity_log_unassigned_organisation', 'totara_competency', $a);
                            default:
                                throw new coding_exception(
                                    'Invalid type',
                                    'Assignment group type not found: '. $assignment_entity->user_group_type
                                );
                        }
                        break;
                    case assignment_entity::TYPE_OTHER:
                        $a = new stdClass();
                        $a->assigner_name = $assignment_model->get_reason_assigned();
                        return get_string('activity_log_unassigned_other', 'totara_competency', $a);
                    default:
                        throw new coding_exception(
                            'Invalid type',
                            'Invalid assignment type: ' . $this->get_assignment()->type
                        );
                }
                break;
            case competency_assignment_user_log::ACTION_TRACKING_START:
                return get_string('activity_log_tracking_started', 'totara_competency');
            case competency_assignment_user_log::ACTION_TRACKING_END:
                return get_string('activity_log_tracking_stopped', 'totara_competency');
            default:
                throw new coding_exception('Invalid action', 'Invalid action provided: ' . $this->get_entity()->action);
        }
    }
}