<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2014 onwards Totara Learning Solutions LTD
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
 * @package totara_reportbuilder
 */

/**
 * Rename reportbuilder columns. Using the $type param to constrain the renaming to a single
 * type is recommended to avoid renaming columns unintentionally.
 *
 * @param array $values     An array with data formatted like array($oldname => $newname)
 * @param string $type      The type constraint, e.g. 'user'
 */
function totara_reportbuilder_migrate_column_names($values, $type = '') {
    global $DB;

    $typesql = '';
    $params = array();
    if (!empty($type)) {
        $typesql = ' AND type = :type';
        $params['type'] = $type;
    }

    foreach ($values as $oldname => $newname) {
        $sql = "UPDATE {report_builder_columns}
                   SET value = :newname
                 WHERE value = :oldname
                       {$typesql}";
        $params['newname'] = $newname;
        $params['oldname'] = $oldname;

        $DB->execute($sql, $params);
    }

    return true;
}

/**
 * Map old position columns to the new job_assignment columns.
 *
 * @param array $values     An array of the values we are updating the type of
 * @param string $oldtype   The oldtype
 * @param string $newtype
 */
function totara_reportbuilder_migrate_column_types($values, $oldtype, $newtype) {
    global $DB;

    // If there is nothing to migrate just return.
    if (empty($values)) {
        return true;
    }

    list($insql, $params) = $DB->get_in_or_equal($values, SQL_PARAMS_NAMED);
    $sql = "UPDATE {report_builder_columns}
               SET type = :newtype
             WHERE type = :oldtype
               AND value {$insql}";
    $params['newtype'] = $newtype;
    $params['oldtype'] = $oldtype;

    return $DB->execute($sql, $params);
}

/**
 * Rename reportbuilder filters. Using the $type param to constrain the renaming to a single
 * type is recommended to avoid renaming filters unintentionally.
 *
 * @param array $values     An array with data formatted like array($oldname => $newname)
 * @param string $type      The type constraint, e.g. 'user'
 */
function totara_reportbuilder_migrate_filter_names($values, $type = '') {
    global $DB;

    // If there is nothing to migrate just return.
    if (empty($values)) {
        return true;
    }

    $typesql = '';
    $params = array();
    if (!empty($type)) {
        $typesql = 'AND type = :type';
        $params['type'] = $type;
    }

    foreach ($values as $oldname => $newname) {
        $sql = "UPDATE {report_builder_filters}
                   SET value = :newname
                 WHERE value = :oldname
                       {$typesql}";
        $params['newname'] = $newname;
        $params['oldname'] = $oldname;

        $DB->execute($sql, $params);
    }

    return true;
}

/**
 * Map old position filters to the new job_assignment columns.
 */
function totara_reportbuilder_migrate_filter_types($values, $oldtype, $newtype) {
    global $DB;

    // If there is nothing to migrate just return.
    if (empty($values)) {
        return true;
    }

    list($insql, $params) = $DB->get_in_or_equal($values, SQL_PARAMS_NAMED);
    $sql = "UPDATE {report_builder_filters}
               SET type = :newtype
             WHERE type = :oldtype
               AND value {$insql}";
    $params['newtype'] = $newtype;
    $params['oldtype'] = $oldtype;

    return $DB->execute($sql, $params);
}

/**
 * Update the filters in any saved searches, generally used after migrating filter types.
 *
 * NOTE: This is a generic function suitable for general use
 * when migrating saved search data for any filter. This should
 * be used instead of {@link totara_reportbuilder_migrate_saved_search_filters()} which was specific to the 2.9 -> 9.0
 * multiple jobs migration.
 *
 * @param string $source Name of the source or '*' to update all sources
 * @param string $oldtype The type of the item to change
 * @param string $oldvalue The value of the item to change
 * @param string $newtype The new type of the item
 * @param string $newvalue The new value of the item
 * @return boolean True if data was updated, false otherwise.
 *
 */
