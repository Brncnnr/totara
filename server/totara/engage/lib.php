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
 * @package totara_engage
 */
defined('MOODLE_INTERNAL') || die();

use core_user\output\myprofile\category;
use core_user\output\myprofile\tree;
use core_user\output\myprofile\node;
use totara_core\advanced_feature;
use core\orm\query\builder;

/**
 * This is a callback from {@see core_user\output\myprofile\manager}
 *
 * @param tree          $tree
 * @param int|stdClass  $user_or_id
 * @param bool          $is_current_user
 * @param stdClass|null $course
 *
 * @return bool
 */
function totara_engage_myprofile_navigation(tree $tree, $user_or_id, $is_current_user = false, $course = null) {
    if (!advanced_feature::is_enabled('engage_resources')) {
        return false;
    } else if (isguestuser($user_or_id)) {
        // Guess user should not have social block at all.
        return false;
    }

    $user = $user_or_id;
    if (!is_object($user_or_id)) {
        // Get user record to construct fullname.
        $builder = builder::table('user');
        $builder->select(['id', 'email']);

        $user_name_fields = get_all_user_name_fields(true);
        $builder->add_select_raw($user_name_fields);

        $builder->where('id', $user_or_id);
        $user = $builder->one();
    }

    $engage_category = new category('engage', get_string('engage_category', 'totara_engage'));
    $library_node = new node(
        'engage',
        'user_library',
        get_string('usersresources', 'totara_engage', fullname($user)),
        null,
        new moodle_url('/totara/engage/user_resources.php', ['user_id' => $user->id])
    );

    $tree->add_category($engage_category);
    $tree->add_node($library_node);

    return true;
}