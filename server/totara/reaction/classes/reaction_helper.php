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
 * @package totara_reaction
 */
namespace totara_reaction;

use totara_reaction\exception\reaction_exception;
use totara_reaction\event\reaction_created;
use totara_reaction\event\reaction_removed;
use totara_reaction\loader\reaction_loader;
use totara_reaction\resolver\resolver_factory;

/**
 * A helper class to centralise one API for every thing.
 * For example: an API to delete the reactions related to the instance.
 */
final class reaction_helper {
    /**
     * Preventing this class from being constructed.
     * reaction_helper constructor.
     */
    private function __construct() {
    }

    /**
     * Deleting all the reactions that are related to the instance. Note that this function
     * will not check for any capabilities, as it should had been checked prior to calling this API.
     *
     * @param string $component
     * @param string $area
     * @param int $instance_id
     *
     * @return bool
     */
    public static function purge_area_reactions(string $component, string $area, int $instance_id): bool {
        global $DB;
        return $DB->delete_records(
            'reaction',
            [
                'instanceid' => $instance_id,
                'component' => $component,
                'area' => $area
            ]
        );
    }


    /**
     * @param reaction  $reaction
     * @param int|null  $user_id    The actor's id, of who is performing this action. User in session
     *                              will be used, if none is provided.
     * @return bool
     */
    public static function purge_reaction(reaction $reaction, ?int $user_id = null): bool {
        global $USER;

        if (!$reaction->exists()) {
            debugging("The reaction record had already been deleted", DEBUG_DEVELOPER);
            return false;
        }

        if (null === $user_id || 0 === $user_id) {
            $user_id = $USER->id;
        }

        $owner_id = $reaction->get_userid();
        if ($user_id != $owner_id) {
            // Actor is not the owner of the record.
            if (!is_siteadmin($user_id)) {
                // And actor is not a site_admin therefore we are going to stop everything from here.
                throw reaction_exception::on_delete();
            }
        }

        $event = reaction_removed::instance($reaction);
        $event->trigger();

        return $reaction->delete();
    }

    /**
     * @param int $instance_id
     * @param string $component
     * @param string $area
     * @param int|null $user_id
     *
     * @return reaction
     */
    public static function create_reaction(int $instance_id, string $component,
                                           string $area, ?int $user_id = null): reaction {
        global $USER;

        if (null === $user_id || 0 === $user_id) {
            $user_id = $USER->id;
        }

        $reaction = reaction_loader::find_by_parameters($component, $area, $instance_id, $user_id);
        if (null !== $reaction) {
            debugging("Reaction already exists", DEBUG_DEVELOPER);
            return $reaction;
        }

        $resolver = resolver_factory::create_resolver($component);

        if (!$resolver->can_create_reaction($instance_id, $user_id, $area)) {
            throw new \coding_exception(
                "Cannot create the reaction for instance '{$instance_id}' within area '{$area}'"
            );
        }

        $context = $resolver->get_context($instance_id, $area);
        $reaction = reaction::create(
            $component,
            $area,
            $instance_id,
            $context->id,
            $user_id
        );

        $event = reaction_created::instance($reaction);
        $event->trigger();

        return $reaction;
    }
}