function totara_reportbuilder_migrate_saved_searches($source, $oldtype, $oldvalue, $newtype, $newvalue) {
    global $DB;

    $savedsearchesupdated = false;

    if ($source == '*') {
        $sourcesql = '';
        $params = array();
    } else {
        $sourcesql = ' WHERE rb.source = :source';
        $params = array('source' => $source);
    }

    // Get all saved searches for specified source.
    $sql = "SELECT rbs.* FROM {report_builder_saved} rbs
        JOIN {report_builder} rb
        ON rb.id = rbs.reportid
        {$sourcesql}";
    $savedsearches = $DB->get_records_sql($sql, $params);

    // Loop through them all and json_decode
    foreach ($savedsearches as $saved) {
        if (empty($saved->search)) {
            continue;
        }

        $search = unserialize($saved->search);

        if (!is_array($search)) {
            continue;
        }

        // Check for any filters that will need to be updated.
        $update = false;
        foreach ($search as $oldkey => $info) {
            list($type, $value) = explode('-', $oldkey);

            if ($type == $oldtype && $value == $oldvalue) {
                $update = true;

                if (!empty($newtype) && !empty($newvalue)) {
                    $newkey = "{$newtype}-{$newvalue}";
                    $search[$newkey] = $info;
                }
                unset($search[$oldkey]);
            }
        }

        if ($update) {
            // Re encode and update the database.
            $todb = new \stdClass();
            $todb->id = $saved->id;
            $todb->search = serialize($search);
            $DB->update_record('report_builder_saved', $todb);
            $savedsearchesupdated = true;
        }
    }

    return $savedsearchesupdated;
}

/**
 * Update the filters in any saved searches, generally used after migrating filter types.
 *
 * NOTE: this function contains code specific to the migration
 * from 2.9 to 9.0 for multiple jobs. DO NOT USE this function
 * for generic saved search migrations, use
 * {@link totara_reportbuilder_migrate_saved_searches()} instead.
 */
function totara_reportbuilder_migrate_saved_search_filters($values, $oldtype, $newtype) {
    global $DB;

    // If there is nothing to migrate just return.
    if (empty($values)) {
        return true;
    }

    // Get all saved searches.
    $savedsearches = $DB->get_records('report_builder_saved');

    // Loop through them all and json_decode
    foreach ($savedsearches as $saved) {
        if (empty($saved)) {
            continue;
        }

        $search = unserialize($saved->search);

        if (!is_array($search)) {
            continue;
        }

        // Check for any filters that will need to be updated.
        $update = false;
        foreach ($search as $oldkey => $info) {
            list($type, $value) = explode('-', $oldkey);

            // NOTE: This isn't quite as generic as the other functions.
            $value = $value == 'posstartdate' ? 'startdate' : $value;
            $value = $value == 'posenddate' ? 'enddate' : $value;

            if ($type == $oldtype && in_array($value, array_keys($values))) {
                $update = true;

                if ($values[$value] == 'allpositions' || $values[$value] == 'allorganisations') {
                    if (isset($info['recursive']) && !isset($info['children'])) {
                        $info['children'] = $info['recursive'];
                        unset($info['recursive']);
                    } else {
                        $info['children'] = isset($info['children']) ? $info['children'] : 0;
                    }
                    $info['operator'] = isset($info['operator']) ? $info['operator'] : 1;
                }

                $newkey = "{$newtype}-{$values[$value]}";
                $search[$newkey] = $info;
                unset($search[$oldkey]);
            }
        }

        if ($update) {
            // Re encode and update the database.
            $saved->search = serialize($search);
            $DB->update_record('report_builder_saved', $saved);
        }
    }

    return true;
}

/**
 * Map reports default sort columns the to new job_assignment columns.
 */
function totara_reportbuilder_migrate_default_sort_columns($values, $oldtype, $newtype) {
    global $DB;

    // If there is nothing to migrate just return.
    if (empty($values)) {
        return true;
    }

    foreach ($values as $sort) {
        $sql = "UPDATE {report_builder}
                   SET defaultsortcolumn = :newsort
                 WHERE defaultsortcolumn = :oldsort";
        $params = array(
            'oldsort' => $oldtype . '_' . $sort,
            'newsort' => $newtype . '_' . $sort
        );

        $DB->execute($sql, $params);
    }

    return true;
}

/**
 * Scheduled reports belonging to a user are now deleted when the user gets deleted
 */
