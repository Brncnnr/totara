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
 * @author Alastair Munro <alastair.munro@totaralearning.com>
 * @package totara_tenant
 */

namespace totara_tenant\form;

use totara_form\form\element\action_button;
use totara_tenant\local\util;
use core_date;


defined('MOODLE_INTERNAL') || die();

/**
 * Upload users to a tenant
 */
final class user_upload extends \totara_form\form {
    private $upload_element;
    private $encoding_element;
    private $createpasswordifneeded_element;

    protected function definition() {
        $tenant = new \totara_form\form\element\hidden('tenantid', PARAM_INT);
        $this->model->add($tenant);
        $tenantid = $tenant->get_field_value();
        $tenantcontext = \context_tenant::instance($tenantid);

        // Set model to use tenant context
        $this->model->set_default_context($tenantcontext);

        $this->upload_element = new \totara_form\form\element\filepicker('tenant_user_upload', get_string('csvfile', 'totara_tenant'));
        $this->model->add($this->upload_element);

        $options = \core_text::get_encodings();
        $this->encoding_element = new \totara_form\form\element\select('encoding', get_string('encoding', 'tool_uploaduser'), $options);
        $this->model->add($this->encoding_element);

        $link = new \moodle_url("/pluginfile.php/{$tenantcontext->id}/totara_tenant/csvtemplate/users.csv");
        $link = \html_writer::link($link, get_string('download'));
        $template = new \totara_form\form\element\static_html('template', get_string('csvfiletemplate', 'totara_tenant'), $link);
        $template->add_help_button('useruploaddownloadtemplate', 'totara_tenant');
        $this->model->add($template);

        $this->createpasswordifneeded_element = new \totara_form\form\element\checkbox('createpasswordifneeded', get_string('createpasswordifneeded', 'auth'));
        $this->model->add($this->createpasswordifneeded_element);

        $forcepasswordchange = new \totara_form\form\element\checkbox('forcepasswordchange', get_string('forcepasswordchange'));
        $this->model->add($forcepasswordchange);

        $this->add_user_defaults();

        $buttongroup = new \totara_form\form\group\buttons('actionbuttonsgroup');
        $this->model->add($buttongroup, -1);

        $previewbutton = new action_button('preview', get_string('preview'), action_button::TYPE_RELOAD);
        $buttongroup->add($previewbutton);
        $submitbutton = new action_button('submit', get_string('uploadusers', 'totara_tenant'), action_button::TYPE_SUBMIT);
        $submitbutton->set_primarybutton(true);
        $buttongroup->add($submitbutton);
        $cancelbutton = new action_button('cancel', get_string('cancel'), action_button::TYPE_CANCEL);
        $buttongroup->add($cancelbutton);
    }

    /**
     * Validate form and file data.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation(array $data, array $files) {
        $errors = parent::validation($data, $files);

        if (empty($files['tenant_user_upload'])) {
            $errors['tenant_user_upload'] = get_string('required');
        } else {
            $file = reset($files['tenant_user_upload']);
            list('error' => $error) = util::validate_users_csv_structure($file->get_content(), $data['encoding'], !(bool)$data['createpasswordifneeded']);
            if ($error !== null) {
                $errors['tenant_user_upload'] = $error;
            }
        }

        return $errors;
    }

    /**
     * Add user defaults for columns that are not in CSV.
     *
     * Note: only add fields that are shared by all users,
     *       do not add unique fields such as description, idnumber or phone numbers.
     */
    private function add_user_defaults() {
        $userdefaultssection = new \totara_form\form\group\section('userelements', get_string('useruploaduserdefaults', 'totara_tenant'));
        $userdefaultssection->set_expanded(false);
        $this->model->add($userdefaultssection);

        $choices = array(0 => get_string('emaildisplayno'), 1 => get_string('emaildisplayyes'), 2 => get_string('emaildisplaycourse'));
        $maildisplay = new \totara_form\form\element\select('default_maildisplay', get_string('emaildisplay'), $choices);
        $userdefaultssection->add($maildisplay);

        $choices = array(0 => get_string('textformat'), 1 => get_string('htmlformat'));
        $mailformat = new \totara_form\form\element\select('default_mailformat', get_string('emailformat'), $choices);
        $userdefaultssection->add($mailformat);

        $choices = array(0 => get_string('emaildigestoff'), 1 => get_string('emaildigestcomplete'), 2 => get_string('emaildigestsubjects'));
        $maildigest = new \totara_form\form\element\select('default_maildigest', get_string('emaildigest'), $choices);
        $userdefaultssection->add($maildigest);

        $choices = [1 => get_string('autosubscribeyes'), 0 => get_string('autosubscribeno')];
        $forumautosub = new \totara_form\form\element\select('default_autosubscribe', get_string('autosubscribe'), $choices);
        $userdefaultssection->add($forumautosub);

        $city = new \totara_form\form\element\text('default_city', get_string('city'), PARAM_TEXT);
        $city->set_attributes(['maxlength' => '120', 'size' => '25']);
        $userdefaultssection->add($city);

        $choices = get_string_manager()->get_list_of_countries();
        $choices = ['' => get_string('selectacountry') . '...'] + $choices;
        $country = new \totara_form\form\element\select('default_country', get_string('country'), $choices);
        $userdefaultssection->add($country);

        $choices = core_date::get_list_of_timezones(null, true);
        $timezone = new \totara_form\form\element\select('default_timezone', get_string('timezone'), $choices);
        $userdefaultssection->add($timezone);

        $preflang = new \totara_form\form\element\select('default_lang', get_string('preferredlanguage'), get_string_manager()->get_list_of_translations());
        $userdefaultssection->add($preflang);

        $institution = new \totara_form\form\element\text('default_institution', get_string('institution'), PARAM_TEXT);
        $institution->set_attributes(['maxlength' => "255", 'size' => "25"]);
        $userdefaultssection->add($institution);

        $department = new \totara_form\form\element\text('default_department', get_string('department'), PARAM_TEXT);
        $department->set_attributes(['maxlength' => "255", 'size' => "25"]);
        $userdefaultssection->add($department);
    }

