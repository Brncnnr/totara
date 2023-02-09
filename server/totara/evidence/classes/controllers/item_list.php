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

use core\entity\user;
use moodle_url;
use totara_evidence\entity\evidence_item;
use totara_evidence\models\helpers\evidence_item_capability_helper;
use totara_evidence\output\header;
use totara_mvc\has_report;
use totara_mvc\report_view;
use totara_mvc\view;

class item_list extends item {

    use has_report;

    /**
     * @var bool
     */
    private $can_view_own_items_only;

    /**
     * @var bool
     */
    private $can_create;

    protected function check_capability(evidence_item_capability_helper $capability_helper): void {
        $capability_helper->can_view_list(true);
        $this->can_view_own_items_only = $capability_helper->can_view_own_items_only();
        $this->can_create = $capability_helper->can_create();
    }

    public function action() {
        parent::action();

        $this->set_url('/totara/evidence/index.php', ['user_id' => $this->user->id]);

        if ($this->is_for_another_user()) {
            if (!$this->get_optional_param('user_id', null, PARAM_INT)) {
                // A user hasn't been specified but 'for=other' url param has, so show an error message
                return (new view('totara_evidence/page', [
                    'content' => get_string('report_message_list_types_other', 'totara_evidence')
                ]));
            }

            $report = $this->load_embedded_report('evidence_bank_other', ['user_id' => $this->user->id]);
            $title = get_string('evidence_bank_for_x', 'totara_evidence', $this->user->fullname);
        } else {
            $report = $this->load_embedded_report('evidence_bank_self', ['user_id' => $this->user->id]);
            $title  = get_string('evidence_bank', 'totara_evidence');
        }

        $items = evidence_item::repository()
            ->where('user_id', $this->user->id);
        if ($this->can_view_own_items_only) {
            $items->where('created_by', user::logged_in()->id);
        }
        if ($items->count() > 0) {
            $content = new report_view('totara_evidence/report', $report);
        } else {
            $content = new view('totara_evidence/_message', [
                'message' => get_string('no_evidence_items', 'totara_evidence')
            ]);
        }

        if ($this->can_create) {
            $add_button = [
                'url' => new moodle_url('/totara/evidence/create.php', ['user_id' => $this->user->id]),
                'label' => get_string('add_evidence_item', 'totara_evidence')
            ];
        }

        return (new view('totara_evidence/page', [
            'header' => header::create($title, null, $add_button ?? null),
            'content' => $content
        ]))
            ->set_title($title);
    }

}
