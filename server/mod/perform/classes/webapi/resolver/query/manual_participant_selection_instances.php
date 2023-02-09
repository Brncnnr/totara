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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\webapi\resolver\query;

use context_coursecat;
use core\collection;
use core\entity\user;
use core\orm\entity\repository;
use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_login;
use core\webapi\query_resolver;
use mod_perform\entity\activity\manual_relationship_selection_progress;
use mod_perform\models\activity\helpers\manual_participant_helper;
use mod_perform\models\activity\subject_instance;
use mod_perform\util;
use totara_core\relationship\relationship;

/**
 * Get the subject instances and their manual relationships that the current user can set the participants for.
 *
 * @package mod_perform\webapi\resolver\query
 */
class manual_participant_selection_instances extends query_resolver {

    /**
     * {@inheritdoc}
     */
    public static function resolve(array $args, execution_context $ec) {
        $ec->set_relevant_context(context_coursecat::instance(util::get_default_category_id()));

        $user_id = user::logged_in()->id;

        $pending_selections = manual_participant_helper::for_user($user_id)
            ->build_pending_selections_query()
            ->with([
                'subject_instance' => static function (repository $repository) {
                    $repository
                        ->with('subject_user')
                        ->with('track.activity');
                },
                'manual_relationship_selection.participant_relationship.resolvers',
            ])
            ->get();

        return self::group_by_subject_instance($pending_selections);
    }

    /**
     * {@inheritdoc}
     */
    public static function get_middleware(): array {
        return [
            new require_advanced_feature('performance_activities'),
            new require_login(),
        ];
    }

    /**
     * Groups the pending selections by subject instance, and wrap subject instance together with available relationships.
     *
     * @param collection|manual_relationship_selection_progress[] $pending_selections
     * @return array
     */
    private static function group_by_subject_instance(collection $pending_selections): array {
        $selection_instances = [];
        foreach ($pending_selections as $selection) {
            $id = $selection->subject_instance_id;

            $subject_instance = subject_instance::load_by_entity($selection->subject_instance);
            if ($subject_instance->should_be_hidden()) {
                continue;
            }

            if (!isset($selection_instances[$id])) {
                $selection_instances[$id] = (object) [
                    'subject_instance' => $subject_instance,
                    'manual_relationships' => [],
                ];
            }

            $relationship_entity = $selection->manual_relationship_selection->participant_relationship;
            $selection_instances[$id]->manual_relationships[] = relationship::load_by_entity($relationship_entity);
        }

        // Make sure the relationships are ordered correctly.
        foreach ($selection_instances as $selection_instance) {
            usort($selection_instance->manual_relationships, static function (relationship $a, relationship $b) {
                return $a->sort_order <=> $b->sort_order;
            });
        }

        return array_values($selection_instances);
    }

}
