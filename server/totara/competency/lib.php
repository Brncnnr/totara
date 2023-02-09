<?php
/*
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
 * @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com>
 * @package totara_competency
 */

use core_user\output\myprofile\node;
use core_user\output\myprofile\tree;
use pathway_manual\models\roles;
use totara_competency\helpers\capability_helper;
use totara_core\advanced_feature;

defined('MOODLE_INTERNAL') || die();

/**
 * Add competency profile link to the user profile page
 *
 * @param tree $tree Tree object
 * @param stdClass $user user object
 * @param bool $this_user
 * @param stdClass $course Course object
 *
 * @return bool
 */
function totara_competency_myprofile_navigation(tree $tree, $user, $this_user, $course) {
    if (!advanced_feature::is_enabled('competency_assignment')) {
        return true;
    }

    $can_view = capability_helper::can_view_profile($user->id);

    if ($can_view) {
        $tree->add_node(
            new node(
                'development',
                'competency_profile',
                get_string('competency_profile', 'totara_competency'),
                null,
                new moodle_url('/totara/competency/profile/index.php', $this_user ? [] : ['user_id' => $user->id])
            )
        );
    }

    if ($this_user && !empty(roles::get_current_user_roles_for_any())) {
        $tree->add_node(
            new node(
                'development',
                'rate_others_competencies',
                get_string('rate_others_competencies', 'totara_competency'),
                null,
                new moodle_url('/totara/competency/rate_users.php')
            )
        );
    }

    return true;
}