function totara_reportbuilder_delete_scheduled_reports() {
    global $DB;

    // Get the reports created by deleted user/s.
    $sql = "SELECT rbs.id
                  FROM {report_builder_schedule} rbs
                  JOIN {user} u ON u.id = rbs.userid
                 WHERE u.deleted = 1";
    $reports = $DB->get_records_sql($sql);
    // Delete all scheduled reports created by deleted user/s.
    foreach ($reports as $report) {
        $DB->delete_records('report_builder_schedule_email_audience',   array('scheduleid' => $report->id));
        $DB->delete_records('report_builder_schedule_email_systemuser', array('scheduleid' => $report->id));
        $DB->delete_records('report_builder_schedule_email_external',   array('scheduleid' => $report->id));
        $DB->delete_records('report_builder_schedule', array('id' => $report->id));
    }

    // Get deleted user/s.
    $sql = "SELECT DISTINCT rbses.userid
                  FROM {report_builder_schedule_email_systemuser} rbses
                  JOIN {user} u ON u.id = rbses.userid
                 WHERE u.deleted = 1";
    $reports = $DB->get_fieldset_sql($sql);
    if ($reports) {
        list($sqlin, $sqlparm) = $DB->get_in_or_equal($reports);
        // Remove deleted user/s from scheduled reports.
        $DB->execute("DELETE FROM {report_builder_schedule_email_systemuser} WHERE userid $sqlin", $sqlparm);
    }

    // Get deleted audience/s.
    $sql = "SELECT DISTINCT rbsea.cohortid
                  FROM {report_builder_schedule_email_audience} rbsea
                 WHERE NOT EXISTS (
                           SELECT 1 FROM {cohort} ch WHERE rbsea.cohortid = ch.id
               )";
    $cohorts = $DB->get_fieldset_sql($sql);
    if ($cohorts) {
        list($sqlin, $sqlparm) = $DB->get_in_or_equal($cohorts);
        // Remove deleted audience/s from scheduled reports.
        $DB->execute("DELETE FROM {report_builder_schedule_email_audience} WHERE cohortid $sqlin", $sqlparm);
    }

    return true;
}

/**
 * Populate the "usermodified" column introduced with the new scheduled report
 * report source implementation.
 */
function totara_reportbuilder_populate_scheduled_reports_usermodified() {
    global $DB;

    $table = 'report_builder_schedule';
    $records = $DB->get_records($table, null, '', 'id,userid,usermodified');
    foreach ($records as $record) {
        $record->usermodified = $record->userid;
        $DB->update_record($table, $record);
    }
}

