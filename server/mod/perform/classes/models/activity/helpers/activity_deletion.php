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
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\models\activity\helpers;

use coding_exception;
use core\entity\adhoc_task;
use core\orm\query\builder;
use mod_perform\entity\activity\activity as activity_entity;
use mod_perform\entity\activity\element as element_entity;
use mod_perform\entity\activity\manual_relationship_selection;
use mod_perform\entity\activity\section_element_reference;
use mod_perform\entity\activity\section_relationship as section_relationship_entity;
use mod_perform\entity\activity\track_user_assignment;
use mod_perform\event\activity_deleted;
use mod_perform\hook\pre_activity_deleted;
use mod_perform\models\activity\activity;
use mod_perform\task\close_activity_subject_instances_task;
use totara_notification\external_helper;

/**
 * Class activity_deletion
 * Responsible for handling the deletion of a perform activity and it's child records.
 *
 * This class is not responsible for deleting the associated perform container and contexts.
 *
 * @see \container_perform\perform::delete()
 */
class activity_deletion {

    /**
     * @var activity
     */
    protected $activity;

    public function __construct(activity $activity) {
        $this->activity = $activity;
    }

    /**
     * Delete the activity and the associated child models.
     * For redisplay questions, it will also delete elements that are used by other perform activities. Otherwise,
     * activities with circular references could never be deleted.
     *
     * An activity_deleted event will be triggered on successful deletions.
     *
     * @see activity_deleted
     * @param bool $force
     * @return activity_deletion
     */
    public function delete(bool $force = false): self {
        if (!$force) {
            // check if perform activity can be deleted
            $hook = new pre_activity_deleted($this->activity->id);
            $hook->execute();

            if ($reason = $hook->get_first_reason()) {
                throw new coding_exception($reason->get_description());
            }
        }

        builder::get_db()->transaction(function () {
            $delete_event = activity_deleted::create_from_activity($this->activity);

            $this->delete_redisplay_section_element_references();
            $this->delete_section_relationships(); // Must be deleted first due to foreign key constraints.
            $this->delete_user_assignments(); // Must be deleted first due to foreign key constraints.
            $this->delete_manual_relationship_selections();

            // Delete any elements that are directly owned by this activity (through shared context).
            $this->delete_own_elements();

            // Delete totara centralised notifications.
            $context = $this->activity->get_context();
            external_helper::remove_notification_preferences(
                $context->id
            );

            // Cascading delete will also delete rows fom the following tables:
            // - perform
            // - perform_track
            // - perform_subject_instance
            // - perform_section
            // - perform_section_element
            activity_entity::repository()->where('id', $this->activity->get_id())->delete();

            // Delete pending close_activity_subject_instances_task adhoc task
            $class = close_activity_subject_instances_task::class;
            if (strpos($class, '\\') !== 0) {
                $class = '\\' . $class;
            }
            adhoc_task::repository()
                ->where('component', 'mod_perform')
                ->where('classname', $class)
                ->where("customdata", json_encode(["activity_id" => $this->activity->id]))
                ->delete();

            // Delete any and every file uploaded to this activity.
            get_file_storage()->delete_area_files($this->activity->get_context()->id);

            $delete_event->trigger();
        });

        return $this;
    }

    /**
     * Delete a list of user assignments based on track ids.
     */
    protected function delete_user_assignments(): void {
        builder::get_db()->delete_records_select(
            track_user_assignment::TABLE,
            "track_id IN (
               SELECT id FROM {perform_track} WHERE activity_id = :activity_id
            )",
            ['activity_id' => $this->activity->id]
        );
    }

    /**
     * Delete elements that share the same context as this element.
     */
    protected function delete_own_elements(): void {
        builder::create()
            ->from(element_entity::TABLE, 'element')
            ->where('context_id', $this->activity->get_context()->id)
            ->delete();
    }

    /**
     * Delete a list of section relationship records.
     */
    protected function delete_section_relationships(): void {
        builder::get_db()->delete_records_select(
            section_relationship_entity::TABLE,
            "section_id IN (
                SELECT id FROM {perform_section} WHERE activity_id = :activity_id
            )",
            ['activity_id' => $this->activity->id]
        );
    }

    /**
     * Delete section element references of redisplay questions that point to any of this activity's elements.
     */
    protected function delete_redisplay_section_element_references(): void {
        builder::get_db()->delete_records_select(
            section_element_reference::TABLE,
            "source_section_element_id IN (
                SELECT se.id FROM {perform_section_element} se
                JOIN {perform_section} s ON s.id = se.section_id
                WHERE s.activity_id = :activity_id
            ) AND referencing_element_id IN (
                SELECT e.id FROM {perform_element} e
                WHERE e.plugin_name = 'redisplay'
            )",
            ['activity_id' => $this->activity->id]
        );
    }

    /**
     * Delete the manual relationship selection records associated with the activity.
     */
    protected function delete_manual_relationship_selections(): void {
        manual_relationship_selection::repository()
            ->where('activity_id', $this->activity->id)
            ->delete();
    }

}
