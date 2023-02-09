<?php
/*
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
 * @author  Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_facetoface
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Wrapper for adding a new template.
 *
 * @param string $reference
 * @param string $title
 * @param string $body
 * @param integer $conditiontype MDL_F2F_CONDITION_xx as **a magic number**
 */
function facetoface_upgradelib_add_new_template(string $reference, string $title, string $body, int $conditiontype) {
    global $DB;
    /** @var moodle_database $DB */

    if (\core_text::strlen($title) > 255) {
        $title = \core_text::substr($title, 0, 255);
    }

    // Make sure that $conditiontype is a power of 2.
    if (($conditiontype & ($conditiontype - 1)) !== 0) {
        throw new coding_exception('$conditiontype is not a valid number.');
    }

    $body = text_to_html($body);

    $templateid = $DB->get_field('facetoface_notification_tpl', 'id', ['reference' => $reference]);
    if ($templateid === false) {
        $templateid = $DB->insert_record('facetoface_notification_tpl', [
            'status' => 1,
            'reference' => $reference,
            'title' => $title,
            'body' => $body,
            'ccmanager' => 0,
        ]);
    }

    // Now add the new template to existing seminars that don't already have one.
    // NOTE: We don't normally want to do this, but it's safe to do
    //       here since this is replacing an existing non-template notification.
    $sql = 'SELECT f.id, f.course
              FROM {facetoface} f
         LEFT JOIN {facetoface_notification} fn
                ON fn.facetofaceid = f.id
               AND fn.conditiontype = :ctype
             WHERE fn.id IS NULL';
    $f2fs = $DB->get_records_sql($sql, ['ctype' => $conditiontype]);

    $data = [
        'type' => 4, // MDL_F2F_NOTIFICATION_AUTO.
        'conditiontype' => $conditiontype,
        'booked' => 0,
        'waitlisted' => 0,
        'cancelled' => 0,
        'requested' => 0,
        'issent' => 0,
        'status' => 1, // Replacing a hard-coded template
        'templateid' => $templateid,
        'ccmanager' => 0,
        'title' => $title,
        'body' => $body,
    ];

    foreach ($f2fs as $f2f) {
        $notification = array_merge([
            'facetofaceid' => $f2f->id,
            'courseid' => $f2f->course,
        ], $data);

        $DB->insert_record('facetoface_notification', $notification);
    }
}

/**
 * Fixed the orphaned records with statuscode 50 as we deprecated "Approved" status.
 */
function facetoface_upgradelib_approval_to_declined_status() {
    global $DB;

    $superceded = 0;
    $statuscode = 50;
    $statuses = $DB->get_records_sql(
        'SELECT fss.*, u.id AS userid, f.id AS facetofaceid
           FROM {facetoface_signups_status} fss
           JOIN {facetoface_signups} fs ON fs.id = fss.signupid
           JOIN {facetoface_sessions} s ON s.id = fs.sessionid
           JOIN {facetoface} f ON f.id = s.facetoface
           JOIN {user} u ON u.id = fs.userid
          WHERE superceded = :superceded AND statuscode = :statuscode',
        ['superceded' => $superceded, 'statuscode' => $statuscode]
    );
    /** @see \mod_facetoface\signup\state\declined::get_code() */
    $declined_status = 30;
    $upgrade_log_notice = defined('UPGRADE_LOG_NOTICE') ? UPGRADE_LOG_NOTICE : 1;
    $trans = $DB->start_delegated_transaction();
    foreach ($statuses as $status) {
        // Update the record.
        $DB->set_field('facetoface_signups_status', 'statuscode', $declined_status, ['id' => $status->id]);

        // Add a log message.
        upgrade_log(
            $upgrade_log_notice,
            'mod_facetoface',
            'Invalid user signup cancelled: userid ' . $status->userid . ', facetofaceid ' . $status->facetofaceid
        );
    }
    $trans->allow_commit();
}

/**
 * Fixed the orphaned url records left after a room is changed from 'Internal' to 'MS teams'.
 */
function facetoface_upgradelib_clear_room_url() {
    global $DB;

    $trans = $DB->start_delegated_transaction();
    $rooms = $DB->get_records_sql(
        "SELECT frvm.roomid
           FROM {facetoface_room_virtualmeeting} frvm
           JOIN {facetoface_room} fr ON fr.id = frvm.roomid
          WHERE fr.url != ''"
    );
    foreach ($rooms as $room) {
        $DB->set_field('facetoface_room', 'url', '', ['id' => $room->roomid]);
    }
    $trans->allow_commit();
}

