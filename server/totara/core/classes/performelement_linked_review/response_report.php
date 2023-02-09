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
 * @package totara_core
 */

namespace totara_core\performelement_linked_review;

use performelement_linked_review\rb\helper\content_type_response_report;
use rb_join;

class response_report implements content_type_response_report {

    /**
     * @inheritDoc
     */
    public function get_content_joins(): array {

        return [
            new rb_join(
                'learning_course',
                'LEFT',
                '{course}',
                "linked_review_content.content_id = learning_course.id
                     AND linked_review_content.content_type = 'learning_course'",
                REPORT_BUILDER_RELATION_MANY_TO_ONE,
                'linked_review_content'
            ),
            new rb_join(
                'learning_program',
                'LEFT',
                '{prog}',
                "linked_review_content.content_id = learning_program.id
                    AND (linked_review_content.content_type = 'learning_program' OR linked_review_content.content_type = 'learning_certification')",
                REPORT_BUILDER_RELATION_MANY_TO_ONE,
                'linked_review_content'
            ),
        ];
    }

    /**
     * @inheritDoc
     */
    public function get_content_name_field(): string {
        return "CASE WHEN linked_review_content.content_type = 'learning_course' THEN learning_course.fullname ELSE learning_program.fullname END";
    }

}