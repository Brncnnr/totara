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
 * @author Russell England <russell.england@catalyst-net.nz>
 * @package totara
 * @subpackage completionimport
 */

use core\collection;
use totara_evidence\entity\evidence_type as evidence_type_entity;
use totara_evidence\models\evidence_type as evidence_type_model;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/totara/completionimport/lib.php');
require_once($CFG->libdir . '/csvlib.class.php');

class upload_form extends moodleform {
    public function definition() {
        global $DB, $CFG;
        $mform =& $this->_form;

        $data = $this->_customdata;

        if (($data->filesource == TCI_SOURCE_EXTERNAL) and empty($CFG->completionimportdir)) {
            // We need the config setting when using external files.
            return;
        }

        switch ($data->importname) {
            case 'course':
                $upload_field = 'course_uploadfile';
                $header_label = 'uploadcourse';
                $field_aria_label = 'coursefieldarialabel';
                break;
            case 'certification':
                $upload_field = 'certification_uploadfile';
                $header_label = 'uploadcertification';
                $field_aria_label = 'certificationfieldarialabel';
                break;
            default:
                $upload_field = 'uploadfile';
                $header_label = 'uploadfile';
                $field_aria_label = 'fieldarialabel';
        }

        // Prepend a reasonable CSS class to 'mform'.
        $mform->updateAttributes(['class' => "totara_completionimport__{$header_label}_form " . $mform->getAttribute('class')]);

        $upload_label = get_string('choosefile', 'totara_completionimport');

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'filesource');
        $mform->setType('filesource', PARAM_INT);

        if ($data->showdescription ?? false) {
            $uploadintro = '';

            if ($data->importname == 'course') {
                $columnnames = implode(',', get_columnnames('course'));
                $uploadintro = get_string('uploadcourseintro_csv', 'totara_completionimport');
                $uploadintro .= '<h4>' . get_string('uploadingrecords_course', 'totara_completionimport') . '</h4>';
                $uploadintro .= '<p>' . get_string('uploadcourseintro', 'totara_completionimport', $columnnames) . '</p>';
                $uploadintro .= '<h4>' . get_string('uploadingrecords_evidence', 'totara_completionimport') . '</h4>';
                $uploadintro .= '<p>' . get_string('uploadcourseevidenceintro', 'totara_completionimport', $columnnames) . '</p>';
            } else if ($data->importname == 'certification') {
                $columnnames = implode(',', get_columnnames('certification'));
                $uploadintro = get_string('uploadcertificationintro', 'totara_completionimport', $columnnames);
            }

            if ($uploadintro !== '') {
                $mform->addElement('html', \html_writer::tag('p', format_text($uploadintro, FORMAT_MOODLE, ['para' => false])));
            }
        }

        if ($data->filesource == TCI_SOURCE_EXTERNAL) {
            $sourcefilegroup = array();
            $stringbeginwith = '<p>' . get_string('sourcefile_beginwith', 'totara_completionimport', $CFG->completionimportdir) . '</p>';
            $sourcefilegroup[] = $mform->createElement('static', '', '', $stringbeginwith);
            $sourcefilegroup[] = $mform->createElement('text', 'sourcefile', '');
            $mform->setType('sourcefile', PARAM_PATH);

            $mform->addGroup($sourcefilegroup, 'sourcefilegrp', get_string('sourcefile', 'totara_completionimport'), array(''), false);
            $mform->addHelpButton('sourcefilegrp', 'sourcefile', 'totara_completionimport');
            $mform->addRule('sourcefilegrp', get_string('sourcefilerequired', 'totara_completionimport'), 'required');
        } else if ($data->filesource == TCI_SOURCE_UPLOAD) {
            $mform->addElement('filepicker',
                    $upload_field,
                    $upload_label,
                    null,
                    array('accepted_types' => array('csv')));
            $mform->addRule($upload_field, get_string('uploadfilerequired', 'totara_completionimport'), 'required');
        }

