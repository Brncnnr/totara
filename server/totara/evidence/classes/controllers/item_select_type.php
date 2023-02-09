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
use totara_evidence\entity\evidence_type;
use totara_evidence\models\helpers\evidence_item_capability_helper;
use totara_evidence\output\header;
use totara_mvc\view;

class item_select_type extends item {

    protected function check_capability(evidence_item_capability_helper $capability_helper): void {
        $capability_helper->can_create(true);
    }

    public function action() {
        parent::action();

        $this->set_url('/totara/evidence/create.php', ['user_id' => $this->user->id]);

        if ($this->is_for_another_user()) {
            $title = get_string(
                'add_evidence_item_for_user', 'totara_evidence',
                $this->user->fullname
            );
            $back_label = get_string(
                'navigation_back_to_evidence_bank_for_x',
                'totara_evidence',
                $this->user->fullname
            );
        } else {
            $title      = get_string('add_an_evidence_item', 'totara_evidence');
            $back_label = get_string('navigation_back_to_evidence_bank', 'totara_evidence');
        }

        $this->get_page()->navbar->add($title);

        return (new view('totara_evidence/page', [
            'header'  => header::create($title, [
                'url'   => new moodle_url('/totara/evidence/index.php', ['user_id' => $this->user->id]),
                'label' => $back_label
            ]),
            'content' => new view('totara_evidence/select_type', [
                'has_types' => evidence_type::repository()->filter_by_active()->exists(),
                'user_id'   => $this->user->id
            ])
        ]))
            ->set_title($title);
    }

}
