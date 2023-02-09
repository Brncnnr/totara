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
use totara_evidence\models\helpers\evidence_item_capability_helper;
use totara_evidence\output\header;
use totara_evidence\output\view_item;
use totara_mvc\view;

class item_view extends item {

    protected function check_capability(evidence_item_capability_helper $capability_helper): void {
        $capability_helper->can_view_item(true);
    }

    public function action() {
        parent::action();

        if ($this->is_for_another_user()) {
            $back_label = get_string(
                'navigation_back_to_evidence_bank_for_x',
                'totara_evidence',
                $this->item->user->fullname
            );
        } else {
            $back_label = get_string('navigation_back_to_evidence_bank', 'totara_evidence');
        }

        $title = $this->item->get_display_name();
        $this->get_page()->navbar->add($title);
        $this->set_url($this->apply_return_url(new moodle_url('/totara/evidence/view.php', ['id' => $this->item->get_id()])));

        if ($this->item->can_modify()) {
            $edit_button = [
                'url' => new moodle_url('/totara/evidence/edit.php', [
                    'id' => $this->item->get_id(),
                    'return_to' => self::RETURN_VIEW,
                ]),
                'label' => get_string('edit_this_item', 'totara_evidence')
            ];
        }

        return (new view('totara_evidence/page', [
            'header'  => header::create($title, [
                'url'   => new moodle_url('/totara/evidence/index.php', ['user_id' => $this->item->user_id]),
                'label' => $back_label
            ], $edit_button ?? null),
            'content' => view_item::create_without_name_and_button($this->item)->render(),
        ]))
            ->set_title($title);
    }

}
