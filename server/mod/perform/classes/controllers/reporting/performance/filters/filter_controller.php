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

namespace mod_perform\controllers\reporting\performance\filters;

use coding_exception;
use context;
use html_writer;
use mod_perform\util;
use mod_perform\controllers\perform_controller;

abstract class filter_controller extends perform_controller {

    /**
     * @inheritDoc
     */
    public function action() {
        throw new coding_exception('Missing action method');
    }

    /**
     * @inheritDoc
     */
    public function setup_context(): context {
        // Whether the user can access this page is based on
        // whether there are any activities the user can report on
        // and not based on the context. The checks for this are based
        // on the users and not on the activiy.
        return util::get_default_context();
    }

    /**
     * Load the totara multi-select dialog modal
     *
     * @return string
     */
    public function action_find() {
        global $CFG;
        require_once($CFG->dirroot . '/totara/core/dialogs/dialog_content.class.php');

        $this->set_url(self::get_url(['action' => 'find']));

        $items = [];
        $options = static::get_item_options();
        foreach ($options as $id => $display_name) {
            $items[] = (object) ['id' => $id, 'name' => $display_name];
        }
        // Load dialog content generator; skip access, since it's checked above
        $dialog = new \totara_dialog_content();
        $dialog->search_code = '/mod/perform/reporting/performance/filters/search.php';
        $dialog->type = \totara_dialog_content::TYPE_CHOICE_MULTI;
        $dialog->items = $items;
        // Set title
        $dialog->selected_title = 'itemstoadd';
        // Setup search
        $dialog->searchtype = static::get_search_type();
        $dialog->urlparams = ['action' => 'find'];
        // Display
        return $dialog->generate_markup();
    }

    /**
     * Select and save selected items from the totara multi-select dialog modal
     *
     * @return string
     */
    public function action_save() {
        $ids = $this->get_required_param('ids', PARAM_TEXT);
        $filtername = $this->get_required_param('filtername', PARAM_ALPHANUMEXT);

        $this->set_url(self::get_url(['action' => 'save', 'ids' => $ids, 'filtername' => $filtername]));

        $ids = explode(',', $ids);
        $out = html_writer::start_div("list-{$filtername}");
        if (!empty($ids)) {
            $options = static::get_item_options();
            foreach ($options as $id => $display_name) {
                if (in_array($id, array_values($ids))) {
                    $out .= static::display_selected_items($id, $display_name, $filtername);
                }
            }
        }
        $out .= html_writer::end_div();
        return $out;
    }

    /**
     * Get an array of options to use for filtering.
     *
     * @return string[] of [plugin_name => Display Name]
     */
    abstract protected static function get_item_options(): array;

    /**
     * Return the search type the totara multi-select dialog modal
     *
     * @return string
     */
    abstract protected static function get_search_type(): string;

    /**
     * Given an options returns the HTML to display it as a filter selection
     *
     * @param string $id
     * @param string $display_name
     * @param string $filtername The identifying name of the current filter
     *
     * @return string HTML to display a selected item
     */
    abstract protected static function display_selected_items($id, $display_name, $filtername): string;
}