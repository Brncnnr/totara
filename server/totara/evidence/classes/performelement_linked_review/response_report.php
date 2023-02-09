<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_evidence
 */

namespace totara_evidence\performelement_linked_review;

use performelement_linked_review\rb\helper\content_type_response_report;
use rb_join;

class response_report implements content_type_response_report {

    /**
     * @inheritDoc
     */
    public function get_content_joins(): array {
        return [
            new rb_join(
                'totara_evidence_item',
                'LEFT',
                '{totara_evidence_item}',
                "linked_review_content.content_id = totara_evidence_item.id 
                    AND linked_review_content.content_type = 'totara_evidence'",
                REPORT_BUILDER_RELATION_MANY_TO_ONE,
                'linked_review_content'
            ),
        ];
    }

    /**
     * @inheritDoc
     */
    public function get_content_name_field(): string {
        return 'totara_evidence_item.name';
    }

}