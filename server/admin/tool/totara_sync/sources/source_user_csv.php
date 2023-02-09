<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @author Eugene Venter <eugene@catalyst.net.nz>
 * @package totara
 * @subpackage totara_sync
 */

require_once($CFG->dirroot.'/admin/tool/totara_sync/sources/classes/source.user.class.php');
require_once($CFG->dirroot.'/admin/tool/totara_sync/lib.php');
require_once($CFG->dirroot.'/admin/tool/totara_sync/elements/user.php');

class totara_sync_source_user_csv extends totara_sync_source_user {
    use \tool_totara_sync\internal\source\csv_trait;

    function get_filepath() {
        $path = '/csv/ready/user.csv';
        $pathos = $this->get_canonical_filesdir($path);
        return $pathos;
    }

    function config_form(&$mform) {
        global $CFG;

        $this->config->import_idnumber = "1";
        $this->config->import_username = "1";
        $this->config->import_timemodified = "1";
        if (!empty($this->element->config->allow_create)) {
            $this->config->import_firstname = "1";
            $this->config->import_lastname = "1";
        }
        if (empty($this->element->config->allowduplicatedemails)) {
            $this->config->import_email = "1";
        }
        $this->config->import_deleted = empty($this->element->config->sourceallrecords) ? "1" : "0";

        $this->config_form_add_csv_details($mform);

        parent::config_form($mform);
    }

    function config_save($data) {
        $this->config_save_csv_file_details($data);
        parent::config_save($data);
    }

    /**
     * Check if length limit for a field is exceeded and truncate it.
     *
     * @param array $datarows contains all rows from the CSV file
     * @param array $columnsinfo contains the metadata of the fields to import from the CSV file
     * @param array $fieldmappings contains mapped fields from the CSV file
     * @param string $source source type (user, org, pos)
     */
    public function check_length_limit(&$datarows, $columnsinfo, $fieldmappings, $source) {
        foreach ($datarows as $i => $datarow) {
            foreach ($datarow as $name => $value) {
                if ((($columnsinfo[$name]->type == 'varchar' || $columnsinfo[$name]->type == 'nvarchar') &&
                        core_text::strlen($value)) && (core_text::strlen($value) > $columnsinfo[$name]->max_length) &&
                    $columnsinfo[$name]->max_length != -1) {
                    $field = in_array($name, $fieldmappings) ? array_search($name, $fieldmappings) : $name;
                    // Prepare value to display in error message and for totara_sync_log table.
                    if (core_text::strlen($value) > 75) {
                        $value = core_text::substr($value, 0, 75) . ' ...';
                    }
                    $this->addlog(get_string('lengthlimitexceeded', 'tool_totara_sync', (object) array(
                        'idnumber' => $datarow['idnumber'],
                        'field' => $field,
                        'value' => $value,
                        'length' => $columnsinfo[$name]->max_length,
                        'source' => $source
                    )), 'error', 'populatesynctablecsv');
                    $max_length = (int) $columnsinfo[$name]->max_length - 1;

                    // We will delete this records in check_sanity() to save id and prevent dataloss
                    $datarows[$i][$field] = core_text::substr($value, 0, $max_length);
                    $datarows[$i]['username'] = 'data_exceeded_length';
                }
            }
        }
    }

