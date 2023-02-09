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

namespace mod_approval\settings;

use coding_exception;
use container_approval\approval;
use context_system;
use core\orm\query\builder;
use coursecat;
use totara_comment\entity\comment;
use core\entity\course_categories;

/**
 * Uninstaller
 */
final class uninstaller {
    /**
     * Perform uninstallation.
     */
    public static function uninstall(): void {
        if (!has_capability('moodle/site:config', context_system::instance())) {
            throw new coding_exception('You do not have permission to uninstall approval workflow');
        }

        builder::get_db()->transaction(function () {
            self::delete_comments();
            self::delete_categories();
            self::delete_forms();
        });
    }

    /**
     * Delete comments.
     */
    private static function delete_comments(): void {
        comment::repository()->where('component', 'mod_approval')->delete();
    }

    /**
     * Delete categories.
     */
    private static function delete_categories(): void {
        $category_ids = course_categories::repository()
            ->select('id')
            ->where('issystem', 1)
            ->where('name', approval::get_container_category_name())
            ->get()
            ->pluck('id');

        $coursecats = coursecat::get_many($category_ids);
        foreach ($coursecats as $coursecat) {
            $coursecat->delete_full();
        }
    }

    /**
     * Delete forms.
     */
    private static function delete_forms(): void {
        // We don't need to delete forms manually at the moment because
        // the plugin manager drops all approval tables.
        // However, if a plugin generates some files, we may need to
        // call out a plugin's entrypoint to let the plugin delete them.
    }
}
