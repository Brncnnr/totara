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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package totara_evidence
 */

namespace totara_evidence\controllers;

use core\notification;
use moodle_url;
use totara_evidence\controllers\helpers\customfield_form_helper;
use totara_evidence\models;
use totara_evidence\models\helpers\evidence_item_capability_helper;
use totara_evidence\output\header;
use totara_mvc\view;

class item_create extends item {

    protected function check_capability(evidence_item_capability_helper $capability_helper): void {
        $capability_helper->can_create(true);
    }

    public function action() {
        parent::action();

        // Check if type is valid
        $id = $this->get_required_param('typeid', PARAM_INT);
        $type = models\evidence_type::load_by_id($id);
        if (!$type->is_visible()) {
            notification::add(
                get_string('error_notification_hidden_type', 'totara_evidence', $type->get_display_name()),
                notification::ERROR
            );
            redirect(new moodle_url('/totara/evidence/create.php', ['user_id' => $this->user->id]));
        }

        // Generate titles and menus based on the user its for
        $title = get_string('page_title_create_item', 'totara_evidence', $type->get_display_name());
        $this->set_url($this->apply_return_url(new moodle_url('/totara/evidence/create.php', [
            'typeid' => $type->get_id(),
            'user_id' => $this->user->id,
        ])));
        if ($this->is_for_another_user()) {
            $navbar_label = get_string(
                'add_evidence_item_for_user', 'totara_evidence',
                $this->user->fullname
            );
            $back_label = get_string(
                'navigation_back_to_evidence_bank_for_x',
                'totara_evidence',
                $this->user->fullname
            );
        } else {
            $navbar_label = get_string('add_an_evidence_item', 'totara_evidence');
            $back_label = get_string('navigation_back_to_evidence_bank', 'totara_evidence');
        }
        $this->get_page()->navbar->add(
            $navbar_label,
            new moodle_url('/totara/evidence/create.php', ['user_id' => $this->user->id])
        )->add($title);

        $return_url = new moodle_url('/totara/evidence/index.php', ['user_id' => $this->user->id]);

        // Render the view
        $view = new view('totara_evidence/page', [
            'content' => customfield_form_helper::get_create_form($type, $this->user, $return_url, $return_url),
            'header'  => header::create($title, [
                'url'   => new moodle_url('/totara/evidence/index.php', ['user_id' => $this->user->id]),
                'label' => $back_label
            ])
        ]);
        $view->set_title($title);
        return $view;
    }

}
