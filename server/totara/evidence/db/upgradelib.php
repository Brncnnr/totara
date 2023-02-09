<?php
/**
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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package totara_evidence
 */

use totara_evidence\models\evidence_type as evidence_type_model;

/**
 * Create the system evidence types needed for the completion history import tool
 */
function totara_evidence_create_completion_types() {
    global $DB;

    $now = time();
    $admin = get_admin()->id;

    // Templates
    $type = [
        'descriptionformat' => FORMAT_HTML,
        'created_at' => $now,
        'modified_at' => $now,
        'created_by' => $admin,
        'modified_by' => $admin,
        'location' => 1, // Equal to \totara_evidence\models\evidence_type::LOCATION_RECORD_OF_LEARNING
        'status' => 0, // Equal to \totara_evidence\models\evidence_type::STATUS_HIDDEN
    ];
    $text_field = [
        'datatype' => 'text',
        'hidden' => 0,
        'locked' => 0,
        'required' => 0,
        'forceunique' => 0,
        'defaultdata' => '',
        'param1' => 30,
        'param2' => 2048,
    ];
    $date_field = [
        'datatype' => 'datetime',
        'hidden' => 0,
        'locked' => 0,
        'required' => 0,
        'forceunique' => 0,
        'defaultdata' => 0,
        'param1' => 1919,
        'param2' => 2038,
    ];

    $transaction = $DB->start_delegated_transaction();

    // Course type and its fields
    // Note: The shortnames must match get_columnnames() in totara/completionimport/lib.php
    if (!$DB->record_exists('totara_evidence_type', ['idnumber' => 'coursecompletionimport'])) {
        $course_type = $DB->insert_record('totara_evidence_type', array_merge($type, [
            'name' => 'multilang:completion_course',
            'idnumber' => 'coursecompletionimport',
            'description' => 'multilang:completion_course',
        ]));
        $DB->insert_record('totara_evidence_type_info_field', array_merge($text_field, [
            'typeid' => $course_type,
            'fullname' => 'multilang:course_shortname',
            'shortname' => 'courseshortname',
            'sortorder' => 1,
            'param1' => 20,
        ]));
        $DB->insert_record('totara_evidence_type_info_field', array_merge($text_field, [
            'typeid' => $course_type,
            'fullname' => 'multilang:course_idnumber',
            'shortname' => 'courseidnumber',
            'sortorder' => 2,
            'param1' => 10,
            'param2' => 100,
        ]));
        $DB->insert_record('totara_evidence_type_info_field', array_merge($date_field, [
            'typeid' => $course_type,
            'fullname' => 'multilang:completion_date',
            'shortname' => 'completiondate',
            'sortorder' => 3,
        ]));
        $DB->insert_record('totara_evidence_type_info_field', array_merge($text_field, [
            'typeid' => $course_type,
            'fullname' => 'multilang:grade',
            'shortname' => 'grade',
            'sortorder' => 4,
            'param1' => 5,
        ]));
        $DB->insert_record('totara_evidence_type_info_field', array_merge($text_field, [
            'typeid' => $course_type,
            'fullname' => 'multilang:import_id',
            'shortname' => 'importid',
            'sortorder' => 5,
            'param1' => 10,
        ]));
    }

    // Certification type and its fields
    // Note: The shortnames must match get_columnnames() in totara/completionimport/lib.php
    if (!$DB->record_exists('totara_evidence_type', ['idnumber' => 'certificationcompletionimport'])) {
        $certification_type = $DB->insert_record('totara_evidence_type', array_merge($type, [
            'name' => 'multilang:completion_certification',
            'idnumber' => 'certificationcompletionimport',
            'description' => 'multilang:completion_certification',
        ]));
        $DB->insert_record('totara_evidence_type_info_field', array_merge($text_field, [
            'typeid' => $certification_type,
            'fullname' => 'multilang:certification_shortname',
            'shortname' => 'certificationshortname',
            'sortorder' => 1,
            'param1' => 20,
        ]));
        $DB->insert_record('totara_evidence_type_info_field', array_merge($text_field, [
            'typeid' => $certification_type,
            'fullname' => 'multilang:certification_idnumber',
            'shortname' => 'certificationidnumber',
            'sortorder' => 2,
            'param1' => 10,
            'param2' => 100,
        ]));
        $DB->insert_record('totara_evidence_type_info_field', array_merge($date_field, [
            'typeid' => $certification_type,
            'fullname' => 'multilang:completion_date',
            'shortname' => 'completiondate',
            'sortorder' => 3,
        ]));
        $DB->insert_record('totara_evidence_type_info_field', array_merge($date_field, [
            'typeid' => $certification_type,
            'fullname' => 'multilang:due_date',
            'shortname' => 'duedate',
            'sortorder' => 4,
        ]));
        $DB->insert_record('totara_evidence_type_info_field', array_merge($text_field, [
            'typeid' => $certification_type,
            'fullname' => 'multilang:import_id',
            'shortname' => 'importid',
            'sortorder' => 5,
            'param1' => 10,
        ]));
    }

    $transaction->allow_commit();
    return true;
}

/**
 * Migrate report column or filter types and values.
 * We only migrate one old value to a new value and not any extra to avoid duplicate column errors.
 *
 * @param string $table
 * @param string $source
 * @param string $old_type
 * @param string $new_type
 * @param string[] $old_values
 * @param string $new_value
 */
