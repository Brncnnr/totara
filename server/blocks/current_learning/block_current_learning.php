<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * @author Alastair Munro <alastair.munro@totaralms.com>
 * @author Simon Player <simon.player@totaralearning.com>
 *
 * @package block_current_learning
 */

defined('MOODLE_INTERNAL') || die();

use totara_core\user_learning\item_helper as learning_item_helper;

/**
 * Current learning block class.
 */
class block_current_learning extends block_base {

    /**
     * The period which when within will lead to a visual alert.
     */
    const DEFAULT_ALERT_PERIOD = WEEKSECS; // One week.

    /**
     * The period which when within will lead to a visual warning.
     */
    const DEFAULT_WARNING_PERIOD = 2592000; // One month. (30 * DAYSECS)

    /**
     * The default view.
     */
    const DEFAULT_VIEW = 'list';

    /**
     * The user id of the user this block is being displayed for.
     * ALWAYS the current user.
     * @var int
     */
    private $userid;

    /**
     * The sortorder for content.
     * @var string
     */
    private $sortorder = 'fullname';

    /**
     * The number of items to display per page.
     * @var int
     */
    private $itemsperpage = 10;

    /**
     * An array of context data - used primarily for unit tests.
     * @var array
     */
    private $contextdata;

    /**
     * Initialises a new block instance.
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_current_learning');
        // NOTE: do NOT initialise $this->>config here, _load_instance() must be executed first!
    }

    /**
     * Set this block to have configuration.
     *
     * @return false
     */
    public function has_config() {
        return false;
    }

    /**
     * The main content for the block.
     *
     * @return \stdClass Object containing the block content.
     */
    public function get_content() {
        global $USER, $CFG;

        if ($this->content !== null) {
            return $this->content;
        }

        // Use defaults for missing config values, this cannot be done earlier.
        if (empty($this->config)) {
            $this->config = new stdClass();
        }

        if (empty($this->config->alertperiod)) {
            $this->config->alertperiod = self::DEFAULT_ALERT_PERIOD;
        }

        if (empty($this->config->warningperiod)) {
            $this->config->warningperiod = self::DEFAULT_WARNING_PERIOD;
        }

        if (empty($this->config->view)) {
            $this->config->view = self::DEFAULT_VIEW;
        }

        $this->content = new stdClass();
        if (empty($this->userid)) {
            // This is the default flow, the userid is typically only set for testing.
            // If it is not set we will use the current user, seeing as it is the current user we will also check that they
            // are logged in, and that they are not the guest user.
            if (!isloggedin() || isguestuser()) {
                return $this->content;
            }
            $this->userid = $USER->id;
        }

        $core_renderer = $this->page->get_renderer('core');

        // Create the learning data.
        $items = $this->get_user_learning_items();

        // Our block content array.
        $contextdata = [
            'instanceid' => $this->instance->id,
            'learningitems' => []
        ];

        $icon_program = new \core\output\flex_icon('program');
        $icon_certification = new \core\output\flex_icon('certification');

        // Create the template data.
        foreach ($items as $item) {
            $itemclass = get_class($item);
            $template = false;

            $singlecourse = false;
            if ($itemclass == 'totara_program\user_learning\item' || $itemclass == 'totara_certification\user_learning\item') {
                $singlecourse = $item->is_single_course();
            }

            switch ($itemclass) {
                case 'core_course\user_learning\item':
                case 'totara_plan\user_learning\course':
                    $template = 'block_current_learning/course_row';
                    break;
                case 'totara_program\user_learning\item':
                case 'totara_certification\user_learning\item':
                    if ($singlecourse) {
                        $template = 'block_current_learning/program_singlecourse_row';
                    } else {
                        $template = 'block_current_learning/program_row';
                    }
                    break;
                default:
                    break;
            }

            // If we don't know the template then we can't render them.
            if ($template !== false) {
                $itemdata = $item->export_for_template();

                // Add block specific display info here for each item.
                // Add status for duetext.
                if ($item instanceof \totara_core\user_learning\item_has_dueinfo && !empty($itemdata->dueinfo)) {
                    $duedate_state = \block_current_learning\helper::get_duedate_state($item->duedate, $this->config);
                    $itemdata->dueinfo->state = $duedate_state['state'];
                    $itemdata->dueinfo->alert = $duedate_state['alert'];
                }

                // Add separate title and icon for programs and certifications (since we use the same template)
                if ($item instanceof \totara_program\user_learning\item) {
                    if ($singlecourse) {
                        $coursename = $singlecourse->fullname;
                        $itemdata->title = get_string('programcontainssinglecourse' , 'block_current_learning', $coursename);
                    } else {
                        $itemdata->title = get_string('thisisaprogram', 'block_current_learning');
                    }
                    $itemdata->icondata = [
                        'context' => $icon_program->export_for_template($core_renderer),
                        'template' => $icon_program->get_template()
                    ];
                }

                if ($item instanceof \totara_certification\user_learning\item) {
                    if ($singlecourse) {
                        $coursename = $singlecourse->fullname;
                        $itemdata->title = get_string('certificationcontainssinglecourse', 'block_current_learning', $coursename);
                    } else {
                        $itemdata->title = get_string('thisisacertification', 'block_current_learning');
                    }
                    $itemdata->icondata = [
                        'context' => $icon_certification->export_for_template($core_renderer),
                        'template' => $icon_certification->get_template()
                    ];
                }

                $itemdata->template = $template;
                $contextdata['learningitems'][] = $itemdata;
            }
        }

        // Set pagination number based on view type
        switch ($this->config->view) {
            case 'list':
                $template = 'block_current_learning/block';
                break;
            case 'tile':
                $template = 'block_current_learning/block_tiles';
                $this->itemsperpage = 6;
                break;
            default:
                $template = 'block_current_learning/block';
        }

        // Create the pagination data if we have items to display.
        if (!empty($contextdata['learningitems'])) {
            $pagination = $this->pagination($contextdata['learningitems']);
            $contextdata['pagination'] = $pagination;
        }

        // The full data.
        $this->contextdata = $contextdata;
        $contextdata['contextdata'] = json_encode($contextdata);

        // The initial view data, limited by itemsperpage.
        $contextdata['learningitems'] = array_slice($contextdata['learningitems'], 0, $this->itemsperpage);
        if (!empty($contextdata['learningitems'])) {
            $contextdata['haslearningitems'] = true;
        } else {
            $rollink = new moodle_url('/totara/plan/record/index.php', array('userid' => $USER->id));
            $contextdata['rollink'] = $rollink->out();
            $contextdata['nocurrentlearning_rol_link'] = get_string('nocurrentlearning', 'block_current_learning', $contextdata['rollink']);
        }

        $this->content->text = $core_renderer->render_from_template($template, $contextdata);

        return $this->content;
    }

