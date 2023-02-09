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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package container_workspace
 */
defined('MOODLE_INTERNAL') || die();

function container_workspace_update_hidden_workspace_with_audience_visibility(): void {
    global $DB, $CFG;

    if (!defined('COHORT_VISIBLE_ENROLLED')) {
        require_once("{$CFG->dirroot}/totara/core/totara.php");
    }

    $DB->execute(
        'UPDATE "ttr_course" SET audiencevisible = :new_audience_visible 
         WHERE containertype = :workspace AND visible = 0 AND audiencevisible = :audience_visible',
        [
            'workspace' => 'container_workspace',
            'audience_visible' => COHORT_VISIBLE_ALL,
            'new_audience_visible' => COHORT_VISIBLE_ENROLLED
        ]
    );
}

function container_workspace_upgrade_migrate_messages() {
    totara_notification_sync_built_in_notification('container_workspace');
}