    /**
     * Generate preview table markup.
     *
     * @return string
     */
    public function render_preview(): string {
        global $OUTPUT, $CFG;
        require_once($CFG->dirroot . '/lib/csvlib.class.php');

        if (empty($this->upload_element->get_files()['tenant_user_upload'])) {
            return '';
        }

        $file = reset($this->upload_element->get_files()['tenant_user_upload']);
        $content = $file->get_content();

        $result = $OUTPUT->heading(get_string('csvfilepreview', 'totara_tenant'), 3);

        $selectedencoding = $this->encoding_element->get_data()['encoding'];
        $requirepasswords = !(bool)$this->createpasswordifneeded_element->get_data()['createpasswordifneeded'];

        list('delimitername' => $delimitername, 'error' => $csverror) = util::validate_users_csv_structure($content, $selectedencoding, $requirepasswords);

        if ($csverror !== null) {
            $result .= $OUTPUT->notification($csverror, \core\output\notification::NOTIFY_ERROR);
        }

        if ($delimitername === null) {
            return $result;
        }

        $iid = \csv_import_reader::get_new_iid('uploaduser');
        $cir = new \csv_import_reader($iid, 'uploaduser');

        if (!$cir->load_csv_content($content, $selectedencoding, $delimitername)) {
            return '';
        }

        $filecolumns = $cir->get_columns();

        // Track whether values for profile fields defined as unique have already been used.
        $profilefieldvalues = [];

        $data = [];
        $cir->init();
        $linenum = 1; // Column names are on the first line.
        while ($line = $cir->next()) {
            $linenum++;
            $rowcols = [];
            foreach ($filecolumns as $key => $column) {
                if (isset($line[$key])) {
                    $rowcols[$column] = $line[$key];
                } else {
                    $rowcols[$column] = '';
                }
            }
            $rowcols = array_map('trim', $rowcols);
            if ($csverror === null) {
                $errormsg = util::validate_users_csv_row($rowcols, $requirepasswords, $profilefieldvalues);
                $rowcols = array_map('s', $rowcols);
                if ($errormsg) {
                    $rowcols['errors'] = implode('<br />', $errormsg);
                } else {
                    $rowcols['errors'] = '';
                }
            } else {
                // Skip validation if CSV file structure is invalid.
                $rowcols = array_map('s', $rowcols);
            }
            if (isset($rowcols['password']) && strlen($rowcols['password']) > 0) {
                $rowcols['password'] = '******';
            }
            // The order of columns is important here!
            $data[] = array_merge([$linenum], array_values($rowcols));
        }
        $cir->close();

        $table = new \html_table();
        $table->id = "uupreview";
        $table->attributes['class'] = 'generaltable';
        $table->summary = get_string('uploaduserspreview', 'tool_uploaduser');
        $table->head = [];
        $table->data = $data;

        $table->head[] = get_string('uucsvline', 'tool_uploaduser');
        foreach ($filecolumns as $column) {
            $table->head[] = s($column);
        }
        if ($csverror === null) {
            $table->head[] = get_string('useruploaderrors', 'totara_tenant');
        }
        $result .= $OUTPUT->render($table);

        return $result;
    }
}
