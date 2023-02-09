<?php
/*
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
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_facetoface
 */

global $CFG;

require_once($CFG->dirroot . '/totara/core/dialogs/dialog_content.class.php');
require_once($CFG->dirroot . '/lib/outputcomponents.php');

defined('MOODLE_INTERNAL') || die();

class seminar_dialog_content extends \totara_dialog_content {

    /**
     * @var null id for create tab
     * @uses /mod/facetoface/thing/ajax/sessionthings.php
     */
    public $createid = null;

    /**
     * @var null url for search tab
     * @uses /mod/facetoface/thing/ajax/sessionthings.php
     */
    public $baseurl = null;

    /**
     * @var bool manageadhocthing
     */
    public $manageadhoc = false;

    /**
     * Generate markup from configuration and return
     *
     * @access  public
     * @return  string  $markup Markup to print
     */
    public function generate_markup() {
        global $OUTPUT;

        header('Content-Type: text/html; charset=UTF-8');

        // Skip container if only displaying search results
        if (optional_param('search', false, PARAM_BOOL)) {
            return $this->generate_search();
        }
        // Skip container if only displaying treeview
        if ($this->show_treeview_only) {
            return $this->generate_treeview();
        }

        $markup = html_writer::start_tag('div', array('class' => 'row-fluid seminar_dialog_content_title'));

        // Open select container
        $width = ($this->type == self::TYPE_CHOICE_MULTI) ? 'span8' : 'span12';
        $markup .= html_writer::start_tag('div', array('class' => $width . ' select'));

        // Show select header
        if (!empty($this->select_title)) {
            $markup .= $OUTPUT->heading(get_string($this->select_title, $this->lang_file), 3);
        }

        $markup .= html_writer::start_tag('div', array('id' => 'dialog-tabs', 'class' => 'dialog-content-select'));

        $tabs[] = html_writer::link('#browse-tab', get_string('browse', 'totara_core'));
        $markup .= html_writer::start_tag('div', array('class' => 'tabtree'));
        if (!empty($this->search_code)) {
            $tabs[] = html_writer::link('#search-tab', get_string('search'));
        }
        if ($this->manageadhoc) {
            $tabs[] = html_writer::link('#create-tab', get_string('create'), ['id' => $this->createid]);
        }
        $markup .= html_writer::alist($tabs, ['class' => 'nav nav-tabs dialog-nobind']);
        $markup .= html_writer::end_div();

        // Display treeview
        $markup .= html_writer::start_div('', ['id' => 'browse-tab']);

        // Display any custom markup
        if (method_exists($this, '_prepend_markup')) {
            $markup .= $this->_prepend_markup();
        }

        $markup .= $this->generate_treeview();
        $markup .= html_writer::end_div();

        if (!empty($this->search_code)) {
            // Display searchview
            $markup .= html_writer::start_div('dialog-load-within', ['id' => 'search-tab']);
            $markup .= $this->generate_search();
            $markup .= html_writer::tag('div', '', ['id' => 'search-results']);
            $markup .= html_writer::end_div();
        }

        // Close select container
        $markup .= html_writer::end_tag('div');
        $markup .= html_writer::end_tag('div');

        // If multi-select, show selected pane
        if ((int)$this->type === (int)self::TYPE_CHOICE_MULTI) {
            $markup .= html_writer::start_tag('div', array('class' => 'span4 selected dialog-nobind', 'id' => $this->selected_id));

            // Show title
            if (!empty($this->selected_title)) {
                $markup .= html_writer::tag('h4', get_string($this->selected_title, $this->lang_file));
            }

            // Populate pane
            $markup .= $this->populate_selected_items_pane($this->selected_items);

            $markup .= html_writer::end_tag('div');
        }

        // Close container for content
        $markup .= html_writer::end_tag('div');

        return $markup;
    }
}