function totara_evidence_migrate_remap_report_values(
    string $table,
    string $source,
    string $old_type,
    string $new_type,
    array $old_values,
    string $new_value
) {
    global $DB;

    if (empty($old_values)) {
        return;
    }

    $reports = $DB->get_recordset('report_builder', ['source' => $source]);

    [$sql_in, $params_in] = $DB->get_in_or_equal($old_values, SQL_PARAMS_NAMED);

    foreach ($reports as $report) {
        $sql = "
            UPDATE {{$table}} 
            SET type = :new_type, value = :new_value
            WHERE reportid = :report_id AND type = :old_type AND value {$sql_in} 
        ";

        $params = [
            'report_id' => $report->id,
            'old_type' => $old_type,
            'new_type' => $new_type,
            'new_value' => $new_value
        ];

        $params = array_merge($params, $params_in);

        $DB->execute($sql, $params);
    }

    $reports->close();
}

/**
 * Migrate saved search data that was used in evidence reports
 *
 * @param string $source
 * @param string $old_type
 * @param string $new_type
 * @param string[] $old_values
 * @param string $new_value
 */
function totara_evidence_migrate_remap_report_saved_searches($source, $old_type, $new_type, $old_values, $new_value) {
    global $DB;

    $saved_searches = $DB->get_recordset_sql("
        SELECT rbs.* FROM {report_builder_saved} rbs
        JOIN {report_builder} rb
        ON rb.id = rbs.reportid
        WHERE rb.source = :source
    ", ['source' => $source]);

    $delete_saved_search_ids = [];
    foreach ($saved_searches as $saved) {
        if (empty($saved->search)) {
            $delete_saved_search_ids[] = $saved->id;
            continue;
        }

        $search = unserialize($saved->search, ['allowed_classes' => false]);

        if (!is_array($search)) {
            $delete_saved_search_ids[] = $saved->id;
            continue;
        }

        // Check for any filters that will need to be updated.
        $update = false;
        foreach ($search as $old_key => $info) {
            [$type, $value] = explode('-', $old_key);

            // There is no longer the ability to filter custom fields so remove the search
            if ($type === 'dp_plan_evidence') {
                unset($search[$old_key]);
                continue;
            }

            foreach ($old_values as $old_value) {
                if ($type === $old_type && $value === $old_value) {
                    $update = true;

                    $new_key = "{$new_type}-{$new_value}";
                    $search[$new_key] = $info;
                    unset($search[$old_key]);
                }
            }
        }

        if ($update) {
            $DB->update_record('report_builder_saved', [
                'id' => $saved->id,
                'search' => serialize($search),
            ]);
        }
    }

    if (!empty($delete_saved_search_ids)) {
        $DB->delete_records_list('report_builder_saved', 'id', $delete_saved_search_ids);
    }

    $saved_searches->close();
}

/**
 * Migrate old reports to use new evidence report source columns and filters
 */
function totara_evidence_migrate_reports() {
    global $DB;

    // What columns and filters to migrate from and to
    $old_source = 'dp_evidence';
    $new_source = 'evidence_item';
    $value_mappings = [
        [
            'old_type' => 'evidence',
            'new_type' => 'base',
            'values' => [
                'name' => [
                    'name',
                    'namelink',
                    'viewevidencelink',
                ],
                'created_at' => [
                    'timecreated',
                ],
                'modified_at' => [
                    'timemodified',
                ],
                'in_use' => [
                    'evidenceinuse',
                ],
                'actions' => [
                    'actionlinks',
                ],
            ]
        ],
        [
            'old_type' => 'evidence',
            'new_type' => 'type',
            'values' => [
                'name' => [
                    'evidencetypename',
                    'evidencetypeid',
                ],
            ]
        ],
    ];

    $transaction = $DB->start_delegated_transaction();

    // Remap the values for columns, filters and saved searches
    foreach ($value_mappings as $mapping) {
        foreach ($mapping['values'] as $new_value => $old_values) {
            totara_evidence_migrate_remap_report_values(
                'report_builder_columns', $old_source, $mapping['old_type'], $mapping['new_type'], $old_values, $new_value
            );
            totara_evidence_migrate_remap_report_values(
                'report_builder_filters', $old_source, $mapping['old_type'], $mapping['new_type'], $old_values, $new_value
            );
            totara_evidence_migrate_remap_report_saved_searches(
                $old_source, $mapping['old_type'], $mapping['new_type'], $old_values, $new_value
            );
        }
    }

    // Change the record of learning embedded report to use new source and shortname
    $DB->set_fields(
        'report_builder',
        ['source' => $new_source, 'shortname' => 'evidence_record_of_learning'],
        ['source' => $old_source, 'shortname' => 'plan_evidence', 'embedded' => 1]
    );

    // Change the reports to use the new sources
    $DB->set_field('report_builder', 'source', $new_source, ['source' => $old_source]);

    $transaction->allow_commit();
}

/**
 * Migrate a set of files to their new item ids
 *
 * @param string $component component
 * @param string $filearea file area
 * @param int|false $itemid item ID or all files if not specified
 * @param array $new_record_data
 * @param bool $delete_old Delete the original file too? Defaults to true
 */
function totara_evidence_migrate_files($component, $filearea, $itemid, $new_record_data, $delete_old = true) {
    global $DB;
    $fs = get_file_storage();

    $params = [
        'contextid' => context_system::instance()->id,
        'component' => $component,
        'filearea' => $filearea,
        'filename_exclude' => '.',
    ];
    $itemid_sql = '';
    if ($itemid) {
        $params['itemid'] = $itemid;
        $itemid_sql = 'AND itemid = :itemid';
    }
    $file_records = $DB->get_recordset_select(
        'files',
        "contextid = :contextid AND component = :component AND filearea = :filearea $itemid_sql AND filename <> :filename_exclude",
        $params
    );

    foreach ($file_records as $file_record) {
        $file = $fs->get_file_instance($file_record);
        $new_file_record = array_merge((array) $file_record, $new_record_data);

        $fs->create_file_from_storedfile($new_file_record, $file);
        if ($delete_old) {
            $file->delete();
        }
    }

    $file_records->close();
}

/**
 * Return legacy evidence types and virtual (no-type) if any items of this type exist in the system
 *
 * @param int $current_time
 * @param int $admin_userid
 * @return array
 */
function totara_evidence_get_legacy_evidence_types(int $current_time, $admin_userid) {
    global $DB;

    // List evidence types
    $types = $DB->get_records('dp_evidence_type', null, 'sortorder');

    // Let's check whether there are any records with no type specified.
    if (!$types || $DB->count_records('dp_plan_evidence', ['evidencetypeid' => 0])) {
        array_unshift($types, (object) [
            'id' => 0,
            'name' => 'multilang:unspecified',
            'description' => 'multilang:unspecified',
            'descriptionformat' => FORMAT_HTML,
            'usermodified' => $admin_userid,
            'timemodified' => $current_time,
        ]);
    }

    return $types;
}

/**
 * Create a dropdown select field that stores the old types that used to exist
 *
 * @param int $type_id
 * @return int The new field ID
 */
function totara_evidence_migrate_type_name_field(int $type_id) {
    global $DB;

    // Create an extra field that stores the name of the old type that the evidence used to be
    $old_type_names_param = '';
    $i = 0;
    $types = $DB->get_recordset('dp_evidence_type', null, 'sortorder');
    foreach ($types as $type) {
        if ($i > 0) {
            $old_type_names_param .= "\n"; // Deliberately don't use PHP_EOL
        }
        $i++;
        $old_type_names_param .= $type->name;
    }
    $types->close();

    // There could potentially already be a custom field using the shortname we want to use, if so then append a number to the end
    $shortname = 'oldtypename';
    $new_shortname = $shortname;
    $shortname_count = 1;
    while ($DB->record_exists('totara_evidence_type_info_field', ['shortname' => $new_shortname])) {
        $new_shortname = $shortname . $shortname_count;
        $shortname_count++;
    }

    // Create the field
    return $DB->insert_record('totara_evidence_type_info_field', [
        'typeid' => $type_id,
        'fullname' => 'multilang:old_type',
        'shortname' => $new_shortname,
        'datatype' => 'menu',
        'sortorder' => 0,
        'hidden' => 0,
        'locked' => 0,
        'required' => 0,
        'forceunique' => 0,
        'defaultdata' => '',
        'param1' => $old_type_names_param,
    ]);
}

/**
 * Migrate custom fields to a new evidence type
 *
 * @param int $new_type_id New type ID
 * @param moodle_recordset|null $fields Custom field definitions associated to the legacy evidence plugins, to save on DB calls
 * @return int[] A map of old IDs to new IDs
 */
function totara_evidence_migrate_type_fields(int $new_type_id, moodle_recordset $fields = null) {
    global $DB;

    // Keeping a map between old and new IDs for custom fields
    $field_ids = [];

    // Populate custom fields for one type. Essentially, we are just cloning an existing set of custom fields
    // over to each new type.
    $fields = $fields ?? $DB->get_recordset('dp_plan_evidence_info_field');
    foreach ($fields as $old_field) {
        // Insert an old custom field into a new table
        $new_field = clone $old_field;
        $new_field->typeid = $new_type_id;
        unset($new_field->id);
        $field_ids[$old_field->id] = $new_field;
        $new_field->id = $DB->insert_record('totara_evidence_type_info_field', $new_field);

        // If it's a textarea and it has default images / files
        if ($new_field->datatype === 'textarea' && strpos($new_field->defaultdata, '@@PLUGINFILE@@') !== false) {
            // Copy the textarea files with the new item id.
            // Keep the existing one as unfortunately this is a shared component / textare combination
            totara_evidence_migrate_files(
                'totara_customfield',
                'old_evidence_textarea',
                $old_field->id,
                ['itemid' => $new_field->id, 'filearea' => 'textarea'],
                false
            );
        }
    }

    $fields->close();

    return $field_ids;
}

/**
 * Generate a unique name by appending and increasing (n) to the filename until it is unique
 *
 * @param file_storage $fs
 * @param object $file_record
 * @return string
 */
function totara_evidence_migrate_get_unique_name_for_record(file_storage $fs, object $file_record) {
    $filename = pathinfo($file_record->filename);
    $i = 1;

    while ($fs->file_exists(
        $file_record->contextid,
        $file_record->component,
        $file_record->filearea,
        $file_record->itemid,
        $file_record->filepath,
        $file_record->filename
    )) {
        $file_record->filename = "{$filename['filename']} ($i).{$filename['extension']}";
        $i++;
    }

    return $file_record->filename;
}

/**
 * Migrate evidence item text area images whilst preventing duplicates.
 *
 * Previously (t9) with old evidence, images that were in the default value for a text area were not prefixed
 * with "@@PLUGINFILE@@" when they were saved, and instead just saved the full URL to the text area file.
 *
 * @param int $old_field_id The ID of the old text area field
 * @param $new_field_id
 * @param int $new_data_id The ID of the new text area field data instance
 * @param string $textarea_content The actual content of the textarea
 */
function totara_evidence_migrate_item_textarea_files($old_field_id, $new_field_id, $new_data_id, $textarea_content) {
    global $DB;
    $context = context_system::instance()->id;
    $fs = get_file_storage();
    $pluginfile = 'pluginfile.php';
    $component = 'totara_customfield';
    $old_filearea = 'textarea';
    $new_filearea = 'evidence';
    $has_changed = false;

    $file_records = $DB->get_recordset_select(
        'files',
        "contextid = :contextid AND component = :component AND filearea = :filearea AND itemid = :itemid AND filename <> '.'",
        [
            'contextid' => $context,
            'component' => $component,
            'filearea' => $old_filearea,
            'itemid' => $new_field_id,
        ]
    );
    foreach ($file_records as $file_record) {
        $old_filename = $file_record->filename;
        $old_filename_encoded = rawurlencode($old_filename);
        $broken_textarea_url = "$pluginfile/$context/$component/$old_filearea/$old_field_id/$old_filename_encoded";

        if (strpos($textarea_content, $broken_textarea_url) !== false) {
            $file = $fs->get_file_instance($file_record);


            $new_record_data = [
                'filearea' => $new_filearea,
                'itemid' => $new_data_id,
                'filename' => totara_evidence_migrate_get_unique_name_for_record($fs, $file_record)
            ];

            $new_file_record = array_merge((array) $file_record, $new_record_data);

            $fs->create_file_from_storedfile($new_file_record, $file);

            // Change the file URL to use the new filename.
            // We need to encode spaces and other entities in the filename as that is how they are saved in the database.
            $new_filename_encoded = rawurlencode($new_file_record['filename']);

            $textarea_content = str_replace(
                "$pluginfile/$context/$component/$old_filearea/$old_field_id/$old_filename_encoded",
                "$pluginfile/$context/$component/$new_filearea/$new_data_id/$new_filename_encoded",
                $textarea_content
            );

            $has_changed = true;
        }
    }

    if ($has_changed) {
        // Remove the full URL from the images, making it use @@PLUGINFILE@@ instead
        $textarea_content = file_rewrite_pluginfile_urls(
            $textarea_content, $pluginfile, $context, $component, $new_filearea, $new_data_id, ['reverse' => true]
        );

        $DB->set_field('totara_evidence_type_info_data', 'data', $textarea_content, ['id' => $new_data_id]);
    }
}

/**
 * Migrate a single evidence item record with custom fields
 *
 * @param object $item Evidence item database record
 * @param int[] $fields Custom fields id mapping [ $oldId => $newField ]
 * @param int A new type id to use
 * @param int $current_time
 * @param int $admin_userid
 * @return int The new item ID
 */
function totara_evidence_migrate_item(object $item, array $fields, int $type_id, int $current_time, int $admin_userid) {
    global $DB;

    // Let's build a new record and insert it in the database.
    // The record may have creator or dates created\modified not set, new bank doesn't allow nullable for these fields
    $new_record = [
        'typeid' => $type_id,
        'user_id' => $item->userid,
        'name' => $item->name,
        'status' => 1, // Equal to \totara_evidence\models\evidence_item::STATUS_ACTIVE
        'created_by' => $item->usermodified ?: $admin_userid,
        'modified_by' => $item->usermodified ?: $admin_userid,
        'created_at' => $item->timecreated ?: $current_time,
        'modified_at' => $item->timemodified ?: $current_time,
        'imported' => $item->readonly
    ];

    $new_item_id = $DB->insert_record('totara_evidence_item', $new_record);

    // Get all custom fields for a given evidence.
    $data_records = $DB->get_recordset('dp_plan_evidence_info_data', ['evidenceid' => $item->id]);
    foreach ($data_records as $data_record) {
        // Now let's insert it to a new table
        $new_record = [
            'fieldid' => $fields[$data_record->fieldid]->id,
            'evidenceid' => $new_item_id,
            'data' => $data_record->data,
        ];

        $data_id = $DB->insert_record('totara_evidence_type_info_data', $new_record);

        // Let's check for param records
        $sql = "
            INSERT INTO {totara_evidence_type_info_data_param} 
                (dataid, value)
            SELECT '{$data_id}', value FROM {dp_plan_evidence_info_data_param} WHERE dataid = :dataid
        ";
        $DB->execute($sql, ['dataid' => $data_record->id]);

        // Migrate files in text area and file manager fields
        if ($fields[$data_record->fieldid]->datatype === 'textarea') {
            totara_evidence_migrate_files('totara_customfield', 'old_evidence', $data_record->id, [
                'itemid' => $data_id,
                'filearea' => 'evidence',
            ], true);
            totara_evidence_migrate_item_textarea_files($data_record->fieldid, $fields[$data_record->fieldid]->id, $data_id, $data_record->data);
        } else if ($fields[$data_record->fieldid]->datatype === 'file') {
            totara_evidence_migrate_files('totara_customfield', 'old_evidence_filemgr', $data_record->id, [
                'itemid' => $data_id,
                'filearea' => 'evidence_filemgr',
            ], true);

            // We also need to remap the data field to the new migrated data ID
            $DB->set_field('totara_evidence_type_info_data', 'data', $data_id, ['id' => $data_id]);
        }
    }
    $data_records->close();

    // Change the evidence relations to use the new evidence
    $sql = "
        UPDATE {dp_plan_evidence_relation} 
        SET evidenceid = :newitemid
        WHERE evidenceid = :evidenceid
    ";
    $DB->execute($sql, ['newitemid' => $new_item_id, 'evidenceid' => -1 * $item->id]);

    // Remove the old evidence records
    $sql = "
        DELETE FROM {dp_plan_evidence_info_data_param}
        WHERE dataid IN (SELECT id FROM {dp_plan_evidence_info_data} WHERE evidenceid = :evidenceid)
    ";
    $DB->execute($sql, ['evidenceid' => $item->id]);
    $DB->delete_records('dp_plan_evidence_info_data', ['evidenceid' => $item->id]);
    $DB->delete_records('dp_plan_evidence', ['id' => $item->id]);

    return $new_item_id;
}

/**
 * Migrate evidence types and items
 *
 * @param int $current_time
 * @param int $admin_userid
 * @param $progress_bar
 * @param int $batch_limit defaults to 10000
 */
function totara_evidence_migrate_evidence_types_and_items($current_time, $admin_userid, $progress_bar, int $batch_limit = 10000) {
    global $DB;

    $types = totara_evidence_get_legacy_evidence_types($current_time, $admin_userid);

    foreach ($types as $old_type) {
        $DB->transaction(static function () use ($DB, $current_time, $admin_userid, $progress_bar, $old_type, $batch_limit) {
            // We need to create a type from a given record
            // Appending the legacy id to the name for a unique idnumber

            // TODO: Can remove once TL-34228 lands
            $used_for_import = $DB->count_records('dp_plan_evidence', ['evidencetypeid' => $old_type->id, 'readonly' => 1]);

            $manual_type_record = [
                'name' => $old_type->name,
                'idnumber' => $old_type->name . '-' . $old_type->id,
                'description' => $old_type->description,
                'descriptionformat' => FORMAT_HTML,
                'location' => $used_for_import ? 1 : 0, // \totara_evidence\models\evidence_type::LOCATION_RECORD_OF_LEARNING for import, else LOCATION_EVIDENCE_BANK
                'status' => 1, // Equal to \totara_evidence\models\evidence_type::STATUS_ACTIVE
                'created_by' => $old_type->usermodified ?: $admin_userid,
                'modified_by' => $old_type->usermodified ?: $admin_userid,
                'created_at' => $old_type->timemodified ?: $current_time,
                'modified_at' => $old_type->timemodified ?: $current_time,
            ];

            // New type
            $new_type_id = $DB->insert_record('totara_evidence_type', $manual_type_record);
            $type_field_ids = totara_evidence_migrate_type_fields($new_type_id);

            // Migrate any images in the type's description
            if ($old_type->id) {
                totara_evidence_migrate_files('totara_plan', 'dp_evidence_type', $old_type->id, [
                    'itemid' => $new_type_id,
                    'component' => 'totara_evidence',
                    'filearea' => 'type_description', // Equal to \totara_evidence\models\evidence_type::DESCRIPTION_FILEAREA
                ]);
            }

            // Let's migrate all the records of a given type
            $offset = 0;
            $limit = $batch_limit;
            $has_items = true;
            while ($has_items) {
                $items = $DB->get_recordset('dp_plan_evidence', ['evidencetypeid' => $old_type->id], 'id', '*', $offset, $limit);
                $has_items = $items->valid();

                foreach ($items as $item) {
                    totara_evidence_migrate_item($item, $type_field_ids, $new_type_id, $current_time, $admin_userid);

                    $progress_bar->increment();
                }
                $items->close();
            }

            if ($old_type->id > 0) {
                $DB->delete_records('dp_evidence_type', ['id' => $old_type->id]);
            }
        });
    }
}

/**
 * We need to shift all the files we want to migrate to a temporary file area first in order
 * to avoid duplicate file item IDs which will prevent us from copying the files.
 */
function totara_evidence_migrate_move_files_to_temp_area() {
    global $DB;
    // Need to override the pathnamehash in order to avoid errors due to duplicates.
    $hash_concat = $DB->sql_concat("'evidence_file_'", 'id');

    // Migrate file upload field responses (aka set by the user)
    $DB->execute("
        UPDATE {files}
        SET filearea = 'old_evidence_filemgr', pathnamehash = {$hash_concat}
        WHERE component = 'totara_customfield' AND filearea = 'evidence_filemgr'
        AND EXISTS (
            SELECT data.id, field.datatype
            FROM {dp_plan_evidence_info_data} data
            JOIN {dp_plan_evidence_info_field} field ON data.fieldid = field.id
            WHERE field.datatype = 'file'
            AND data.id = itemid
        )
    ");

    // Migrate textarea field responses (aka set by the user)
    $DB->execute("
        UPDATE {files}
        SET filearea = 'old_evidence', pathnamehash = {$hash_concat}
        WHERE component = 'totara_customfield' AND filearea = 'evidence'
        AND EXISTS (
            SELECT data.id, field.datatype
            FROM {dp_plan_evidence_info_data} data
            JOIN {dp_plan_evidence_info_field} field ON data.fieldid = field.id
            WHERE field.datatype = 'textarea'
            AND data.id = itemid
        )
    ");

    // Migrate textarea field default data (aka set by the admin)
    $DB->execute("
        UPDATE {files}
        SET filearea = 'old_evidence_textarea', pathnamehash = {$hash_concat}
        WHERE component = 'totara_customfield' AND filearea = 'textarea'
        AND EXISTS (
            SELECT field.id, field.datatype, field.defaultdata
            FROM {dp_plan_evidence_info_field} field
            WHERE field.datatype = 'textarea'
            AND field.defaultdata LIKE '%@@PLUGINFILE@@%'
            AND field.id = itemid
        )
    ");
}

/**
 * Once we are done copying everything over, we need to clean up the temporary file area we created
 *
 * @param int $batch_limit defaults to 10000
 */
function totara_evidence_migrate_remove_temporary_files(int $batch_limit = 10000) {
    global $DB;
    $context = context_system::instance()->id;
    $fs = get_file_storage();

    $offset = 0;
    $limit = $batch_limit;
    $has_files = true;
    while ($has_files) {
        $files = $DB->get_recordset_select(
            'files',
            "contextid = {$context} AND (
                (component = 'totara_customfield' AND filearea IN ('old_evidence', 'old_evidence_filemgr', 'old_evidence_textarea'))
                OR (component = 'totara_plan' AND filearea = 'dp_evidence_type')
            )",
            null, 'id', '*', $offset, $limit
        );
        $has_files = $files->valid();

        foreach ($files as $file) {
            $fs->get_file_instance($file)->delete();
        }
        $files->close();
    }

    $DB->delete_records('dp_plan_evidence_info_field');
}

/**
 * Migrate old evidence to the new evidence tables
 *
 * @param int $batch_limit defaults to 10000
 * @return bool
 */
function totara_evidence_migrate(int $batch_limit = 10000) {
    global $DB;

    $time = time();
    $admin_userid = get_admin()->id;

    // Progress bar required to prevent browser timeout if there is a large amount of records to migrate
    $progress_bar = new class () {
        protected $current_count;
        protected $total_count;
        protected $progress_bar;

        public function __construct() {
            global $DB;
            $this->current_count = 0;
            $this->total_count = $DB->count_records('dp_plan_evidence');
        }

        private function initialise(): void {
            if (!isset($this->progress_bar)) {
                $this->progress_bar = new progress_bar('totara_evidence_upgrade', 500, true);
            }
        }

        public function show_starting(): void {
            if ($this->total_count > 0) {
                $this->initialise();
                $this->progress_bar->update(0, $this->total_count, get_string('upgrading_evidence_starting', 'totara_evidence'));
            }
        }

        public function increment(): void {
            $this->initialise();

            $this->current_count++;
            $this->progress_bar->update($this->current_count, $this->total_count, get_string(
                'upgrading_evidence_progress',
                'totara_evidence',
                ['current' => $this->current_count, 'total' => $this->total_count]
            ));
        }

        public function show_finishing(): void {
            if ($this->total_count > 0) {
                $this->progress_bar->update($this->total_count, $this->total_count, get_string(
                    'upgrading_evidence_finishing', 'totara_evidence', $this->total_count
                ));
            }
        }

        public function show_finished(): void {
            if ($this->total_count > 0) {
                $this->progress_bar->update($this->total_count, $this->total_count, get_string(
                    'upgrading_evidence_finished', 'totara_evidence', $this->total_count
                ));
            }
        }
    };

    $progress_bar->show_starting();

    // First update all linked evidence items' ids to negative ids if not done previously
    $has_negative_ids = $DB->count_records_select('dp_plan_evidence_relation', 'evidenceid < 0');
    if ($has_negative_ids == 0) {
        $sql =
            "UPDATE {dp_plan_evidence_relation}
                SET evidenceid = evidenceid * -1";
        $DB->execute($sql);
    }

    totara_evidence_migrate_move_files_to_temp_area();

    totara_evidence_migrate_evidence_types_and_items($time, $admin_userid, $progress_bar, $batch_limit);

    $progress_bar->show_finishing();

    totara_evidence_migrate_remove_temporary_files($batch_limit);

    totara_evidence_migrate_reports();

    $progress_bar->show_finished();

    return true;
}



/**
 * Map legacy import type names to the actual evidence type with the same name.
 * Exclude legacy import types that no longer exist.
 * Where an old name maps to more than 1 evidence type, we use the one created first as that is probably the one created during migration
 *
 * @return array
 */
function totara_evidence_get_legacy_import_type_map(): array {
    global $DB;

    $type_map_sql =
        "SELECT etd.data as name, MIN(newt.id) new_type_id
            FROM {totara_evidence_type_info_field} etf
            JOIN {totara_evidence_type_info_data} etd
              ON etd.fieldid = etf.id
            JOIN {totara_evidence_type} newt
              ON newt.name = etd.data
           WHERE etf.fullname = :cf_oldtype
           GROUP BY etd.data";
    $params = ['cf_oldtype' => 'multilang:old_type'];
    return $DB->get_records_sql($type_map_sql, $params);
}

/**
 * Return sql statement to select all evidence items belonging to the legacy evidence type
 *
 * @param int $type_name_field_id
 * @param string $type_name
 * @return array
 */
function totara_evidence_item_sql(int $type_name_field_id, string $type_name): array {
    global $DB;

    if (empty($type_name)) {
        $sql =
            "SELECT ev_item.id AS itemid, ev_item.typeid
               FROM {totara_evidence_item} ev_item
               JOIN {totara_evidence_type} ev_type
                 ON ev_type.id = ev_item.typeid
              WHERE ev_type.idnumber = :legacy_type";
        $params = ['legacy_type' => 'legacycompletionimport'];

        return [$sql, $params];
    }

    // Using like for the table name where condition as MSSQL doesn't allow = on text field
    $tablename_like_sql = $DB->sql_like('type_data.data', ':type_name');

    $sql =
        "SELECT ev_item.id AS itemid, ev_item.typeid
           FROM {totara_evidence_item} ev_item
           JOIN {totara_evidence_type_info_data} type_data
             ON type_data.fieldid = :type_field_id
            AND type_data.evidenceid = ev_item.id
          WHERE {$tablename_like_sql}";
    $params = [
        'type_field_id' => $type_name_field_id,
        'type_name' => $type_name,
    ];

    return [$sql, $params];
}

/**
 * Move the evidence items' custom fields from the system type to the correct evidence type's fields
 *
 * @param int $legacy_table_name_fieldid
 * @param string $type_name
 * @param int $old_type_id
 * @param int $new_type_id
 * @return bool
 */
function totara_evidence_move_fields(int $type_name_field_id, string $type_name, int $old_type_id, int $new_type_id): bool {
    global $DB;

    [$items_sql, $item_params] = totara_evidence_item_sql($type_name_field_id, $type_name);

    switch ($DB->get_dbfamily()) {
        case 'mysql':
            $sql =
                "UPDATE {totara_evidence_type_info_data} update_target
                   JOIN (
                     SELECT tei.itemid , old_etf.id AS old_field_id, new_etf.id AS new_field_id
                     FROM ({$items_sql}) tei
                     JOIN {totara_evidence_type_info_field} old_etf
                       ON old_etf.typeid = tei.typeid
                     JOIN {totara_evidence_type_info_data} old_etd
                       ON old_etd.fieldid = old_etf.id
                      AND old_etd.evidenceid = tei.itemid
                     JOIN {totara_evidence_type_info_field} new_etf
                       ON new_etf.shortname = old_etf.shortname
                     WHERE old_etf.typeid = :old_type_id
                       AND new_etf.typeid = :new_type_id
                     ) AS update_source
                   ON update_target.fieldid = update_source.old_field_id
                     AND update_target.evidenceid = update_source.itemid
                  SET update_target.fieldid = update_source.new_field_id";
            break;

        case 'mssql':
        case 'postgres':
        default:
            $sql =
                "UPDATE {totara_evidence_type_info_data}
                    SET fieldid = update_source.new_field_id
                   FROM (
                       SELECT tei.itemid, old_etf.id AS old_field_id, new_etf.id AS new_field_id
                        FROM ({$items_sql}) tei
                         JOIN {totara_evidence_type_info_field} old_etf    
                           ON old_etf.typeid = tei.typeid
                         JOIN {totara_evidence_type_info_data} old_etd
                           ON old_etd.fieldid = old_etf.id
                          AND old_etd.evidenceid = tei.itemid
                         JOIN {totara_evidence_type_info_field} new_etf
                           ON new_etf.shortname = old_etf.shortname
                        WHERE old_etf.typeid = :old_type_id
                         AND new_etf.typeid = :new_type_id
                     ) AS update_source
                   WHERE fieldid = update_source.old_field_id
                     AND evidenceid = update_source.itemid";
            break;
    }

    $params = array_merge($item_params, [
        'old_type_id' => $old_type_id,
        'new_type_id' => $new_type_id,
    ]);

    // We can't delete the old type name field value yet - we need it when updating the items

    return $DB->execute($sql, $params);
}

/**
 * Move the evidence items to their original import type
 *
 * @param int $legacy_table_name_fieldid
 * @param string $type_name
 * @param int $new_type_id
 * @return bool
 */
function totara_evidence_move_items(int $type_name_field_id, string $type_name, int $new_type_id): bool {
    global $DB;

    [$items_sql, $item_params] = totara_evidence_item_sql($type_name_field_id, $type_name);

    switch ($DB->get_dbfamily()) {
        case 'mysql':
            $sql =
                "UPDATE {totara_evidence_item} update_target
                   JOIN ({$items_sql}) tei
                     ON update_target.id = tei.itemid
                  SET update_target.typeid = :new_type_id,
                      update_target.imported = 1";
            break;

        case 'mssql':
        case 'postgres':
        default:
            $sql =
                "UPDATE {totara_evidence_item}
                    SET typeid = :new_type_id,
                        imported = 1
                   FROM ({$items_sql}) tei
                  WHERE id = tei.itemid";
            break;
    }

    $params = array_merge($item_params, ['new_type_id' => $new_type_id,]);

    return $DB->execute($sql, $params);
}

/**
 * Delete the all oldtypename field data with the specified type name
 *
 * @param int $type_name_field_id
 * @param string $type_name
 * @return bool
 */
function totara_evidence_delete_old_type_name(int $type_name_field_id, string $type_name): bool {
    global $DB;

    // Delete the old type name field value
    $tablename_like_sql = $DB->sql_like('data', ':type_name');
    $sql =
        "DELETE FROM {totara_evidence_type_info_data}
          WHERE fieldid = :type_field_id
            AND {$tablename_like_sql}";
    $params = [
        'type_name' => $type_name,
        'type_field_id' => $type_name_field_id,
    ];

    return $DB->execute($sql, $params);
}

/**
 * Generate a unique idnumber for the row.
 * We simply start off by appending the id to the name. If that already exists, simply add a seq nr to the end until we get one that
 * doesn't yet exist
 *
 * @param stdClass $row
 * @return string
 */
function totara_evidence_generate_type_idnumber(stdClass $row): string {
    global $DB;

    $idnumber = '';
    $exist = true;
    $seq = 1;
    while ($exist) {
        $idnumber = $row->name . '-' . $row->id . '.' . $seq++;
        $exist = $DB->record_exists('totara_evidence_type', ['idnumber' => $idnumber]);
    }
    return $idnumber;
}

/**
 * - Ensure the type has an idnumber (else it isn't available for import)
 * - Set the 'location' field to LOCATION_RECORD_OF_LEARNING
 * - Un-hide the type if needed
 *
 * @param int $type_id
 * @param array $options
 * @return bool
 */
function totara_evidence_update_legacy_type(int $type_id): bool {
    global $DB;

    try {
        $row = $DB->get_record('totara_evidence_type', ['id' => $type_id], '*', MUST_EXIST);
    } catch (dml_missing_record_exception $e) {
        return false;
    }

    // Assign an idnumber if empty
    if (empty($row->idnumber)) {
        $idnumber = totara_evidence_generate_type_idnumber($row);
        $row->idnumber = preg_replace('/\s+/', '', $idnumber);
    }

    // TODO: change to LOCATION_EVIDENCE_BANK once TL-34228 lands
    $row->location = evidence_type_model::LOCATION_RECORD_OF_LEARNING;
    $row->status = evidence_type_model::STATUS_ACTIVE;

    return $DB->update_record('totara_evidence_type', $row);
}

/**
 * Delete the legacy import type
 *
 * @return bool
 */
function totara_evidence_delete_legacycompletionimport() {
    global $DB;

    $system_type = $DB->get_record('totara_evidence_type', ['idnumber' => 'legacycompletionimport']);
    $nitems = $DB->count_records('totara_evidence_item', ['typeid' => $system_type->id]);
    if ($nitems === 0) {
        return $DB->delete_records('totara_evidence_type', ['idnumber' => 'legacycompletionimport']);
    }

    return true;
}

/**
 * Create the multilang:unspecified type if it doesn't already exist
 *
 * @param int $legacy_import_type_id
 * @return stdClass
 */
function totara_evidence_create_unspecified_type(int $legacy_import_type_id) {
    global $DB;

    $type = $DB->get_record('totara_evidence_type', ['name' => 'multilang:unspecified'], '*', IGNORE_MISSING);
    if (!$type) {
        $admin_userid = get_admin()->id;
        $current_time = time();

        $type_record = [
            'name' => 'multilang:unspecified',
            'idnumber' => 'multilang:unspecified',
            'description' => 'multilang:unspecified',
            'descriptionformat' => FORMAT_HTML,
            'location' => 0, // \totara_evidence\models\evidence_type::LOCATION_EVIDENCE_BANK
            'status' => 1, // Equal to \totara_evidence\models\evidence_type::STATUS_ACTIVE
            'created_by' => $admin_userid,
            'modified_by' => $admin_userid,
            'created_at' => $current_time,
            'modified_at' => $current_time,
        ];
        $type_id = $DB->insert_record('totara_evidence_type', $type_record);

        // Copy all fields in the legacycompletionimport type to unspecified
        $fields = $DB->get_recordset('totara_evidence_type_info_field', ['typeid' => $legacy_import_type_id]);
        foreach ($fields as $legacy_field) {
            $new_field = clone $legacy_field;
            $new_field->typeid = $type_id;
            unset($new_field->id);
            $DB->insert_record('totara_evidence_type_info_field', $new_field);
        }
        return $DB->get_record('totara_evidence_type', ['name' => 'multilang:unspecified']);;
    } else {
        // Ensure the unspecified type has an idnumber
        if (empty($unspecified_type->idnumber)) {
            $type->idnumber = 'unspecified';
            $DB->update_record('totara_evidence_type', $type);
        }
    }
    return $type;
}

/**
 * Move all remaining items linked to the legacy import type to the unspecified type.
 * NOTE - this function should only be called once all items that were imported to a specific type have been moved.
 *
 * @param int $legacy_import_type_id
 * @param int $old_name_field_id
 */
function totara_evidence_move_items_to_unspecified_type(int $legacy_import_type_id, int $old_name_field_id) {
    global $DB;

    // Checking that no items with an existing old type exists
    $sql =
        "SELECT COUNT(*)
           FROM {totara_evidence_item} ev_item
           JOIN {totara_evidence_type_info_data} etd
             ON etd.evidenceid = ev_item.id
            AND etd.fieldid = :type_fieldid
           JOIN {totara_evidence_type} ev_type
             ON ev_type.name = etd.data
          WHERE ev_item.typeid = :legacy_typeid";
    $param = [
        'type_fieldid' => $old_name_field_id,
        'legacy_typeid' => $legacy_import_type_id,
    ];
    $nitems = $DB->count_records_sql($sql, $param);
    if ($nitems > 0) {
        throw new \coding_exception("totara_evidence_move_items_to_unspecified_type should only after all other items have been moved");
    }

    $items = $DB->get_records('totara_evidence_item', ['typeid' => $legacy_import_type_id]);
    if (count($items) > 0) {
        $trans = $DB->start_delegated_transaction();

        $unspecified_type = totara_evidence_create_unspecified_type($legacy_import_type_id);
        if (totara_evidence_move_fields($old_name_field_id, '', $legacy_import_type_id, $unspecified_type->id) === false) {
            $DB->rollback_delegated_transaction($trans);
            return;
        }

        if (totara_evidence_move_items($old_name_field_id, '', $unspecified_type->id) === false) {
            $DB->rollback_delegated_transaction($trans);
            return;
        }

         $DB->commit_delegated_transaction($trans);
    }
}

/**
 * Restore evidence items created before migration to their original type
 */
function totara_evidence_restore_legacy_import_types() {
    global $DB;

    $system_type = $DB->get_record('totara_evidence_type', ['idnumber' => 'legacycompletionimport'], '*', IGNORE_MISSING);
    if ($system_type === false) {
        return;
    }

    $system_type_fields = $DB->get_records('totara_evidence_type_info_field', ['typeid' => $system_type->id]);
    $old_name_field_id = 0;
    foreach ($system_type_fields as $field) {
        if ($field->fullname == 'multilang:old_type') {
            $old_name_field_id = $field->id;
            break;
        }
    }

    if ($old_name_field_id === 0) {
        return;
    }

    $types_to_restore = totara_evidence_get_legacy_import_type_map();
    foreach ($types_to_restore as $name => $type_map) {
        $trans = $DB->start_delegated_transaction();

        if (totara_evidence_move_fields($old_name_field_id, $name, $system_type->id, $type_map->new_type_id) === false) {
            $DB->rollback_delegated_transaction($trans);
            continue;
        }

        if (totara_evidence_move_items($old_name_field_id, $name, $type_map->new_type_id) === false) {
            $DB->rollback_delegated_transaction($trans);
            continue;
        }

        if (totara_evidence_delete_old_type_name($old_name_field_id, $name) === false) {
            $DB->rollback_delegated_transaction($trans);
            continue;
        }

        if (totara_evidence_update_legacy_type($type_map->new_type_id) === false) {
            $DB->rollback_delegated_transaction($trans);
            continue;
        }

         $DB->commit_delegated_transaction($trans);
    }

    // At this point the only items left that belongs to the legacy import type are items
    //    - that were imported without a type  OR
    //    - items whose type no longer exists
    // Move these to the 'unspecified' type
    totara_evidence_move_items_to_unspecified_type($system_type->id, $old_name_field_id);

    totara_evidence_delete_legacycompletionimport();
}

/**
 * Remove deleted user evidence
 */
function totara_evidence_remove_deleted_user_evidence() {
    global $DB;

    $sql = "SELECT totaraevidence.id AS totaraevidenceid,
            totaraevidence.user_id AS userid
            FROM {totara_evidence_item} AS totaraevidence
            LEFT JOIN {user} AS auser ON auser.id = totaraevidence.user_id
            WHERE auser.deleted = 1";

    $evidencestobedeleted = $DB->get_records_sql($sql);

    foreach ($evidencestobedeleted as $evidence) {
        $DB->delete_records('totara_evidence_item', array('id' => $evidence->totaraevidenceid));
    }
}
