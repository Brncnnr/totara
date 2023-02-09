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
use totara_form\form\element\textarea;
use totara_form\form\group\buttons;
use totara_form\form\element\action_button;

/**
 * This form is meaning for adding topic(s) only. If editting a topic's value, please use {@see topic_form}
 */
final class bulk_topic_form extends form {
    /**
     * @return void
     */
    protected function definition(): void {
        /** @var section $section */
        $section = $this->model->add(new section('bulktopic', get_string('bulkadd', 'totara_topic')));
        $section->set_collapsible(false);

        $element = $section->add(new textarea('topics', get_string('entertopics', 'totara_topic'), PARAM_RAW));
        $element->set_attribute('required', true);
        $element->set_attribute('rows', 10);
        $element->set_attribute('cols', 10);

        $group = $this->model->add(new buttons('actionbuttonsgroup'), -1);
        $group->add(new action_button("submitbutton", get_string('add', 'totara_topic'), action_button::TYPE_SUBMIT));
        $group->add(new action_button('cancelbutton', get_string('cancel', 'moodle'), action_button::TYPE_CANCEL));
    }
}
