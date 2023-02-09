<?php
/**
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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package totara_evidence
 */

namespace totara_evidence\controllers;

use moodle_url;
use totara_evidence\entity\evidence_type;
use totara_evidence\models\evidence_type as evidence_type_model;
use totara_evidence\output\header;
use totara_mvc\has_report;
use totara_mvc\report_view;
use totara_mvc\view;

class type_list extends type {

    use has_report;

    protected $url = '/totara/evidence/type/index.php';

    public function action() {
        if (evidence_type::repository()->count() > 0) {
            $report = $this->load_embedded_report('evidence_type');
            $content = new report_view('totara_evidence/report', $report);
        } else {
            $content = new view('totara_evidence/_message', [
                'message' => get_string('no_evidence_types', 'totara_evidence')
            ]);
        }

        $button = null;
        if (evidence_type_model::can_manage()) {
            $button = [
                'url'   => new moodle_url('/totara/evidence/type/create.php'),
                'label' => get_string('add_evidence_type', 'totara_evidence')
            ];
        }

        return (new view('totara_evidence/page', [
            'header' => header::create(get_string('manage_evidence_types', 'totara_evidence'), null, $button),
            'content' => $content
        ]))
            ->set_title(get_string('manage_evidence_types', 'totara_evidence'));
    }
}
