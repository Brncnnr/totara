<?php
/**
 * This file is part of Totara Perform
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\rb\filter;

use stdClass;
use html_writer;

/** @var \core_config $CFG */
require_once($CFG->dirroot.'/totara/reportbuilder/filters/lib.php');

abstract class perform_filter_type extends \rb_filter_type {

    protected const RB_FILTER_IS_ANY_VALUE = 0;
    protected const RB_FILTER_IS_EQUAL_TO = 1;
    protected const RB_FILTER_IS_NOT_EQUAL_TO = 2;

    /**
     * Adds controls specific to this filter in the form.
     *
     * @param object $mform a MoodleForm object to setup
     */
    public function setupForm(&$mform) {
        global $SESSION;

        $label = format_string($this->label);
        $advanced = $this->advanced;
        $defaultvalue = $this->defaultvalue;

        $objs = array();
        $objs[] =& $mform->createElement('select', $this->name.'_op', $label, $this->get_operators());
        $objs[] = $mform->createElement(
            'static',
            'title'.$this->name,
            '',
            html_writer::tag(
                'span',
                '',
                ['id' => $this->name . 'title', 'class' => 'dialog-result-title']
            )
        )->set_allow_xss(true);
        $mform->setType($this->name.'_op', PARAM_TEXT);

        [$identifier, $component] = static::get_modal_title();
        // Can't use a button because id must be 'show-*-dialog' and formslib appends 'id_' to ID.
        $objs[] = $mform->createElement(
            'static',
            'selectorbutton',
            '',
            html_writer::empty_tag(
                'input',
                [
                    'type' => 'button',
                    'class' => 'rb-filter-button ' . static::get_modal_css(),
                    'value' => get_string($identifier, $component),
                    'id' => 'show-' . $this->name . '-dialog'
                ]
            )
        )->set_allow_xss(true);

        // Container for currently selected items.
        $content = html_writer::tag('div', '', ['class' => 'rb-filter-content-list list-' . $this->name]);
        $element = $mform->createElement('static', $this->name.'_list', '', $content)->set_allow_xss(true);
        $objs[] =& $element;

        // Create a group for the elements.
        $grp =& $mform->addElement('group', $this->name.'_grp', $label, $objs, '', false);
        $this->add_help_button($mform, $grp->_name, 'filterselect', 'filters');

        if ($advanced) {
            $mform->setAdvanced($this->name.'_grp');
            $mform->setAdvanced($this->name.'_list');
        }

        $mform->addElement('hidden', $this->name, '');
        $mform->setType($this->name, PARAM_TEXT);

        // Set default values.
        if (isset($SESSION->reportbuilder[$this->report->get_uniqueid()][$this->name])) {
            $defaults = $SESSION->reportbuilder[$this->report->get_uniqueid()][$this->name];
        } else if (!empty($defaultvalue)) {
            $this->set_data($defaultvalue);
        }
        if (isset($defaults['value'])) {
            $mform->setDefault($this->name, $defaults['value']);
            if (!isset($defaults['operator'])) {
                $defaults['operator'] = self::RB_FILTER_IS_EQUAL_TO;
            }
            $this->set_data($defaults);
        }
        if (isset($defaults['operator'])) {
            $mform->setDefault($this->name . '_op', $defaults['operator']);
        }
    }

    /**
     * Returns the condition to be used with SQL where
     *
     * @param array $data filter settings
     * @return array containing filtering condition SQL clause and params
     */
    function get_sql_filter($data): array {
        global $DB;

        if (!isset($data['value']) || empty($data['value'])) {
            // Don't filter if there isn't any value.
            // Return 1=1 instead of TRUE for MSSQL support.
            return [' 1=1 ', []];
        }

        if (!isset($data['operator'])) {
            $data['operator'] = static::RB_FILTER_IS_EQUAL_TO;
        } else if ((int)$data['operator'] == static::RB_FILTER_IS_ANY_VALUE) {
            // Don't filter if operator is any value.
            // Return 1=1 instead of TRUE for MSSQL support.
            return [' 1=1 ', []];
        }

        $items = explode(',', $data['value']);
        // Don't filter if none selected.
        if (empty($items)) {
            // Return 1=1 instead of TRUE for MSSQL support.
            return [' 1=1 ', []];
        }

        $query = $this->get_field();
        $equal = (int)$data['operator'] == static::RB_FILTER_IS_NOT_EQUAL_TO ? false : true;
        list($insql, $inparams) = $DB->get_in_or_equal(
            $items,
            SQL_PARAMS_NAMED,
            'param',
            $equal
        );
        return ["{$query} {$insql}", $inparams];
    }

