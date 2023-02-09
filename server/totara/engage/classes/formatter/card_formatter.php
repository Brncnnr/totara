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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_engage
 */
namespace totara_engage\formatter;

use core\webapi\formatter\field\string_field_formatter;
use core\webapi\formatter\formatter;
use totara_engage\card\card;
use totara_engage\formatter\field\date_field_formatter;

/**
 * Formatter for the card
 */
final class card_formatter extends formatter {
    /**
     * card_formatter constructor.
     * @param card          $card
     * @param \context|null $context
     */
    public function __construct(card $card, ?\context $context = null) {
        if (null === $context) {
            $context = \context_system::instance();
        }

        $record = new \stdClass();
        $record->instanceid = $card->get_instanceid();
        $record->name = $card->get_name();
        $record->summary = $card->get_summary();
        $record->component = $card->get_component();
        $record->tuicomponent = $card->get_tui_component()->get_name();
        $record->imagetuicomponent = $card->get_card_image_component()->get_name();
        $record->timecreated = $card->get_timecreated();
        $record->timemodified = $card->get_timemodified();

        parent::__construct($record, $context);
    }

    /**
     * @return array
     */
    protected function get_map(): array {
        return [
            'instanceid' => null,
            'component' => null,
            'tuicomponent' => null,
            'imagetuicomponent' => null,
            'name' => string_field_formatter::class,
            'summary' => null,
            'timecreated' => function (int $value, date_field_formatter $formatter): string {
                return $formatter->format($value);
            }
        ];
    }
}