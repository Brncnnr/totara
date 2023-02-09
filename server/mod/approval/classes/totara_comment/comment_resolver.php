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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\totara_comment;

use coding_exception;
use mod_approval\interactor\application_interactor;
use mod_approval\model\application\application;
use mod_approval\model\workflow\stage_type\finished;
use totara_comment\comment;
use totara_comment\resolver;

/**
 * comment_resolver class referenced by totara_comment
 */
final class comment_resolver extends resolver {
    /**
     * @param integer $instance_id application id
     * @return boolean
     */
    private static function is_draft(int $instance_id): bool {
        return application::load_by_id($instance_id)->current_state->is_draft();
    }

    /**
     * @param integer $instance_id application id
     * @return boolean
     */
    private static function is_completed(int $instance_id): bool {
        return application::load_by_id($instance_id)->current_state->is_stage_type(finished::get_code());
    }

    /**
     * @param string $area
     * @param integer $instance_id
     * @return boolean|null
     */
    private static function common_checks(string $area, int $instance_id): ?bool {
        if ($area !== 'comment') {
            return false;
        }
        if (self::is_draft($instance_id)) {
            return false;
        }
        return null;
    }

    /**
     * @param integer $instance_id
     * @param string $area
     * @param integer $actor_id
     * @return boolean
     */
    private static function can_view_comment_helper(int $instance_id, string $area, int $actor_id): bool {
        if (($check = self::common_checks($area, $instance_id)) !== null) {
            return $check;
        }
        $interactor = application_interactor::from_application_id($instance_id, $actor_id);
        return $interactor->can_view_comments();
    }

    public function is_allow_to_create(int $instanceid, string $area, int $actorid): bool {
        if (($check = self::common_checks($area, $instanceid)) !== null) {
            return $check;
        }
        if (self::is_completed($instanceid)) {
            return false;
        }
        $interactor = application_interactor::from_application_id($instanceid, $actorid);
        return $interactor->can_post_comment();
    }

    public function is_allow_to_delete(comment $comment, int $actorid): bool {
        if (($check = self::common_checks($comment->get_area(), $comment->get_instanceid())) !== null) {
            return $check;
        }
        if (is_siteadmin($actorid)) {
            return true;
        }
        if ($comment->get_userid() != $actorid) {
            return false;
        }
        $interactor = application_interactor::from_application_id($comment->get_instanceid(), $actorid);
        return $interactor->can_delete_comment();
    }

    public function is_allow_to_update(comment $comment, int $actorid): bool {
        if (($check = self::common_checks($comment->get_area(), $comment->get_instanceid())) !== null) {
            return $check;
        }
        if (is_siteadmin($actorid)) {
            return true;
        }
        if ($comment->get_userid() != $actorid) {
            return false;
        }
        $interactor = application_interactor::from_application_id($comment->get_instanceid(), $actorid);
        return $interactor->can_post_comment();
    }

    public function get_context_id(int $instance_id, string $area): int {
        if ($area === 'comment') {
            return application::load_by_id($instance_id)->get_context()->id;
        }
        throw new coding_exception('area is invalid');
    }

    public function get_owner_id_from_instance(string $area, int $instance_id): ?int {
        if ($area === 'comment') {
            return application::load_by_id($instance_id)->user_id;
        }
        throw new coding_exception('area is invalid');
    }

    public function can_create_reaction_on_comment(comment $comment, int $actor_id): bool {
        return false;
    }

    public function can_view_reactions_of_comment(comment $comment, int $actor_id): bool {
        return false;
    }

    public function can_see_comments(int $instance_id, string $area, int $actor_id): bool {
        return $this->can_view_comment_helper($instance_id, $area, $actor_id);
    }

    public function can_see_replies(int $instance_id, string $area, int $actor_id): bool {
        return $this->can_view_comment_helper($instance_id, $area, $actor_id);
    }

    public function can_report_comment(comment $comment, int $actor_id): bool {
        return false;
    }

    public function can_report_reply(comment $comment, int $actor_id): bool {
        return false;
    }
}