    /**
     * Returns a human friendly description of the filter used as label.
     *
     * @param array $data filter settings
     * @return string active filter label
     */
    function get_label($data): string {
        $ids = explode(',', $data['value']);
        if (empty($ids)) {
            return '';
        }

        $a = new stdClass();
        $a->label = $this->label;

        $selected = [];
        $options = static::get_item_options();
        foreach ($options as $id => $display_name) {
            if (in_array($id, array_values($ids))) {
                $selected[] = '"' . $display_name . '"';
            }
        }
        $orstring = get_string('or', 'totara_reportbuilder');
        $a->value = implode($orstring, $selected);

        return get_string('selectlabelnoop', 'filters', $a);
    }

    /**
     * Definition after data.
     *
     * @param object $mform a MoodleForm object to setup
     */
    public function definition_after_data(&$mform) {

        if ($ids = $mform->getElementValue($this->name)) {
            $ids = explode(',', $ids);

            $items = static::get_item_options();
            if ($items) {
                $out = html_writer::start_tag('div', ['class' => "list-".$this->name]);
                foreach ($items as $id => $display_name) {
                    if (in_array($id, array_values($ids))) {
                        $out .= static::display_selected_items($id, $display_name, $this->name);
                    }
                }
                $out .= html_writer::end_tag('div');
                $mform->setDefault($this->name.'_list', $out);
            }
        }
    }

    /**
     * Include Js for this filter
     */
    public function include_js() {
        /** @var \moodle_page $PAGE */
        global $PAGE;

        [$identifier, $component] = static::get_modal_title();

        $code = [];
        $code[] = TOTARA_JS_DIALOG;
        $code[] = TOTARA_JS_TREEVIEW;
        local_js($code);

        $jsdetails = new stdClass();
        $jsdetails->strings = [
            $component => [$identifier]
        ];
        $jsdetails->args = ['filter_to_load' => $this->name, $this->name, 'reportid' => $this->report->_id];

        foreach ($jsdetails->strings as $scomponent => $sstrings) {
            $PAGE->requires->strings_for_js($sstrings, $scomponent);
        }
        $PAGE->requires->js_call_amd('mod_perform/filter_dialogs', 'init', $jsdetails->args);
    }

    /**
     * Is this filter performing the filtering of results?
     *
     * @param array $data element filtering data
     * @return bool
     */
    public function is_filtering(array $data): bool {
        $value = $data['value'] ?? '';
        return !empty($value);
    }

    /**
     * Returns an array of comparison operators.
     *
     * @return array of comparison operators
     */
    public function get_operators(): array {
        return [
            static::RB_FILTER_IS_ANY_VALUE => get_string('isanyvalue', 'filters'),
            static::RB_FILTER_IS_EQUAL_TO => get_string('isequalto', 'filters'),
            static::RB_FILTER_IS_NOT_EQUAL_TO => get_string('isnotequalto', 'filters')
        ];
    }

    /**
     * Retrieves data from the form data.
     *
     * @param object $formdata data submited with the form
     * @return mixed array filter data or false when filter not set
     */
    public function check_data($formdata) {
        $field = $this->name;
        $operator = $field . '_op';

        if (isset($formdata->$field) && $formdata->$field != '') {
            $data = [
                'operator' => (int)$formdata->$operator,
                'value'    => (string)$formdata->$field
            ];
            return $data;
        }
        return false;
    }

    /**
     * Given a element type returns the HTML to display it as a filter selection
     *
     * @param string $id
     * @param string $display_name
     * @param string $filtername The identifying name of the current filter
     *
     * @return string HTML to display a selected item
     */
    public static function display_selected_items($id, $display_name, $filtername): string {
        global $OUTPUT;
        $out = html_writer::start_tag(
            'div',
            [
                'data-filtername' => $filtername,
                'data-id' => $id,
                'class' => 'multiselect-selected-item'
            ]
        );
        $out .= format_string($display_name);
        $out .= $OUTPUT->action_icon(
            '#',
            new \pix_icon('/t/delete', get_string('delete'), 'moodle'),
            null,
            ['class' => 'action-icon delete']
        );
        $out .= html_writer::end_tag('div');
        return $out;
    }

    /**
     * Get all available options for multi-select modal filter.
     *
     * @return array
     */
    abstract public static function get_item_options(): array;

    /**
     * Return title string for multi-select modal filter
     *
     * @return string[]
     */
    abstract protected static function get_modal_title(): array;

    /**
     * Return css class name for multi-select modal filter
     *
     * @return string
     */
    abstract protected static function get_modal_css(): string;
}