        $mform->addElement('advcheckbox', 'create_evidence', get_string('create_evidence', 'totara_completionimport'), '', [
            'aria-label' => get_string(
                $field_aria_label,
                'totara_completionimport',
                get_string('create_evidence', 'totara_completionimport')
            ),
        ]);
        $mform->addHelpButton('create_evidence', 'create_evidence', 'totara_completionimport');

        // Evidence types, array of [id => name]
        $evidence_type_options = [0 => get_string('default', 'totara_completionimport')];
        $evidence_type_options += evidence_type_entity::repository()
            ->select(['id', 'name'])
            ->where_not_in('idnumber', evidence_type_model::DEFAULT_SYSTEM_TYPES)
            ->order_by('name')
            ->get()
            ->map(function (evidence_type_entity $type) {
                return evidence_type_model::load_by_entity($type)->display_name;
            })
            ->all(true);
        $mform->addElement('select', 'evidencetype', get_string('evidencetype', 'totara_completionimport'), $evidence_type_options);
        $mform->setType('evidencetype', PARAM_INT);
        $mform->addHelpButton('evidencetype', 'evidencetype', 'totara_completionimport');
        $mform->disabledIf('evidencetype', 'create_evidence', 'notchecked', 1);

        // Static elements are not created with a name or id.
        // Wrapping it in a div with a specific id in order to allow update
        $mform->addElement('html', \html_writer::start_div('completionimport_evidencetype_customfields hidden'));
        $mform->addElement('static', '', '', get_string('evidencetype_customfields_list', 'totara_completionimport'));
        $mform->addElement('static', '', '', '');
        $mform->addElement('html', \html_writer::end_div());

        $dateformats = get_dateformats();
        $mform->addElement('select', 'csvdateformat', get_string('csvdateformat', 'totara_completionimport'), $dateformats,
            array('aria-label' => get_string($field_aria_label, 'totara_completionimport', get_string('csvdateformat', 'totara_completionimport'))));
        $mform->setType('csvdateformat', PARAM_TEXT);

        if (in_array($data->importname, ['course'])) {
            $selectoptions = [
                TCI_CSV_GRADE_POINT => get_string('csvgradeunit_point', 'totara_completionimport'),
                TCI_CSV_GRADE_PERCENT => get_string('csvgradeunit_percent', 'totara_completionimport'),
            ];
            $gradeunitstr = get_string('csvgradeunit', 'totara_completionimport');
            $mform->addElement('select', 'csvgradeunit', $gradeunitstr, $selectoptions,
                array('aria-label' => get_string($field_aria_label, 'totara_completionimport', get_string('csvgradeunit', 'totara_completionimport'))));
            $mform->setDefault('csvgradeunit', TCI_CSV_GRADE_POINT);
        }

        // Function get_delimiter_list() actually returns the list of separators as in "comma *separated* values".
        $separators = csv_import_reader::get_delimiter_list();
        $mform->addElement('select', 'csvseparator', get_string('csvseparator', 'totara_completionimport'), $separators,
            array('aria-label' => get_string($field_aria_label, 'totara_completionimport', get_string('csvseparator', 'totara_completionimport'))));
        $mform->setType('csvseparator', PARAM_TEXT);
        if (array_key_exists('cfg', $separators)) {
            $mform->setDefault('csvseparator', 'cfg');
        } else if (get_string('listsep', 'langconfig') == ';') {
            $mform->setDefault('csvseparator', 'semicolon');
        } else {
            $mform->setDefault('csvseparator', 'comma');
        }

        $delimiters = array('"' => '"', "'" => "'", '' => 'none');
        $mform->addElement('select', 'csvdelimiter', get_string('csvdelimiter', 'totara_completionimport'), $delimiters,
            array('aria-label' => get_string($field_aria_label, 'totara_completionimport', get_string('csvdelimiter', 'totara_completionimport'))));
        $mform->setType('csvdelimiter', PARAM_TEXT);