function facetoface_upgradelib_upgrade_existing_virtual_meetings() {
    global $DB;

    $DB->set_field_select('facetoface_room_virtualmeeting', 'status', 0, 'status IS NULL'); // STATUS_CONFIRMED
    $DB->set_field_select('facetoface_room_dates_virtualmeeting', 'status', -3, 'status IS NULL AND sessionsdateid IS NULL AND roomid IS NULL'); // STATUS_PENDING_DELETION

    $rooms = $DB->get_records_sql(
        "SELECT fr.id
           FROM {facetoface_room} fr
     INNER JOIN {facetoface_room_virtualmeeting} frvm ON frvm.roomid = fr.id"
    );
    foreach ($rooms as $room) {
        $room_dates = $DB->get_records_sql(
            "SELECT frd.*, frdvm.id AS roomdatevirtualmeetingid, frdvm.virtualmeetingid
               FROM {facetoface_room_dates} frd
          LEFT JOIN {facetoface_room_dates_virtualmeeting} frdvm
                 ON frdvm.sessionsdateid = frd.sessionsdateid AND frdvm.roomid = frd.roomid
         INNER JOIN {facetoface_room} fr ON fr.id = frd.roomid
         INNER JOIN {facetoface_sessions_dates} fsd ON fsd.id = frd.sessionsdateid
              WHERE fr.id = :roomid
                AND frdvm.status IS NULL
           ORDER BY frd.id ASC",
            ['roomid' => $room->id],
        );

        // Process
        foreach ($room_dates as $room_date) {
            // Create? or Update?
            if (empty($room_date->roomdatevirtualmeetingid)) {
                $DB->insert_record(
                    'facetoface_room_dates_virtualmeeting',
                    [
                        'sessionsdateid' => $room_date->sessionsdateid,
                        'roomid' => $room_date->roomid,
                        'status' => -2, // STATUS_PENDING_UPDATE
                        'virtualmeetingid' => null,
                    ],
                    false
                );
            } else {
                if (empty($room_date->virtualmeetingid)) {
                    $DB->set_field(
                        'facetoface_room_dates_virtualmeeting',
                        'status',
                        -2, // STATUS_PENDING_UPDATE
                        ['id' => $room_date->roomdatevirtualmeetingid]
                    );
                } else {
                    $DB->set_field(
                        'facetoface_room_dates_virtualmeeting',
                        'status',
                        1, // STATUS_AVAILABLE
                        ['id' => $room_date->roomdatevirtualmeetingid]
                    );
                }
            }
        }
    }
}

/**
 * Replace 'sessiondate' with 'sessionstartdate' and 'datefinish' with 'sessionfinishdate' column values
 * for 'rb_source_facetofcae_sessions' and 'rb_source_facetoface_signin' seminar report sources to make consistency and
 * use it as a single column value for all seminar report sources
 */
function facetoface_upgradelib_migrate_reoportbuilder_date_fields() {
    global $CFG;
    require_once($CFG->dirroot.'/totara/reportbuilder/db/upgradelib.php');

    reportbuilder_rename_data('columns', 'facetoface_signin', 'date', 'sessiondate', 'date', 'sessionstartdate');
    reportbuilder_rename_data('columns', 'facetoface_signin', 'date', 'datefinish', 'date', 'sessionfinishdate');
    reportbuilder_rename_data('columns', 'facetoface_sessions', 'date', 'sessiondate', 'date', 'sessionstartdate');
    reportbuilder_rename_data('columns', 'facetoface_sessions', 'date', 'datefinish', 'date', 'sessionfinishdate');

    reportbuilder_rename_data('filters', 'facetoface_signin', 'date', 'sessiondate', 'date', 'sessionstartdate');
    reportbuilder_rename_data('filters', 'facetoface_signin', 'date', 'datefinish', 'date', 'sessionfinishdate');
    reportbuilder_rename_data('filters', 'facetoface_sessions', 'date', 'sessiondate', 'date', 'sessionstartdate');
    reportbuilder_rename_data('filters', 'facetoface_sessions', 'date', 'datefinish', 'date', 'sessionfinishdate');

    reportbuilder_rename_data('search_cols', 'facetoface_signin', 'date', 'sessiondate', 'date', 'sessionstartdate');
    reportbuilder_rename_data('search_cols', 'facetoface_signin', 'date', 'datefinish', 'date', 'sessionfinishdate');
    reportbuilder_rename_data('search_cols', 'facetoface_sessions', 'date', 'sessiondate', 'date', 'sessionstartdate');
    reportbuilder_rename_data('search_cols', 'facetoface_sessions', 'date', 'datefinish', 'date', 'sessionfinishdate');

    totara_reportbuilder_migrate_saved_searches('facetoface_signin', 'date', 'sessiondate', 'date', 'sessionstartdate');
    totara_reportbuilder_migrate_saved_searches('facetoface_signin', 'date', 'datefinish', 'date', 'sessionfinishdate');
    totara_reportbuilder_migrate_saved_searches('facetoface_sessions', 'date', 'sessiondate', 'date', 'sessionstartdate');
    totara_reportbuilder_migrate_saved_searches('facetoface_sessions', 'date', 'datefinish', 'date', 'sessionfinishdate');

    totara_reportbuilder_migrate_default_sort_columns_by_source('facetoface_signin', 'date', 'sessiondate', 'date', 'sessionstartdate');
    totara_reportbuilder_migrate_default_sort_columns_by_source('facetoface_signin', 'date', 'datefinish', 'date', 'sessionfinishdate');
    totara_reportbuilder_migrate_default_sort_columns_by_source('facetoface_sessions', 'date', 'sessiondate', 'date', 'sessionstartdate');
    totara_reportbuilder_migrate_default_sort_columns_by_source('facetoface_sessions', 'date', 'datefinish', 'date', 'sessionfinishdate');

    totara_reportbuilder_migrate_svggraph_category('facetoface_signin', 'date', 'sessiondate', 'date', 'sessionstartdate');
    totara_reportbuilder_migrate_svggraph_category('facetoface_signin', 'date', 'datefinish', 'date', 'sessionfinishdate');
    totara_reportbuilder_migrate_svggraph_category('facetoface_sessions', 'date', 'sessiondate', 'date', 'sessionstartdate');
    totara_reportbuilder_migrate_svggraph_category('facetoface_sessions', 'date', 'datefinish', 'date', 'sessionfinishdate');
}