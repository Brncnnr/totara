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

use core\orm\collection;
use core\orm\entity\repository;
use totara_evidence\entity;

abstract class totara_evidence_testcase extends advanced_testcase {
    /**
     * @return \totara_evidence\testing\generator
     */
    protected function generator(): \totara_evidence\testing\generator {
        return \totara_evidence\testing\generator::instance();
    }

    protected function items(): collection {
        return entity\evidence_item::repository()->order_by('id')->get();
    }

    protected function type_repository(): entity\evidence_type_repository {
        return entity\evidence_type::repository()
            ->where('idnumber', '<>', 'coursecompletionimport')
            ->where('idnumber', '<>', 'certificationcompletionimport');
    }

    protected function field_repository(): repository {
        return entity\evidence_type_field::repository()
            ->join([entity\evidence_type::TABLE, 'type'], 'typeid', 'id')
            ->where('type.idnumber', '<>', 'coursecompletionimport')
            ->where('type.idnumber', '<>', 'certificationcompletionimport');
    }

    protected function types(): collection {
        return $this->type_repository()->order_by('id')->get();
    }

    protected function fields(): collection {
        return $this->field_repository()->order_by('id')->get();
    }

}