function totara_reportbuilder_migrate_svggraph_settings() {
    global $DB;

    $records = $DB->get_records('report_builder_graph', null, '', 'id,settings');
    if (empty($records)) {
        return;
    }

    // Fetch the SVGGraph Settings object, and invert it
    $translation = totara_reportbuilder\local\graph\settings\svggraph::$translation;
    $translate_setting = function ($key, $value, $translation) use (&$translate_setting) {

        // Check all translation settings to see if we have a matched value -- this is inefficient,
        // but we only do it once
        foreach (array_keys($translation) as $k) {
            // Skip non-string keys, and default values
            if (!is_string($k) || $k === '_default') {
                continue;
            }

            $val = $translation[$k];
            if (is_array($val)) {
                $child_response = $translate_setting($key, $value, $val);
                if (is_array($child_response)) {
                    return [
                        $k => $child_response
                    ];
                }
            } else if ($translation[$k] === $key) {
                return [
                    $k => $value
                ];
            }
        }

        return false;
    };

    foreach ($records as $record) {
        if (empty($record->settings)) {
            continue;
        }

        // Try to parse the settings ini
        $oldsettings = @parse_ini_string($record->settings);
        if (!$oldsettings) {
            // The settings are invalid (or already JSON), so leave them alone
            continue;
        }

        $newsettings = [];
        foreach ($oldsettings as $key => $value) {
            $translated = $translate_setting($key, $value, $translation);
            if ($translated) {
                $newsettings = array_merge_recursive($newsettings, $translated);
            } else {
                // If there isn't any setting for this, put it in the custom settings so it isn't
                // lost
                $newsettings = array_merge_recursive($newsettings, [
                    'custom' => [
                        $key => $value
                    ]
                ]);
            }
        }

        $record->settings = json_encode($newsettings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $DB->update_record('report_builder_graph', $record);
    }
}

/**
 * Migrate all reports using the competency_evidence source to use the comp_status_history source
 */
function reportbuilder_migrate_competency_evidence_to_competency_status_perform() {
    global $CFG, $DB;

    require_once ($CFG->dirroot . '/totara/reportbuilder/lib.php');

    $report_ids = $DB->get_fieldset_sql("SELECT id FROM {report_builder} WHERE source = :oldsource",
        ['oldsource' => 'competency_evidence']
    );

    if (empty($report_ids)) {
        return;
    }

    [$id_sql, $id_params] = $DB->get_in_or_equal($report_ids, SQL_PARAMS_NAMED);

    // Columns and saved searches
    $update_sql =
        "SET type = :new_type,
                value = :new_value
            WHERE reportid {$id_sql}
              AND type = :old_type
              AND value = :old_value";

    $delete_wh =
        "WHERE reportid {$id_sql}
                 AND type = :old_type
                 AND value ";

    $to_update = [
        [
            'old_type' => 'competency_evidence',
            'old_value' => 'proficiency',
            'new_type' => 'competency_status',
            'new_value' => 'scale_value_name',
        ],
        [
            'old_type' => 'competency_evidence',
            'old_value' => 'proficiencyid',
            'new_type' => 'competency_status',
            'new_value' => 'scale_value_id',
        ],
        [
            'old_type' => 'competency_evidence',
            'old_value' => 'timemodified',
            'new_type' => 'competency',
            'new_value' => 'time_created',
        ],
    ];

    $to_delete = [
        'competency_evidence' => [
            'proficientdate',
            'organisationid',
            'organisationid2',
            'organisationpath',
            'organisation',
            'positionid',
            'positionid2',
            'positionpath',
            'position',
            'assessor',
            'assessorname',
        ],
        'competency' => [
            'competencylink',
            'id2',
            'statushistorylink',
            'shortname',
            'path',
        ],
    ];

    foreach ($to_update as $update_params) {
        $params = array_merge($id_params, $update_params);

        foreach (['report_builder_columns', 'report_builder_filters', 'report_builder_search_cols'] as $table) {
            $DB->execute("UPDATE {{$table}} " . $update_sql, $params);
        }

        totara_reportbuilder_migrate_saved_searches('competency_evidence',
            $update_params['old_type'], $update_params['old_value'], $update_params['new_type'], $update_params['new_value']);
    }

    foreach ($to_delete as $type => $values) {
        [$values_sql, $values_params] = $DB->get_in_or_equal($values, SQL_PARAMS_NAMED);
        $params = array_merge($id_params, ['old_type' => $type], $values_params);

        foreach (['report_builder_columns', 'report_builder_filters', 'report_builder_search_cols'] as $table) {
            $DB->execute("DELETE FROM {{$table}} " . $delete_wh . $values_sql, $params);
        }

        foreach ($values as $value) {
            totara_reportbuilder_migrate_saved_searches('competency_evidence',
                $type, $value, null, null);
        }
    }

    // No numeric columns - no graphs

    // Purge all caches
    foreach ($report_ids as $id) {
        reportbuilder_purge_cache($id);
    }

    // Source
    $DB->execute(
        "UPDATE {report_builder} 
                 SET source = :new_source 
               WHERE id {$id_sql}",
        array_merge($id_params, ['new_source' => 'competency_status'])
    );
}

/**
 * Replace old category with a new category column value in report_builder_graph table
 *
 * @param string $source value of 'source' column of the 'report_builder' table
 * @param string $oldtype current value of 'type' column of the 'report_builder_columns' table
 * @param string $oldvalue current value of 'value' column of the 'report_builder_columns' table
 * @param string $newtype new 'type' value to replace the current 'type' value
 * @param string $newvalue new 'value' value to replace the current 'value' value
 * @return bool
 */
function totara_reportbuilder_migrate_svggraph_category(string $source, string $oldtype, string $oldvalue, string $newtype, string $newvalue) {
    global $DB;
    if (empty($source) || empty($oldtype) || empty($oldvalue) || empty($newtype) || empty($newvalue)) {
        throw new coding_exception('all params must have a valid value');
    }
    $sourcesql = ' WHERE rb.source = :source AND rbg.category = :category';
    $params = array('source' => $source);
    $params['category'] = "{$oldtype}-{$oldvalue}";
    $sql = "
        SELECT rbg.*
          FROM {report_builder_graph} rbg
          JOIN {report_builder} rb ON rb.id = rbg.reportid
        {$sourcesql}
    ";
    $categories = $DB->get_records_sql($sql, $params);
    foreach ($categories as $category) {
        $todb = new \stdClass();
        $todb->id = $category->id;
        $todb->category = "{$newtype}-{$newvalue}";
        $DB->update_record('report_builder_graph', $todb);
    }

    return true;
}

/**
 * Replace old defaultsortcolumn with a new defaultsortcolumn column value in report_builder table
 *
 * @param string $source value of 'source' column of the 'report_builder' table
 * @param string $oldtype current value of 'type' column of the 'report_builder_columns' table
 * @param string $oldvalue current value of 'value' column of the 'report_builder_columns' table
 * @param string $newtype new 'type' value to replace the current 'type' value
 * @param string $newvalue new 'value' value to replace the current 'value' value
 * @return bool
 */
function totara_reportbuilder_migrate_default_sort_columns_by_source(string $source, string $oldtype, string $oldvalue, string $newtype, string $newvalue) {
    global $DB;
    if (empty($source) || empty($oldtype) || empty($oldvalue) || empty($newtype) || empty($newvalue)) {
        throw new coding_exception('all params must have a valid value');
    }
    $sql = "UPDATE {report_builder} SET defaultsortcolumn = :newsort WHERE source = :source AND defaultsortcolumn = :oldsort";
    $params = array('source' => $source);
    $params['newsort'] = "{$newtype}_{$newvalue}";
    $params['oldsort'] = "{$oldtype}_{$oldvalue}";
    $DB->execute($sql, $params);

    return true;
}

/**
 * Inject a filter into an existing report.
 *
 * @param string $filter_shortname
 * @param string $filter_type
 * @param string $filter_value
 * @param string $filter_name
 * @param array $filter_default_value
 * @param int $filter_advanced
 * @param int $filter_ingrequired
 * @param int $filter_customname
 * @param int $filter_region
 * @return bool
 */
function totara_reportbuilder_inject_filter_into_report(
    string $filter_shortname,
    string $filter_type,
    string $filter_value,
    string $filter_name = '',
    array $filter_default_value = [],
    int $filter_advanced = 0,
    int $filter_ingrequired = 0,
    int $filter_customname = 0,
    int $filter_region = rb_filter_type::RB_FILTER_REGION_STANDARD
) {
    global $DB;
    if (empty($filter_shortname) || empty($filter_type) || empty($filter_value)) {
        throw new coding_exception('all params must have a valid value');
    }

    $report_id = $DB->get_field('report_builder', 'id', ['shortname' => $filter_shortname]);
    if (!$report_id) {
        // Nothing to upgrade.
        return true;
    }

    // Check that the new filter is not added already.
    $record = $DB->get_record('report_builder_filters', [
        'reportid' => $report_id,
        'type' => trim($filter_type),
        'value' => trim($filter_value)
    ]);

    if (!$record) {
        $todb = new stdClass();
        $todb->reportid = $report_id;
        $todb->type = trim($filter_type);
        $todb->value = trim($filter_value);
        $todb->filtername = trim($filter_name) ?: '';
        $todb->advanced = $filter_advanced == 1 ? 1 : 0;
        $todb->customname = $filter_customname == 1 ? 1 : 0;
        $todb->filteringrequired = $filter_ingrequired == 1 ? 1 : 0;
        $todb->region =
            $filter_region == rb_filter_type::RB_FILTER_REGION_SIDEBAR
                ? rb_filter_type::RB_FILTER_REGION_SIDEBAR
                : rb_filter_type::RB_FILTER_REGION_STANDARD;
        $todb->defaultvalue = !empty($filter_default_value) ? serialize($filter_default_value) : null;
        $sortorder = $DB->get_field('report_builder_filters', 'MAX(sortorder) + 1', ['reportid' => $report_id, 'region' => $todb->region]);
        $todb->sortorder = $sortorder ?: 1;

        $DB->insert_record('report_builder_filters', $todb);
    }

    return true;
}

/**
 * Reset cache for reports with visibility checks.
 */
function totara_reportbuilder_reset_cache_for_reports_with_visibility_checks() {
    global $DB;

    $reports_no_cache_allowed = [
        'dp_program',
        'dp_program_recurring',
        'course_completion',
        'course_completion_all',
        'facetoface_events',
        'facetoface_signin',
        'facetoface_sessions',
        'facetoface_summary',
        'course_membership',
        'dp_certification',
        'perform_element',
        'perform_response',
        'perform_response_element',
        'perform_response_subject_instance'
    ];

    // Delete cache tables.
    list($sqlin, $params) = $DB->get_in_or_equal($reports_no_cache_allowed, SQL_PARAMS_NAMED);
    $report_ids = $DB->get_fieldset_sql("SELECT id FROM {report_builder} WHERE cache = 1 AND source {$sqlin}", $params);
    if (!empty($report_ids)) {
        // Purge all caches
        foreach ($report_ids as $id) {
            reportbuilder_purge_cache($id, true);
        }
    }

    return true;
}
