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
 * @package container_workspace
 */
namespace container_workspace\webapi\resolver\type;

use coding_exception;
use container_workspace\formatter\member\formatter;
use container_workspace\interactor\workspace\interactor as workspace_interactor;
use container_workspace\interactor\member\interactor as member_interactor;
use context_course;
use core\webapi\execution_context;
use core\webapi\type_resolver;
use container_workspace\member\member as workspace_member;
use container_workspace\member\status;
use core\entity\user;

/**
 * Resolver for type member
 */
class member extends type_resolver {
    /**
     * @param string            $field
     * @param workspace_member  $member
     * @param array             $args
     * @param execution_context $ec
     *
     * @return mixed|void
     */
    public static function resolve(string $field, $member, array $args, execution_context $ec) {
        if (!($member instanceof workspace_member)) {
            throw new coding_exception("The parameter source is invalid");
        }

        if (!$ec->has_relevant_context()) {
            $workspace_id = $member->get_workspace_id();
            $context = context_course::instance($workspace_id);

            $ec->set_relevant_context($context);
        }

        switch ($field) {
            case 'id':
                return $member->get_id();

            case 'user':
                return $member->get_user_record();

            case 'status':
                $status = $member->get_status();
                return status::get_code($status);

            case 'workspace_interactor':
                $workspace_id = $member->get_workspace_id();
                $user_id = $member->get_user_id();

                return workspace_interactor::from_workspace_id($workspace_id, $user_id);

            case 'audiences':
                $user = user::logged_in();
                $workspace_id = $member->get_workspace_id();

                if (is_null($user)) {
                    return [];
                }

                if (!workspace_interactor::from_workspace_id($workspace_id, $user->id)->can_add_audiences()) {
                    return [];
                }

                return $member->get_audiences();

            case 'member_interactor':
                $actor_id = null;
                if (isset($args['user_id'])) {
                    $actor_id = $args['user_id'];
                }

                return new member_interactor($member, $actor_id);

            default:
                $format = null;
                if (isset($args['format'])) {
                    $format = $args['format'];
                }

                $context = $ec->get_relevant_context();

                $formatter = new formatter($member, $context);
                return $formatter->format($field, $format);
        }
    }
}