        $encodings = core_text::get_encodings();
        $mform->addElement('select', 'csvencoding', get_string('csvencoding', 'totara_completionimport'), $encodings,
            array('aria-label' => get_string($field_aria_label, 'totara_completionimport', get_string('csvencoding', 'totara_completionimport'))));
        $mform->setType('csvencoding', PARAM_TEXT);
        $mform->setDefault('csvencoding', 'UTF-8');

        if ($data->importname == 'certification') {
            $selectoptions = array(
                COMPLETION_IMPORT_TO_HISTORY => get_string('importactioncertificationhistory', 'totara_completionimport'),
                COMPLETION_IMPORT_COMPLETE_INCOMPLETE => get_string('importactioncertificationcertify', 'totara_completionimport'),
                COMPLETION_IMPORT_OVERRIDE_IF_NEWER => get_string('importactioncertificationnewer', 'totara_completionimport'),
            );
            $mform->addElement('select', 'importactioncertification', get_string('importactioncertification', 'totara_completionimport'), $selectoptions,
                array('aria-label' => get_string($field_aria_label, 'totara_completionimport', get_string('importactioncertification', 'totara_completionimport'))));
            $mform->setType('importactioncertification', PARAM_INT);
            $mform->addHelpButton('importactioncertification', 'importactioncertification', 'totara_completionimport');
        } else {
            $selectoptions = [
                COMPLETION_IMPORT_NEVER_OVERRIDE => get_string('overrideactivecourse_no', 'totara_completionimport'),
                COMPLETION_IMPORT_ALWAYS_OVERRIDE => get_string('overrideactivecourse_yes', 'totara_completionimport'),
                COMPLETION_IMPORT_OVERRIDE_IF_NEWER => get_string('overrideactivecourse_renew', 'totara_completionimport')
            ];
            $overrideactivesetting = 'overrideactive' . $data->importname;
            $overrideactivestr = get_string($overrideactivesetting, 'totara_completionimport');
            $mform->addElement('select', $overrideactivesetting, $overrideactivestr, $selectoptions,
                array('aria-label' => get_string($field_aria_label, 'totara_completionimport', get_string('overrideactive'.$data->importname, 'totara_completionimport'))));
            $mform->addHelpButton($overrideactivesetting, $overrideactivesetting, 'totara_completionimport');
        }

        $mform->addElement('advcheckbox', 'forcecaseinsensitive'.$data->importname, get_string('caseinsensitive'.$data->importname, 'totara_completionimport'), '',
            array('aria-label' => get_string($field_aria_label, 'totara_completionimport', get_string('caseinsensitive'.$data->importname, 'totara_completionimport'))));
        $mform->addHelpButton('forcecaseinsensitive'.$data->importname, 'caseinsensitive'.$data->importname, 'totara_completionimport');
        $mform->setAdvanced('forcecaseinsensitive'.$data->importname);

        $this->add_action_buttons(false, get_string('submit', 'totara_completionimport'));

        $this->set_data($data);
    }

    /**
     * Overriding this function to get unique form id so the form can be used more than once
     *
     * @return string form identifier
     */
    protected function get_form_identifier() {
        $formid = $this->_customdata->importname . '_' . get_class($this);
        return $formid;
    }

    public function validation($data, $files) {
        global $CFG;
        $errors = parent::validation($data, $files);

        if (isset($data['sourcefile'])) {
            if (empty($CFG->completionimportdir)) {
                // This form shouldn't have been shown in the first place, but just in case.
                $errors['sourcefilegrp'] = get_string('sourcefile_noconfig', 'totara_completionimport');
            } else if (strpos($data['sourcefile'], $CFG->completionimportdir) !== 0) {
                $errors['sourcefilegrp'] = get_string('sourcefile_validation', 'totara_completionimport',
                    $CFG->completionimportdir);
            }
        }

        return $errors;
    }
}
