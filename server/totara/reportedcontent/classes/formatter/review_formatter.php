<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @package totara_reportedcontent
 */

namespace totara_reportedcontent\formatter;

use core\webapi\formatter\field\date_field_formatter;
use core\webapi\formatter\field\text_field_formatter;
use core\webapi\formatter\formatter;
use totara_reportedcontent\review;

/**
 * Class review_formatter
 *
 * @package totara_reportedcontent\formatter
 */
class review_formatter extends formatter {
    /**
     * review_formatter constructor.
     *
     * @param review $review
     */
    public function __construct(review $review) {
        $context_id = $review->get_context_id();
        $context = \context::instance_by_id($context_id);

        $record = static::to_record($review);
        parent::__construct($record, $context);
    }

    /**
     * @param review $review
     * @return \stdClass
     */
    protected static function to_record(review $review): \stdClass {
        $record = new \stdClass();

        $record->id = $review->get_id();
        $record->url = $review->get_url();
        $record->content = $review->get_content();
        $record->format = $review->get_format();
        $record->time_created = $review->get_time_created();
        $record->time_content = $review->get_time_content();
        $record->time_reviewed = $review->get_time_reviewed();
        $record->time_reviewed_description = $review->get_time_reviewed();
        $record->item_id = $review->get_item_id();
        $record->context_id = $review->get_context_id();
        $record->component = $review->get_component();
        $record->area = $review->get_area();
        $record->target_user = $review->get_target_user();
        $record->complainer = $review->get_complainer();
        $record->reviewer = $review->get_reviewer();
        $record->status = $review->get_status();
        $record->approved = $review->get_status();
        $record->removed = $review->get_status();

        return $record;
    }

    /**
     * @return array
     */
    protected function get_map(): array {
        $that = $this;
        return [
            'target_user' => null,
            'complainer' => null,
            'reviewer' => null,
            'id' => null,
            'url' => null,
            'content' => function (?string $content, text_field_formatter $formatter) use ($that): string {
                if (null === $content) {
                    return '';
                }

                $formatter->set_pluginfile_url_options(
                    $that->context,
                    $that->object->component,
                    $that->object->area
                );

                $formatter->set_text_format($that->object->format);
                return $formatter->format($content);
            },
            'time_created' => null,
            'time_content' => null,
            // time_reviewed is deprecated, use time_reviewed_description instead
            'time_reviewed' => null,
            'time_reviewed_description' => date_field_formatter::class,
            'item_id' => null,
            'context_id' => null,
            'component' => null,
            'area' => null,
            'status' => null,
            'approved' => function ($status) use ($that): bool {
                return $status === review::DECISION_APPROVE;
            },
            'removed' => function ($status) use ($that): bool {
                return $status === review::DECISION_REMOVE;
            },
        ];
    }
}