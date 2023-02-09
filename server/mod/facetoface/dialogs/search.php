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

defined('TOTARA_DIALOG_SEARCH') || die();

require_once($CFG->dirroot . '/totara/core/searchlib.php');
require_once($CFG->dirroot . '/totara/core/dialogs/search_form.php');
require_once($CFG->dirroot . '/mod/facetoface/dialogs/seminar_dialog_content.php');

global $OUTPUT;

// Get parameter values
$query      = optional_param('query', null, PARAM_TEXT); // search query
$page       = optional_param('page', 0, PARAM_INT); // results page number
$searchtype = $this->searchtype;

// Trim whitespace off search query
// Prevent passing null as input parameter in PHP 8.1
$query = trim($query ?? '');
// Extra form data
$formdata = array(
    'hidden'        => $this->customdata,
    'query'         => $query,
    'searchtype'    => $searchtype
);
$mform = new \mod_facetoface\form\dialog_search(null, $formdata);
// Display form
$mform->display();

// Generate results
if (strlen($query)) {

    if (!$this->items) {
        $params['query'] = $query;
        $message = get_string('noresultsfor', 'totara_core', (object)$params);
        echo html_writer::tag('p', $message, array('class' => 'message'));
        return;
    }

    $founditems = [];
    // Why strtoupper()? From PHP 7.3, it is parsing the special characters more accurate, as sample ß == ss in German,
    // so searching for "STRASSE" doesn't pick a room named "Straße" up if PHP is lower than 7.2
    // In this case we use strtoupper() instead of strtolower() as it will not parse as user is expecting.
    $needle = \core_text::strtoupper($query);
    // Generate some treeview data
    foreach ($this->items as $result) {

        $haystack = \core_text::strtoupper($result->fullname);
        if (\core_text::strpos($haystack, $needle) === false) {
             continue;
        }
        // Add datakey attributes to item.
        $item = new stdClass();
        foreach ($this->datakeys as $key) {
            $item->$key = $result->$key;
        }
        $item->id = $result->id;
        $item->fullname = format_string($result->fullname);

        $founditems[$item->id] = $item;
    }

    if (count($founditems) > 0) {
        $total = count($founditems);
        // Support pagination.
        $data = [
            'search'        => true,
            'query'         => $query,
            'searchtype'    => $searchtype,
            'page'          => $page,
            'sesskey'       => sesskey()
        ];
        $baseurl = new moodle_url($this->baseurl, array_merge($data, $this->urlparams));
        $start = $page * DIALOG_SEARCH_NUM_PER_PAGE;
        $items = array_slice($founditems, $start, DIALOG_SEARCH_NUM_PER_PAGE);
        $output = $OUTPUT->render(new \paging_bar($total, $page, DIALOG_SEARCH_NUM_PER_PAGE, $baseurl));
        echo html_writer::tag('div', $output, array('class' => "search-paging"));

        $dialog = new \seminar_dialog_content();
        $dialog->items = array();
        $dialog->parent_items = array();
        $dialog->searchtype = $this->searchtype;
        $dialog->set_datakeys($this->datakeys);

        $dialog->items = $items;
        $dialog->disabled_items = $this->disabled_items;
        echo $dialog->generate_treeview();
    } else {
        $params['query'] = $query;
        $message = get_string('noresultsfor', 'totara_core', (object)$params);
        echo html_writer::tag('p', $message, array('class' => 'message'));
    }
}
