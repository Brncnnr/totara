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

use core\entity\user;
use totara_evidence\models;
use totara_evidence\models\helpers\evidence_item_capability_helper;
use totara_reportbuilder\rb;

class rb_evidence_bank_self_embedded extends rb_base_embedded {

    public function __construct($data) {
        $this->url       = '/totara/evidence/index.php';
        $this->source    = 'evidence_item';
        $this->shortname = 'evidence_bank_self';
        $this->fullname  = get_string('title_self', 'rb_source_evidence_item');

        $this->columns = $this->define_columns();
        $this->filters = $this->define_filters();

        // can only view own evidence or users below
        $this->contentmode = REPORT_BUILDER_CONTENT_MODE_ANY;
        $this->contentsettings = [
            'user' => [
                'enable' => 1,
                'who' => rb\content\user::USER_OWN
            ]
        ];

        $this->defaultsortcolumn = 'base_created_at';
        $this->defaultsortorder = SORT_ASC;

        parent::__construct();
    }

    public function embedded_global_restrictions_supported(): bool {
        return true;
    }

    protected function define_columns(): array {
        $columns = [
            [
                'type'    => 'base',
                'value'   => 'name',
                'heading' => null
            ],
            [
                'type'    => 'type',
                'value'   => 'name',
                'heading' => null
            ],
            [
                'type'    => 'base',
                'value'   => 'created_at',
                'heading' => null
            ],
            [
                'type'    => 'creator',
                'value'   => 'name',
                'heading' => null
            ],
            [
                'type'    => 'base',
                'value'   => 'in_use',
                'heading' => null
            ],
            [
                'type'    => 'base',
                'value'   => 'actions',
                'heading' => null
            ],
        ];

        return $columns;
    }

    protected function define_filters(): array {
        $filters = [
            [
                'type'     => 'base',
                'value'    => 'name',
                'advanced' => false
            ],
            [
                'type'     => 'type',
                'value'    => 'name',
                'advanced' => false
            ],
            [
                'type' => 'base',
                'value' => 'source',
                'advanced' => false,
                'defaultvalue' => ['operator' => 1, 'value' => 0],
            ],
            [
                'type'     => 'creator',
                'value'    => 'name',
                'advanced' => true
            ],
            [
                'type'     => 'base',
                'value'    => 'created_at',
                'advanced' => true
            ],
        ];

        return $filters;
    }

    public function is_capable($reportfor, $report): bool {
        return evidence_item_capability_helper::for_user(user::logged_in()->id)->can_view_list();
    }

}
