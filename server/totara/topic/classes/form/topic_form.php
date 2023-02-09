<?php
/**
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_topic
 */
namespace totara_topic\form;

use totara_form\form;
use totara_form\form\group\section;
use totara_form\form\element\text;
use totara_form\form\element\hidden;
use totara_form\form\element\action_button;
use totara_form\form\group\buttons;

/**
 * Topic form only available for editing a singular topic's value only.
 * For adding new topic(s) please use {@see bulk_topic_form}.
 */
class topic_form extends form {
    /**
     * @return void
     */
    protected function definition(): void {
        $currentdata = $this->model->get_current_data(null);

        // Changing the label of the form accordingly
        if (!isset($currentdata['id']) || 0 == $currentdata['id']) {
            throw new \coding_exception("Cannot initialise a form that does not have an id of the topic");
        }

        /** @var section $section */
        $section = $this->model->add(new section('topic', get_string('edittopic', 'totara_topic')));
        $section->set_collapsible(false);

        $section->add(new hidden('id', PARAM_INT));

        $text = $section->add(new text('value', get_string('value', 'totara_topic'), PARAM_RAW));
        $text->set_attribute('required', true);

        $group = $this->model->add(new buttons('actionbuttonsgroup'), -1);
        $group->add(
            new action_button(
                'submitbutton',
                get_string('save', 'totara_topic'),
                action_button::TYPE_SUBMIT
            )
        );

        $group->add(new action_button('cancelbutton', get_string('cancel', 'moodle'), action_button::TYPE_CANCEL));
    }

    /**
     * @return \moodle_url
     */
    public function get_action_url(): \moodle_url {
        global $PAGE;
        $current = parent::get_action_url();

        if (!AJAX_SCRIPT) {
            // We should not trust the page url. Though, we can check if there is a back parameter in
            // the page url or not to redirect to where origin was.
            $url = $PAGE->url;
            $back = $url->get_param('back');

            if (null != $back) {
                $current->param('back', $back);
            }
        }

        return $current;
    }
}