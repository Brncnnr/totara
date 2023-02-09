<?php
/*
 * This file is part of Totara LMS
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
 * @author Nathan Lewis <nathan.lewis@totaralms.com>
 * @package totara_core
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot.'/'.$CFG->admin.'/tool/customlang/locallib.php');

class totara_core_lang_link_testcase extends advanced_testcase {

    /**
     * Lang strings with suffix '_link' are not translated in AMOS or in the built-in string customisation
     * tool within Totara. This is because these strings are used to generate links to the help site. For
     * example, $string['messagetemplate_link'] = 'Event Monitoring'; is used to generate the URL
     * https://help.totaralearning.com/display/TH13/Event%20Monitoring and is displayed in a help popup.
     */
    public function test_lang_link_keys_are_in_whitelist(): void {
        $stringman  = get_string_manager();
        $components = tool_customlang_utils::list_components();

        $whitelist = $this->get_whitelist();

        foreach ($components as $component) {
            $strings = $stringman->load_component_strings($component, current_language());

            foreach ($strings as $key => $string) {
                if (substr_compare($key, '_link', strlen($key) - strlen('_link'), strlen('_link')) !== 0) {
                    continue;
                }

                if (isset($whitelist[$component]) && in_array($key, $whitelist[$component])) {
                    continue;
                }

                if ($stringman->string_deprecated($key, $component)) {
                    continue;
                }

                self::fail(
                    "String keys ending with '_link' are reserved. If '{$key}' in component '{$component}' is " .
                    "used to link to Totara documentation then it should be added to the whitelist, otherwise " .
                    "it should be renamed."
                );
            }
        }
    }

    /**
     * Get the list of all strings keys ending in _link which are expected to be used in links to Totara
     * documentation, grouped by component
     *
     * @return string[][]
     */
    private function get_whitelist(): array {
        return [
            'core' => [
                'courselegacyfiles_link',
                'scale_link',
                'scalestandard_link',
            ],
            'core_admin' => [
                'cron_link',
                'upgradepluginsinfo_link',
            ],
            'core_backup' => [
                'filealiasesrestorefailures_link',
            ],
            'core_completion' => [
                'completion_link',
            ],
            'core_grades' => [
                'aggregation_link',
                'aggregationcoefextra_link',
                'aggregationcoefextrasum_link',
                'aggregationcoefextraweight_link',
                'aggregationcoefweight_link',
                'calculation_link',
                'importcsv_link',
                'importoutcomes_link',
                'minmaxtouse_link',
            ],
            'core_group' => [
                'importgroups_link',
            ],
            'core_plugin' => [
                'notdownloadable_link',
                'validationmsg_missingcomponent_link',
            ],
            'core_question' => [
                'editcategories_link',
                'exportquestions_link',
                'importquestions_link',
                'parentcategory_link',
                'howquestionsbehave_link',
            ],
            'core_role' => [
                'assignroles_link',
                'overridepermissions_link',
                'roles_link',
            ],
            'qtype_calculated' => [
                'editdatasets_link',
                'pluginname_link',
            ],
            'qtype_calculatedmulti' => [
                'pluginname_link',
            ],
            'qtype_calculatedsimple' => [
                'pluginname_link',
            ],
            'qtype_ddimageortext' => [
                'pluginname_link',
            ],
            'qtype_ddmarker' => [
                'pluginname_link',
            ],
            'qtype_ddwtos' => [
                'pluginname_link',
            ],
            'qtype_essay' => [
                'pluginname_link',
            ],
            'qtype_gapselect' => [
                'pluginname_link',
            ],
            'qtype_match' => [
                'pluginname_link',
            ],
            'qtype_multianswer' => [
                'pluginname_link',
            ],
            'qtype_multichoice' => [
                'pluginname_link',
            ],
            'qtype_numerical' => [
                'pluginname_link',
            ],
            'qtype_randomsamatch' => [
                'pluginname_link',
            ],
            'qtype_shortanswer' => [
                'pluginname_link',
            ],
            'qtype_truefalse' => [
                'pluginname_link',
            ],
            'mod_assign' => [
                'modulename_link',
            ],
            'mod_book' => [
                'modulename_link',
            ],
            'mod_chat' => [
                'modulename_link',
            ],
            'mod_choice' => [
                'modulename_link',
            ],
            'mod_contentmarketplace' => [
                'modulename_link',
            ],
            'mod_data' => [
                'modulename_link',
                'uploadrecords_link',
            ],
            'mod_feedback' => [
                'modulename_link',
            ],
            'mod_folder' => [
                'modulename_link',
            ],
            'mod_forum' => [
                'modulename_link',
            ],
            'mod_glossary' => [
                'modulename_link',
            ],
            'mod_imscp' => [
                'modulename_link',
            ],
            'mod_label' => [
                'modulename_link',
            ],
            'mod_lesson' => [
                'modulename_link',
            ],
            'mod_lti' => [
                'modulename_link',
                'modulename_shortcut_link',
            ],
            'mod_page' => [
                'modulename_link',
            ],
            'mod_quiz' => [
                'editingquiz_link',
                'import_link',
                'modulename_link',
                'overduehandling_link',
                'quizopenclose_link',
                'timelimit_link',
            ],
            'mod_resource' => [
                'displayselect_link',
                'modulename_link',
            ],
            'mod_scorm' => [
                'modulename_link',
            ],
            'mod_survey' => [
                'modulename_link',
                'surveytype_link',
            ],
            'mod_url' => [
                'modulename_link',
            ],
            'mod_wiki' => [
                'formatcreole_link',
                'formatnwiki_link',
                'modulename_link',
            ],
            'mod_workshop' => [
                'allowedfiletypesforoverallfeedback_link',
                'allowedfiletypesforsubmission_link',
                'modulename_link',
            ],
            'enrol_guest' => [
                'status_link',
            ],
            'gradeimport_direct' => [
                'userdata_link',
            ],
            'gradeimport_xml' => [
                'importxml_link',
            ],
            'qbehaviour_deferredcbm' => [
                'cbmgrades_link',
                'certainty_link',
            ],
            'qformat_aiken' => [
                'pluginname_link',
            ],
            'qformat_gift' => [
                'pluginname_link',
            ],
            'qformat_missingword' => [
                'pluginname_link',
            ],
            'qformat_multianswer' => [
                'pluginname_link',
            ],
            'qformat_webct' => [
                'pluginname_link',
            ],
            'qformat_xhtml' => [
                'pluginname_link',
            ],
            'qformat_xml' => [
                'pluginname_link',
            ],
            'tool_monitor' => [
                'messagetemplate_link',
            ],
            'tool_oauth2' => [
                'issueralloweddomains_link',
            ],
            'repository_upload' => [
                'mimetype_whitelist_link',
            ],
        ];
    }
}