    function import_data($temptable) {
        global $CFG, $DB, $OUTPUT;

        $file = $this->open_csv_file();

        // Map CSV fields.
        $fields = fgetcsv($file, 0, $this->config->delimiter);
        $fieldmappings = array();
        foreach ($this->fields as $f) {
            // Prevent allow_delete == SUSPEND_USERS and suspended column being set
            // at the same time.
            if ($f == 'suspended' && $this->element->config->allow_delete == totara_sync_element_user::SUSPEND_USERS) {
                unset($this->config->import_suspended);
            }

            if (empty($this->config->{'import_'.$f})) {
                continue;
            }
            if (!empty($this->config->{'fieldmapping_'.$f})) {
                $fieldmappings[$this->config->{'fieldmapping_'.$f}] = $f;
            }
        }

        foreach (array_keys($this->customfields) as $f) {
            if (empty($this->config->{'import_'.$f})) {
                continue;
            }
            if (!empty($this->config->{'fieldmapping_'.$f})) {
                $fieldmappings[$this->config->{'fieldmapping_'.$f}] = $f;
            }
        }

        // Throw an exception if fields contain invalid characters
        foreach ($fields as $field) {
            $invalidchars = preg_replace('/[ ?!A-Za-z0-9_-]/i', '', $field);
            if (strlen($invalidchars)) {
                $errorvar = new stdClass();
                $errorvar->invalidchars = $invalidchars[0];
                $errorvar->delimiter = $this->config->delimiter;
                throw new totara_sync_exception($this->get_element_name(), 'mapfields', 'csvnotvalidinvalidchars', $errorvar);
            }
        }

        // Ensure necessary mapped fields are present in the CSV
        foreach ($fieldmappings as $m => $f) {
            if (!in_array($m, $fields)) {
                if ($f == $m) {
                    throw new totara_sync_exception($this->get_element_name(), 'mapfields', 'csvnotvalidmissingfieldx', $f);
                } else {
                    throw new totara_sync_exception($this->get_element_name(), 'mapfields', 'csvnotvalidmissingfieldxmappingx', (object)array('field' => $f, 'mapping' => $m));
                }
            }
        }

        // Create an import map of fields that are going to be imported with their
        // mapped field name for use later on.
        $importmap = [];
        foreach ($fields as $index => $field) {
            if (!empty($fieldmappings[$field])) {
                // We have a mapping
                $realfieldname = $fieldmappings[$field];
                if (!empty($this->config->{'import_'.$realfieldname})) {
                    $importmap[$field] = $realfieldname;
                }
            } else {
                // No entry in fieldmappings but it could be a field that has
                // been configured to be mapped so we need to ensure there is
                // no fieldmapping setting before adding to import map.
                if (empty($this->config->{'fieldmapping_' . $field})) {
                    if (!empty($this->config->{'import_' . $field})) {
                        $importmap[$field] = $field;
                    }
                }
            }
        }

        // Check field integrity for general fields.
        foreach ($this->fields as $f) {
            if (empty($this->config->{'import_'.$f}) || in_array($f, $fieldmappings)) {
                // Disabled or mapped fields can be ignored
                continue;
            }
            if (!in_array($f, $fields)) {
                throw new totara_sync_exception($this->get_element_name(), 'importdata', 'csvnotvalidmissingfieldx', $f);
            }
        }

        // Check field integrity for custom fields.
        foreach ($this->customfields as $cf => $name) {
            if (empty($this->config->{'import_'. $cf}) || in_array($cf, $fieldmappings)) {
                // Disabled or mapped fields can be ignored.
                continue;
            }
            if (!in_array($cf, $fields)) {
                throw new totara_sync_exception($this->get_element_name(), 'importdata', 'csvnotvalidmissingfieldx', $cf);
            }
        }

        // Populate temp sync table from CSV
        $now = time();
        $datarows = array();    // holds csv row data
        $dbpersist = TOTARA_SYNC_DBROWS;  // # of rows to insert into db at a time
        $rowcount = 0;
        $fieldcount = new stdClass();
        $fieldcount->headercount = count($fields);
        $fieldcount->rownum = 0;
        $csvdateformat = (isset($CFG->csvdateformat)) ? $CFG->csvdateformat : get_string('csvdateformatdefault', 'totara_core');
        $badtimezones = false;

        // Convert setting into a boolean.
        $csvsaveemptyfields = isset($this->element->config->csvsaveemptyfields) && $this->element->config->csvsaveemptyfields == 1;

        while ($csvrow = fgetcsv($file, 0, $this->config->delimiter)) {
            $fieldcount->rownum++;
            // Skip empty rows
            if (is_array($csvrow) && current($csvrow) === null) {
                $fieldcount->fieldcount = 0;
                $fieldcount->delimiter = $this->config->delimiter;
                $this->addlog(get_string('fieldcountmismatch', 'tool_totara_sync', $fieldcount), 'error', 'populatesynctablecsv');
                unset($fieldcount->delimiter);
                continue;
            }
            $fieldcount->fieldcount = count($csvrow);
            if ($fieldcount->fieldcount !== $fieldcount->headercount) {
                $fieldcount->delimiter = $this->config->delimiter;
                $this->addlog(get_string('fieldcountmismatch', 'tool_totara_sync', $fieldcount), 'error', 'populatesynctablecsv');
                unset($fieldcount->delimiter);
                continue;
            }

            $csvrow = array_combine($fields, $csvrow);  // nice associative array ;)

            // Set up a db row
            $dbrow = [];

            foreach ($importmap as $mappedname => $fieldname) {
                if (isset($csvrow[$mappedname])) {
                    if ($mappedname !== $fieldname) {
                        $dbrow[$fieldname] = $csvrow[$mappedname];
                    } else {
                        $dbrow[$fieldname] = $csvrow[$fieldname];
                    }
                }
            }

            $dbrow = $this->clean_fields($dbrow);

            if (empty($csvrow['timemodified'])) {
                $dbrow['timemodified'] = 0;
            } else {
                // Try to parse the contents - if parse fails assume a unix timestamp and leave unchanged
                $parsed_date = totara_date_parse_from_format($csvdateformat, trim($csvrow['timemodified']), true);
                if ($parsed_date) {
                    $dbrow['timemodified'] = $parsed_date;
                }
            }

            // Deleted doesn't support ignore empty fields, empty defaults to zero.
            if (isset($dbrow['deleted'])) {
                $dbrow['deleted'] = empty($dbrow['deleted']) ? 0 : 1;
            }

            if (isset($dbrow['suspended'])) {
                if ($dbrow['suspended'] === '' && !$csvsaveemptyfields)  {
                    $dbrow['suspended'] = null;
                } else {
                    $dbrow['suspended'] = empty($dbrow['suspended']) ? 0 : 1;
                }
            }

            if (isset($dbrow['email'])) {
                if ($dbrow['email'] === '' && !$csvsaveemptyfields) {
                    $dbrow['email'] = null;
                }
            }

            if (isset($dbrow['emailstop'])) {
                // If email stop is empty then set it to null.
                // (if an empty string is inserted into an integer field in the DB is will become 0).
                if ($dbrow['emailstop'] === '' && !$csvsaveemptyfields) {
                    $dbrow['emailstop'] = null;
                } else {
                    // If we are saving empty field this can come empty from source and will become zero.
                    $dbrow['emailstop'] = empty($dbrow['emailstop']) ? 0 : 1;
                }
            }

            if (isset($dbrow['timezone'])) {
                if (!empty($dbrow['timezone'])) {
                    $timezone = core_date::normalise_timezone($dbrow['timezone']);
                    if ($timezone != '99' and $timezone !== $dbrow['timezone']) {
                        // Unsupported timezone, output message at end of process
                        $badtimezones = true;
                    }
                    $dbrow['timezone'] = $timezone;
                } else if ($dbrow['timezone'] === '' && $csvsaveemptyfields) {
                    $dbrow['timezone'] = '99';
                } else {
                    $dbrow['timezone'] = null;
                }
            }

            if (isset($dbrow['tenantmember'])) {
                if ($dbrow['tenantmember'] === '' && !$csvsaveemptyfields) {
                    $dbrow['tenantmember'] = null;
                }
            }

            // Custom fields are special - needs to be json-encoded
            if (!empty($this->customfields)) {
                $cfield_data = array();
                foreach (array_keys($this->customfields) as $cf) {
                    if (!empty($this->config->{'import_'.$cf})) {
                        // Get shortname and check if we need to do field type processing
                        if ($realfieldname = array_search($cf, $importmap)) {
                            $value = trim($csvrow[$realfieldname]);
                        } else {
                            $value = trim($csvrow[$cf]);
                        }
                        if ($value === '') {
                            if (!$csvsaveemptyfields) {
                                // Empty means skip, don't import.
                                continue;
                            }
                        } else if (isset($value)) {
                            $shortname = str_replace("customfield_", "", $cf);
                            $datatype = $DB->get_field('user_info_field', 'datatype', array('shortname' => $shortname));
                            switch ($datatype) {
                                case 'datetime':
                                    // Try to parse the contents - if parse fails assume a unix timestamp and leave unchanged.
                                    $parsed_date = totara_date_parse_from_format($csvdateformat, $value, true);
                                    if ($parsed_date) {
                                        $value = $parsed_date;
                                    } elseif (empty($value) || !is_numeric($value)) {
                                        // Don't try to put a value if the field has been left empty, is 0 or not numeric.
                                        continue 2;
                                    }
                                    break;
                                case 'date':
                                    // Try to parse the contents - if parse fails assume a unix timestamp and leave unchanged.
                                    $parsed_date = totara_date_parse_from_format($csvdateformat, $value, true, 'UTC');
                                    if ($parsed_date) {
                                        $value = $parsed_date;
                                    } elseif (empty($value) || !is_numeric($value)) {
                                        // Don't try to put a value if the field has been left empty, is 0 or not numeric.
                                        continue 2;
                                    }
                                    break;
                                case 'checkbox':
                                    $value = $value == '0' ? 0 : 1;
                                    break;
                                default:
                                    break;
                            }
                        }

                        $cfield_data[$cf] = $value;
                        unset($dbrow[$cf]);
                    }
                }
                $dbrow['customfields'] = json_encode($cfield_data);
                unset($cfield_data);
            }

            // We have dealt with all specific cases so all (non required) remaining empty
            // fields should be changed to null if we are not saving empty fields.
            foreach ($this->fields as $f) {
                if (isset($dbrow[$f]) && $dbrow[$f] === '' && !$csvsaveemptyfields && !in_array($f, $this->required_fields)) {
                    $dbrow[$f] = null;
                }
            }

            $datarows[] = $dbrow;
            $rowcount++;

            if ($rowcount >= $dbpersist) {
                $this->check_length_limit($datarows, $DB->get_columns($temptable), $fieldmappings, 'user');
                // Bulk insert
                try {
                    totara_sync_bulk_insert($temptable, $datarows);
                } catch (dml_exception $e) {
                    $debuginfo = debugging() ? $e->debuginfo : '';
                    throw new totara_sync_exception($this->get_element_name(), 'populatesynctablecsv', 'couldnotimportallrecords', $e->getMessage(), $debuginfo);
                }

                $rowcount = 0;
                unset($datarows);
                $datarows = array();

                gc_collect_cycles();
            }
        }  // while

        $this->check_length_limit($datarows, $DB->get_columns($temptable), $fieldmappings, 'user');
        // Insert remaining rows
        try {
            totara_sync_bulk_insert($temptable, $datarows);
        } catch (dml_exception $e) {
            $debuginfo = debugging() ? $e->debuginfo : '';
            throw new totara_sync_exception($this->get_element_name(), 'populatesynctablecsv', 'couldnotimportallrecords', $e->getMessage(), $debuginfo);
        }
        unset($fieldmappings);

        if ($badtimezones) {
            $OUTPUT->notification(get_string('badusertimezonemessage', 'tool_totara_timezonefix'), 'notifynotice');
        }

        $this->close_csv_file($file);

        // Update temporary table stats once import is done.
        $DB->update_temp_table_stats();

        return true;
    }

