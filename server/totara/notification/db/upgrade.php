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
 * @package totara_notification
 */
defined('MOODLE_INTERNAL') || die();

/**
 * @param $old_version
 * @return bool
 * @throws coding_exception
 * @throws ddl_exception
 * @throws ddl_table_missing_exception
 * @throws dml_exception
 * @throws downgrade_exception
 * @throws upgrade_exception
 */
function xmldb_totara_notification_upgrade($old_version) {
    global $DB, $CFG;
    require_once("{$CFG->dirroot}/totara/notification/db/upgradelib.php");

    if ($old_version < 2022110802) {

        // Backup body and subject fields
        $sql_subject_backup = "UPDATE {notification_preference} SET subject_backup = subject WHERE subject IS NOT NULL AND subject_backup IS NULL";
        $sql_body_backup = "UPDATE {notification_preference} SET body_backup = body WHERE body IS NOT NULL AND body_backup IS NULL";

        $DB->execute($sql_subject_backup);
        $DB->execute($sql_body_backup);

        // Convert notifications' invalid line breaks on the subject and body to make Weka Editor compatible.
        totara_notification_upgrade_convert_invalid_line_break();

        // Program savepoint reached.
        upgrade_plugin_savepoint(true, 2022110802, 'totara', 'notification');
    }

    return true;
}