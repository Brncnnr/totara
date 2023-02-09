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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\model\workflow\helper;

use core\orm\collection;
use container_approval\approval as workflow_container;
use mod_approval\model\assignment\assignment;
use mod_approval\model\workflow\workflow;
use mod_approval\model\workflow\workflow_stage;
use totara_core\extended_context;
use totara_notification\loader\delivery_channel_loader;
use totara_notification\model\notifiable_event_preference;
use totara_notification\loader\notification_preference_loader;
use totara_notification\builder\notification_preference_builder;
use totara_notification\entity\notifiable_event_preference as notifiable_event_preference_entity;

/**
 * Class cloner
 */
class cloner {

    /**
     * Clone workflow
     *
     * @param workflow $workflow
     * @param string $name
     * @param int $default_assigment_type
     * @param int $default_assigment_id
     * @return workflow
     */
    public static function clone(
        workflow $workflow,
        string $name,
        int $default_assigment_type,
        int $default_assigment_id
    ): workflow {
        $new_workflow = $workflow->clone(
            $name,
            $default_assigment_type,
            $default_assigment_id
        );
        $new_workflow_version = $new_workflow->get_latest_version();

        $old_workflow_version = $workflow->get_latest_version();
        $old_workflow_stages = $old_workflow_version->get_stages();
        foreach ($old_workflow_stages as $old_workflow_stage) {
            $new_workflow_stage = $old_workflow_stage->clone($new_workflow_version);
            self::clone_workflow_stage_notifications($workflow, $new_workflow, $old_workflow_stage, $new_workflow_stage);
            self::clone_workflow_stage_formviews($old_workflow_stage, $new_workflow_stage);
            self::clone_workflow_stage_approval_levels($old_workflow_stage, $new_workflow_stage);
            self::clone_workflow_stage_interactions($old_workflow_stage, $new_workflow_stage);
        }

        // Activate default assignment for new workflow.
        $new_workflow->get_default_assignment()->activate();

        return $new_workflow;
    }

    /**
     * Clone all workflow stage formviews
     *
     * @param workflow_stage $old_workflow_stage
     * @param workflow_stage $new_workflow_stage
     * @return void
     */
    private static function clone_workflow_stage_formviews(
        workflow_stage $old_workflow_stage,
        workflow_stage $new_workflow_stage
    ): void {
        /** @var collection $formviews */
        $formviews = $old_workflow_stage->get_formviews();
        foreach ($formviews as $formview) {
            $formview->clone($new_workflow_stage);
        }
    }

    /**
     * Clone all workflow stage approval levels
     *
     * @param workflow_stage $old_workflow_stage
     * @param workflow_stage $new_workflow_stage
     * @return void
     */
    private static function clone_workflow_stage_approval_levels(
        workflow_stage $old_workflow_stage,
        workflow_stage $new_workflow_stage
    ): void {
        /** @var collection $approval_levels */
        $approval_levels = $old_workflow_stage->get_approval_levels();
        foreach ($approval_levels as $approval_level) {
            $approval_level->clone($new_workflow_stage);
        }
    }

    /**
     * Clone all notifications for new workflow
     *
     * @param workflow $old_workflow
     * @param workflow $new_workflow
     * @param workflow_stage $old_workflow_stage
     * @param workflow_stage $new_workflow_stage
     */
    private static function clone_workflow_stage_notifications(
        workflow $old_workflow,
        workflow $new_workflow,
        workflow_stage $old_workflow_stage,
        workflow_stage $new_workflow_stage
    ): void {
        $extended_context = extended_context::make_with_id(
            $old_workflow->get_context()->id,
            'mod_approval',
            'workflow_stage',
            $old_workflow_stage->id
        );
        $new_extended_context = extended_context::make_with_id(
            $new_workflow->get_context()->id,
            'mod_approval',
            'workflow_stage',
            $new_workflow_stage->id
        );
        $notification_preferences = notification_preference_loader::get_notification_preferences($extended_context, null, true);
        foreach ($notification_preferences as $notification_preference) {
            $parent = $notification_preference->get_parent();
            if ($parent) {
                $parent_path = $parent->get_extended_context()->get_context()->path;
                $current_path = $extended_context->get_context()->path;
                if (stripos($current_path, $parent_path) === false) {
                    continue;
                }
            }
            $builder = new notification_preference_builder(
                $notification_preference->get_resolver_class_name(),
                $new_extended_context
            );
            $builder->set_ancestor_id($notification_preference->get_ancestor_id());
            $builder->set_notification_class_name($notification_preference->get_notification_class_name());
            $builder->set_additional_criteria($notification_preference->get_additional_criteria());
            $builder->set_body($notification_preference->get_body());
            $builder->set_body_format($notification_preference->get_body_format());
            $builder->set_subject($notification_preference->get_subject());
            $builder->set_subject_format($notification_preference->get_subject_format());
            $builder->set_enabled($notification_preference->get_enabled());
            $builder->set_schedule_offset($notification_preference->get_schedule_offset());
            $builder->set_recipients($notification_preference->get_recipients());
            $builder->set_forced_delivery_channels($notification_preference->get_forced_delivery_channels());
            $builder->save();
        }

        $notifiable_event_preferences = notifiable_event_preference_entity::repository()
            ->select('*')
            ->filter_by_extended_context($extended_context)
            ->get();
        foreach ($notifiable_event_preferences as $notifiable_event_preference) {
            $new_notifiable_event_preference = notifiable_event_preference::create(
                $notifiable_event_preference->resolver_class_name,
                $new_extended_context,
                $notifiable_event_preference->enabled
            );

            $raw_list = $notifiable_event_preference->get_attribute('default_delivery_channels');
            $resolver_class_name = $notifiable_event_preference->get_attribute('resolver_class_name');

            if ($raw_list === null) {
                $new_notifiable_event_preference->set_default_delivery_channels(
                    delivery_channel_loader::get_for_event_resolver($resolver_class_name)
                );
            } else {
                $list = explode(',', $raw_list);
                $new_notifiable_event_preference->set_default_delivery_channels(
                    delivery_channel_loader::get_from_list($resolver_class_name, $list)
                );
            }
            $new_notifiable_event_preference->save();
        }
    }

    /**
     * Clone all workflow stage interactions
     *
     * @param workflow_stage $old_workflow_stage
     * @param workflow_stage $new_workflow_stage
     * @return void
     */
    private static function clone_workflow_stage_interactions(
        workflow_stage $old_workflow_stage,
        workflow_stage $new_workflow_stage
    ): void {
        /** @var collection $interactions */
        $interactions = $old_workflow_stage->get_interactions();
        foreach ($interactions as $interaction) {
            $interaction->clone($new_workflow_stage);
        }
    }
}