    /**
     * Takes an array of user learning instances and ensures no instance appears twice.
     *
     * If more than one are found then the primary for each type is kept.
     *
     * @deprecated since Totara 13.0
     * @param \totara_core\user_learning\item_base[] $items
     * @return \totara_core\user_learning\item_base[]
     */
    private function ensure_user_learning_items_unique(array $items) {
        debugging('block_current_learning->ensure_user_learning_items_unique() is deprecated,
            please use \totara\core\item_helper::ensure_distinct_learning_items() instead.', DEBUG_DEVELOPER);

        return learning_item_helper::ensure_distinct_learning_items($items);
    }

    /**
     * Filters the collective user learning items altering the structure to meet this blocks purpose.
     *
     * @deprecated since Totara 13.0
     * @param \totara_core\user_learning\item_base[] $items
     * @return \totara_core\user_learning\item_base[]
     */
    private function filter_collective_content(array $items) {
        global $DB, $CFG;

        debugging('block_current_learning->filter_collective_content() is deprecated,
            please use \totara\core\item_helper::filter_collective_learning_items() instead.', DEBUG_DEVELOPER);

        return learning_item_helper::filter_collective_learning_items($this->userid, $items);
    }

    /**
     * Combines the data of the separate getters.
     *
     * @return \totara_core\user_learning\item_base[]
     */
    private function get_user_learning_items() {
        global $CFG;

        $items = learning_item_helper::get_users_current_learning_items($this->userid);

        // Expand the items are required to create a specialised list for this block.
        $items = learning_item_helper::expand_learning_item_specialisations($items);

        // Sort the data.
        core_collator::asort_objects_by_property($items, $this->sortorder, core_collator::SORT_NATURAL);

        // Filter the content to exclude duplications, completed courses and other block specific criteria.
        $items = learning_item_helper::filter_collective_learning_items($this->userid, $items);

        return $items;
    }

    /**
     * Expands any item specific user learning item data as required for this block.
     *
     * @deprecated since Totara 13.0
     * @param \totara_core\user_learning\item_base[] $items
     * @return \totara_core\user_learning\item_base[]
     */
    private function expand_item_specialisations(array $items) {
        debugging('block_current_learning->expand_item_specialisations() is deprecated,
            please use \totara\core\item_helper::expand_learning_item_specialisations() instead.', DEBUG_DEVELOPER);

        return learning_item_helper::expand_learning_item_specialisations($items);
    }

    /**
     * Check if totara_program is the only course enrollment for the user
     *
     * @deprecated since Totara 13.0
     * @param \core_course\user_learning\item $item
     * @return bool
     */
    public function only_prog_enrol(\core_course\user_learning\item $item) {
        debugging('block_current_learning->only_prog_enrol() is deprecated,
            please use \totara\core\item_helper::only_prog_enrol() instead.', DEBUG_DEVELOPER);

        return learning_item_helper::only_prog_enrol($this->userid, $item);
    }


    /**
     * Creates the data needed for the pagination template.
     *
     * @param stdClass[] $learning_data An array of learning data context items.
     * @return stdClass A pagination context data object.
     */
    private function pagination(array $learning_data) {

        $data = new stdClass();

        $data->totalitems = count($learning_data);
        $data->itemsperpage = $this->itemsperpage;
        $data->currentpage = 1;
        $data->pages = null;
        $data->text = 0;
        $data->pages = array();

        if ($data->totalitems === 0) {
            return $data;
        }

        // Figure out how many pages we have.
        $pages = (int)ceil($data->totalitems / $this->itemsperpage);

        if ($pages <= 1) {
            $pages = 1;
            $data->onepage = 1;
        }

        $data->nextclass = $data->currentpage == $pages ? 'disabled' : '';
        $data->previousclass = $data->currentpage == 1 ? 'disabled' : '';


        // The display text.
        $data->text = get_string("displayingxofx", "block_current_learning", array(
            'start' => 1,
            'end' => ($data->totalitems < $data->itemsperpage) ? $data->totalitems : $data->itemsperpage,
            'total' => $data->totalitems
        ));

        $pages = range(1, $pages);

        foreach ($pages as $page) {
            $pageinfo = new \stdClass();
            $pageinfo->page = $page;
            $pageinfo->link = '';
            if ($page == $data->currentpage) {
                $pageinfo->active = 'active';
            }
            $data->pages[] = $pageinfo;
        }

        return $data;
    }
}
