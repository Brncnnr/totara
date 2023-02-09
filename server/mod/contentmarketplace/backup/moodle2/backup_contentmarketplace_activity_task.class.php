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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author  Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package mod_contentmarketplace
 */

global $CFG;
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

use mod_contentmarketplace\backup\backup_activity_structure_step;

class backup_contentmarketplace_activity_task extends backup_activity_task {

    /**
     * @inheritDoc
     */
    protected function define_my_settings(): void {
        // No particular settings for this activity
    }

    /**
     * @inheritDoc
     */
    protected function define_my_steps(): void {
        $this->add_step(new backup_activity_structure_step('contentmarketplace_structure', 'contentmarketplace.xml'));
    }

    /**
     * @inheritDoc
     */
    public static function encode_content_links($content, backup_task $task = null): string {
        return $content;
    }
}
