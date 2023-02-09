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

use totara_evidence\models;

class rb_evidence_type_embedded extends rb_base_embedded {

    public function __construct() {
        $this->url       = '/totara/evidence/type/index.php';
        $this->source    = 'evidence_type';
        $this->shortname = 'evidence_type';
        $this->fullname  = get_string('title', 'rb_source_evidence_type');

        $this->columns = $this->define_columns();

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
                'type'    => 'base',
                'value'   => 'idnumber',
                'heading' => null
            ],
            [
                'type'    => 'base',
                'value'   => 'created_at',
                'heading' => null,
                'hidden'  => true
            ],
            [
                'type'    => 'base',
                'value'   => 'forusein',
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

    public function is_capable($reportfor, $report): bool {
        return models\evidence_type::can_manage();
    }

}
