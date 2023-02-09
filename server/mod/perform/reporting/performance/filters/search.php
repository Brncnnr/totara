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

defined('TOTARA_DIALOG_SEARCH') || die();

/** @var \core_config $CFG */
require_once($CFG->dirroot . '/totara/core/dialogs/search_form.php');
require_once($CFG->dirroot . '/totara/core/searchlib.php');
require_once($CFG->dirroot . '/mod/perform/classes/rb/filter/element_type.php');
require_once($CFG->dirroot . '/mod/perform/element/linked_review/classes/rb/filter/review_type.php');
require_once($CFG->dirroot . '/mod/perform/classes/rb/filter/relationship_name.php');
require_once($CFG->dirroot . '/mod/perform/classes/rb/filter/section_titles_by_activity.php');

use mod_perform\rb\filter\element_type as rb_filter_element_type;
use mod_perform\rb\filter\relationship_name as rb_filter_relationship_name;
use mod_perform\rb\filter\section_titles_by_activity as rb_filter_section_title;
use performelement_linked_review\rb\filter\review_type as rb_filter_review_type;

global $DB, $OUTPUT, $USER, $PAGE;

// Get parameter values
$query = optional_param('query', '', PARAM_TEXT); // search query
$page = optional_param('page', 0, PARAM_INT); // results page number
$searchtype = $this->searchtype;

// Trim whitespace off search query
$query = trim($query);

// This url
$data = [
    'search'        => true,
    'query'         => $query,
    'searchtype'    => $searchtype,
    'page'          => $page,
    'sesskey'       => sesskey()
];
$thisurl = new moodle_url(strip_querystring(qualified_me()), array_merge($data, $this->urlparams));

// Extra form data
$formdata = [
    'hidden'        => $this->urlparams,
    'query'         => $query,
    'searchtype'    => $searchtype,
];

$search_info = new stdClass();
$search_info->id = 'id';
$search_info->fullname = 'fullname';
$search_info->sql = null;
$search_info->params = null;
$search_info->extrafields = null;

// Check if user has capability to view emails.
if (isset($this->context)) {
    $context = $this->context;
} else {
    $context = context_system::instance();
}

// Generate form markup.
$mform = new dialog_search_form(null, $formdata);
// Display form
$mform->display();

// Generate results
if (strlen($query)) {
    $items = [];
    switch ($searchtype) {
        case 'element_type':
            $options = rb_filter_element_type::get_item_options();
            foreach ($options as $id => $display_name) {
                if (stripos($display_name, $query) !== false) {
                    $items[] = (object) ['id' => $id, 'name' => $display_name];
                }
            }
            break;
        case 'review_type':
            $options = rb_filter_review_type::get_item_options();
            foreach ($options as $id => $display_name) {
                if (stripos($display_name, $query) !== false) {
                    $items[] = (object) ['id' => $id, 'name' => $display_name];
                }
            }
            break;
        case 'relationship_name':
            $options = rb_filter_relationship_name::get_item_options();
            foreach ($options as $id => $display_name) {
                if (stripos($display_name, $query) !== false) {
                    $items[] = (object) ['id' => $id, 'name' => $display_name];
                }
            }
            break;
        case 'section_title':
            $options = rb_filter_section_title::get_item_options();
            foreach ($options as $id => $display_name) {
                if (stripos($display_name, $query) !== false) {
                    $items[] = (object) ['id' => $id, 'name' => $display_name];
                }
            }
            break;
        default:
            print_error('invalidsearchtable', 'totara_core');
    }

    if (!empty($items)) {
        $pagingbar = new paging_bar(count($items), $page, DIALOG_SEARCH_NUM_PER_PAGE, $thisurl);
        $pagingbar->pagevar = 'page';

        $output = $OUTPUT->render($pagingbar);
        echo html_writer::tag('div',$output, ['class' => 'search-paging']);

        // Generate some treeview data
        $dialog = new totara_dialog_content();
        $dialog->parent_items = [];
        $dialog->set_context($context);
        $dialog->items = $items;

        $dialog->disabled_items = $this->disabled_items;
        echo $dialog->generate_treeview();
    } else {
        $message = get_string('noresultsfor', 'totara_core', (object)['query' => $query]);
        echo html_writer::tag('p', $message, ['class' => 'message']);
    }
}