    /**
     * Get any notifications that should be displayed for the element source.
     *
     * @return string Notifications HTML.
     */
    public function get_notifications() {
        global $OUTPUT;

        $notifications = $this->get_common_csv_notifications();

        // Show a notification about delete suspending/unsuspending users
        if (isset($this->element->config->allow_delete) && $this->element->config->allow_delete == totara_sync_element_user::SUSPEND_USERS) {
            $suspenddelete = get_string('suspendcolumndisabled', 'tool_totara_sync');
            $notifications .= $OUTPUT->notification($suspenddelete, \core\output\notification::NOTIFY_WARNING);
        }

        return $notifications;
    }

    /**
     * Cleans values for import. Excludes custom fields, which should not be part of the input array.
     *
     * @param string[] $row with field name as key (after mapping) and value provided for the given field.
     * @return string[] Same structure as input but with cleaned values.
     */
    private function clean_fields($row) {
        $cleaned = [];
        foreach($row as $key => $value) {
            switch ($key) {
                case 'idnumber':
                case 'timemodified':
                case 'username':
                case 'firstname':
                case 'lastname':
                case 'firstnamephonetic':
                case 'lastnamephonetic':
                case 'middlename':
                case 'alternatename':
                case 'email':
                case 'emailstop':
                case 'city':
                case 'country':
                case 'timezone':
                case 'lang':
                case 'url':
                case 'institution':
                case 'department':
                case 'phone1':
                case 'phone2':
                case 'address':
                case 'auth':
                case 'deleted':
                case 'suspended':
                    $cleaned[$key] = clean_param(trim($value), PARAM_TEXT);
                    break;
                case 'description':
                case 'password':
                case 'tenantmember':      // Do not modify idnumbers!
                case 'tenantparticipant': // Do not modify idnumbers!
                    $cleaned[$key] = clean_param(trim($value), PARAM_RAW);
                    break;
                default:
                    // This is not an available field to be synced, don't include.
            }
        }

        return $cleaned;
    }
}
