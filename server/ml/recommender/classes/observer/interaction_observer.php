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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package ml_recommender
 */

namespace ml_recommender\observer;

use core\event\base;
use core\event\course_deleted;
use core\event\course_viewed;
use core_ml\event\interaction_event;
use core_ml\event\public_access_aware_event;
use ml_recommender\entity\component;
use ml_recommender\entity\interaction;
use ml_recommender\entity\interaction_type;
use ml_recommender\repository\interaction_repository;
use totara_core\advanced_feature;

/**
 * For generating the interaction record
 */
final class interaction_observer {
    /**
     * Preventing this class from being constructed
     * interaction_observer constructor.
     */
    private function __construct() {
    }

    /**
     * @param interaction_event $event
     * @param int $time time of interaction, defaults to current time if null
     */
    public static function watch_interaction(interaction_event $event, ?int $time = null): void {
        // Don't observe anything if recommender is disabled
        if (advanced_feature::is_disabled('ml_recommender')) {
            return;
        }

        // Don't save any private interactions
        if ($event instanceof public_access_aware_event &&  !$event->is_public()) {
            return;
        }

        // Don't observe guest user interactions
        if (isguestuser($event->get_user_id())) {
            return;
        }

        // Record the event, but if it fails, let it fail silently.
        // It's possible for multiple of the same event to occur at the same
        // time and we don't want them crashing the page.
        try {
            $component_repo = component::repository();
            $type_repo = interaction_type::repository();
            /** @var interaction_repository $interaction_repo */
            $interaction_repo = interaction::repository();

            $entity = new interaction();
            $entity->user_id = $event->get_user_id();
            $entity->component_id = $component_repo->ensure_id($event->get_component(), $event->get_area());
            $entity->rating = $event->get_rating();
            $entity->interaction_type_id = $type_repo->ensure_id($event->get_interaction_type());
            $entity->item_id = $event->get_item_id();
            $entity->set_created_timestamp($time);
            if (!$interaction_repo->has_interaction($entity)) {
                $entity->save();
            }
        } catch (\dml_exception $exception) {
            // Do nothing
        }
    }

    /**
     * @param base $event
     * @param int $time time of interaction, defaults to current time if null
     */
    public static function watch_core(base $event, ?int $time = null): void {
        // Don't observe anything if recommender is disabled
        if (advanced_feature::is_disabled('ml_recommender')) {
            return;
        }

        $component = $event->component;
        $item_id = $event->objectid;

        if ($event instanceof course_viewed) {
            $item_id = $event->courseid;
            if (SITEID == $item_id) {
                // Skip for SITE course.
                return;
            }

            $component = 'container_course';
        }

        // Record the event, but if it fails, let it fail silently.
        // It's possible for multiple of the same event to occur at the same
        // time and we don't want them crashing the page.
        try {
            $component_repo = component::repository();
            $type_repo = interaction_type::repository();
            /** @var interaction_repository $interaction_repo */
            $interaction_repo = interaction::repository();

            $entity = new interaction();
            $entity->user_id = $event->userid;
            $entity->component_id = $component_repo->ensure_id($component);
            $entity->rating = 1;
            $entity->interaction_type_id = $type_repo->ensure_id('view');
            $entity->item_id = $item_id;
            $entity->set_created_timestamp($time);

            if (!$interaction_repo->has_interaction($entity)) {
                $entity->save();
            }
        } catch (\dml_exception $exception) {
            // Do nothing
        }
    }

    /**
     * @param interaction_event $event
     */
    public static function watch_delete(interaction_event $event): void {
        // Don't observe anything if recommender is disabled
        if (advanced_feature::is_disabled('ml_recommender')) {
            return;
        }

        $component = $event->get_component();
        $item_id = $event->get_item_id();

        /** @var interaction_repository $repo */
        $repo = interaction::repository();
        $repo->delete_for_component($component, $item_id);
    }

    /**
     * @param base $event
     */
    public static function watch_core_delete(base $event): void {
        // Don't observe anything if recommender is disabled
        if (advanced_feature::is_disabled('ml_recommender')) {
            return;
        }

        $component = $event->component;
        $item_id = $event->objectid;

        if ($event instanceof course_deleted) {
            $component = 'container_course';
            $item_id = $event->courseid;
        }

        /** @var interaction_repository $repo */
        $repo = interaction::repository();
        $repo->delete_for_component($component, $item_id);
    }
}