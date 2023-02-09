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
    'mod_perform\entities\activity\filters\subject_instance_id' => 'mod_perform\entity\activity\filters\subject_instance_id',
    'mod_perform\entities\activity\filters\subject_instances_about' => 'mod_perform\entity\activity\filters\subject_instances_about',
    'mod_perform\entities\activity\activity' => 'mod_perform\entity\activity\activity',
    'mod_perform\entities\activity\activity_repository' => 'mod_perform\entity\activity\activity_repository',
    'mod_perform\entities\activity\activity_setting' => 'mod_perform\entity\activity\activity_setting',
    'mod_perform\entities\activity\activity_setting_repository' => 'mod_perform\entity\activity\activity_setting_repository',
    'mod_perform\entities\activity\activity_type' => 'mod_perform\entity\activity\activity_type',
    'mod_perform\entities\activity\activity_type_repository' => 'mod_perform\entity\activity\activity_type_repository',
    'mod_perform\entities\activity\element' => 'mod_perform\entity\activity\element',
    'mod_perform\entities\activity\element_identifier' => 'mod_perform\entity\activity\element_identifier',
    'mod_perform\entities\activity\element_identifier_repository' => 'mod_perform\entity\activity\element_identifier_repository',
    'mod_perform\entities\activity\element_repository' => 'mod_perform\entity\activity\element_repository',
    'mod_perform\entities\activity\element_response' => 'mod_perform\entity\activity\element_response',
    'mod_perform\entities\activity\element_response_repository' => 'mod_perform\entity\activity\element_response_repository',
    'mod_perform\entities\activity\external_participant' => 'mod_perform\entity\activity\external_participant',
    'mod_perform\entities\activity\external_participant_repository' => 'mod_perform\entity\activity\external_participant_repository',
    'mod_perform\entities\activity\manual_relationship_selection' => 'mod_perform\entity\activity\manual_relationship_selection',
    'mod_perform\entities\activity\manual_relationship_selection_progress' => 'mod_perform\entity\activity\manual_relationship_selection_progress',
    'mod_perform\entities\activity\manual_relationship_selection_progress_repository' => 'mod_perform\entity\activity\manual_relationship_selection_progress_repository',
    'mod_perform\entities\activity\manual_relationship_selection_repository' => 'mod_perform\entity\activity\manual_relationship_selection_repository',
    'mod_perform\entities\activity\manual_relationship_selector' => 'mod_perform\entity\activity\manual_relationship_selector',
    'mod_perform\entities\activity\manual_relationship_selector_repository' => 'mod_perform\entity\activity\manual_relationship_selector_repository',
    'mod_perform\entities\activity\notification' => 'mod_perform\entity\activity\notification',
    'mod_perform\entities\activity\notification_recipient' => 'mod_perform\entity\activity\notification_recipient',
    'mod_perform\entities\activity\participant_instance' => 'mod_perform\entity\activity\participant_instance',
    'mod_perform\entities\activity\participant_instance_repository' => 'mod_perform\entity\activity\participant_instance_repository',
    'mod_perform\entities\activity\participant_section' => 'mod_perform\entity\activity\participant_section',
    'mod_perform\entities\activity\participant_section_repository' => 'mod_perform\entity\activity\participant_section_repository',
    'mod_perform\entities\activity\section' => 'mod_perform\entity\activity\section',
    'mod_perform\entities\activity\section_element' => 'mod_perform\entity\activity\section_element',
    'mod_perform\entities\activity\section_element_repository' => 'mod_perform\entity\activity\section_element_repository',
    'mod_perform\entities\activity\section_relationship' => 'mod_perform\entity\activity\section_relationship',
    'mod_perform\entities\activity\section_repository' => 'mod_perform\entity\activity\section_repository',
    'mod_perform\entities\activity\subject_instance' => 'mod_perform\entity\activity\subject_instance',
    'mod_perform\entities\activity\subject_instance_manual_participant' => 'mod_perform\entity\activity\subject_instance_manual_participant',
    'mod_perform\entities\activity\subject_instance_manual_participant_repository' => 'mod_perform\entity\activity\subject_instance_manual_participant_repository',
    'mod_perform\entities\activity\subject_instance_repository' => 'mod_perform\entity\activity\subject_instance_repository',
    'mod_perform\entities\activity\subject_static_instance' => 'mod_perform\entity\activity\subject_static_instance',
    'mod_perform\entities\activity\subject_static_instance_repository' => 'mod_perform\entity\activity\subject_static_instance_repository',
    'mod_perform\entities\activity\temp_track_user_assignment_queue' => 'mod_perform\entity\activity\temp_track_user_assignment_queue',
    'mod_perform\entities\activity\track' => 'mod_perform\entity\activity\track',
    'mod_perform\entities\activity\track_assignment' => 'mod_perform\entity\activity\track_assignment',
    'mod_perform\entities\activity\track_assignment_repository' => 'mod_perform\entity\activity\track_assignment_repository',
    'mod_perform\entities\activity\track_repository' => 'mod_perform\entity\activity\track_repository',
    'mod_perform\entities\activity\track_user_assignment' => 'mod_perform\entity\activity\track_user_assignment',
    'mod_perform\entities\activity\track_user_assignment_repository' => 'mod_perform\entity\activity\track_user_assignment_repository',
    'mod_perform\entities\activity\track_user_assignment_via' => 'mod_perform\entity\activity\track_user_assignment_via',
);

