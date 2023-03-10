<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_core
 */

namespace totara_core\form\menu;

use \totara_core\totara\menu\item;
use \totara_core\totara\menu\helper;

defined('MOODLE_INTERNAL') || die();

/**
 * Form to update custom item.
 */
final class update_custom_item extends \totara_form\form {
    public function definition() {
        /** @var item $node */
        $node = $this->get_parameters()['item'];

        $options = $this->get_parameters()['parentidoptions'];
        $parentid = new \totara_form\form\element\select('parentid', get_string('menuitem:formitemparent', 'totara_core'), $options);
        $this->model->add($parentid);

        $title = new \totara_form\form\element\text('title', get_string('menuitem:formitemtitle', 'totara_core'), PARAM_TEXT);
        $title->add_help_button('menuitem:formitemtitle', 'totara_core');
        $title->set_attributes(array('required'=> 1, 'maxlength' => 1024, 'size' => 100));
        $this->model->add($title);

        $options = array(
            item::VISIBILITY_SHOW => get_string('menuitem:show', 'totara_core'),
            item::VISIBILITY_HIDE => get_string('menuitem:hide', 'totara_core'),
            item::VISIBILITY_CUSTOM => get_string('menuitem:showcustom', 'totara_core'),
        );
        $visibility = new \totara_form\form\element\radios('visibility', get_string('menuitem:formitemvisibility', 'totara_core'), $options);
        $this->model->add($visibility);

        // This is not a real URL, we need to sanitise it before display.
        $url = new \totara_form\form\element\text('url', get_string('menuitem:formitemurl', 'totara_core'), PARAM_RAW);
        $url->add_help_button('menuitem:formitemurl', 'totara_core');
        $url->set_attributes(array('required'=> 1, 'maxlength' => 1333));
        $this->model->add($url);

        $targetattr = new \totara_form\form\element\checkbox('targetattr', get_string('menuitem:formitemtargetattr', 'totara_core'), '_blank', '_self');
        $targetattr->add_help_button('menuitem:formitemtargetattr', 'totara_core');
        $this->model->add($targetattr);

        $this->model->add_action_buttons(true, get_string('savechanges'));

        $this->model->add(new \totara_form\form\element\hidden('id', PARAM_INT));
    }

    /**
     * Validation.
     *
     * @param array $data
     * @param array $files
     * @return array list of errors
     */
    public function validation(array $data, array $files) {
        $errors = parent::validation($data, $files);

        $urlerror = helper::validate_item_url($data['url']);
        if ($urlerror !== null) {
            $errors['url'] = $urlerror;
        }

        if (trim($data['title']) === '') {
            $errors['title'] = get_string('required');
        }

        return $errors;
    }
}
