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

use moodle_url;
use totara_evidence\controllers\helpers\customfield_form_helper;
use totara_evidence\models\helpers\evidence_item_capability_helper;
use totara_evidence\output\header;
use totara_mvc\view;

class item_edit extends item {

    protected function check_capability(evidence_item_capability_helper $capability_helper): void {
        $capability_helper->can_modify(true);
    }

    protected function authorize(): void {
        parent::authorize();

        if ($this->item->in_use()) {
            print_error(
                'error_notification_edit_item',
                'totara_evidence',
                $this->get_return_url($this->item),
                $this->item->get_display_name()
            );
        }
    }

    public function action() {
        parent::action();

        // Generate titles and menus based on the user its for
        if ($this->is_for_another_user()) {
            $back_label = get_string(
                'navigation_back_to_evidence_bank_for_x',
                'totara_evidence',
                $this->item->user->fullname
            );
        } else {
            $back_label = get_string('navigation_back_to_evidence_bank', 'totara_evidence');
        }
        $title = get_string('edit_x', 'totara_evidence', $this->item->get_display_name());
        $this->set_url($this->apply_return_url(new moodle_url('/totara/evidence/edit.php', ['id' => $this->item->get_id()])));
        $this->get_page()->navbar->add($title);

        // Render the view
        $view = new view('totara_evidence/page', [
            'content' => customfield_form_helper::get_edit_form($this->item),
            'header'  => header::create($title, [
                'url'   => new moodle_url('/totara/evidence/index.php', ['user_id' => $this->user->id]),
                'label' => $back_label
            ])
        ]);
        $view->set_title($title);
        return $view;
    }

}
