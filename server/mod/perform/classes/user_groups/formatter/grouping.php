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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\user_groups\formatter;

use core\collection;
use core\format;
use core\formatter\user_card_formatter;
use core\formatter\user_card_display_field_formatter;
use core\webapi\formatter\formatter;
use core\webapi\formatter\field\string_field_formatter;
use core_user\profile\card_display;
use core_user\profile\card_display_field;
use mod_perform\user_groups\grouping as grouping_model;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Maps the grouping class into the GraphQL mod_perform_user_grouping type.
 */
class grouping extends formatter {
    /**
     * {@inheritdoc}
     */
    protected function get_map(): array {
        return [
            'id' => null,
            'type' => string_field_formatter::class,
            'type_label' => string_field_formatter::class,
            'name' => string_field_formatter::class,
            'size' => null,
            'extra' => null,
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function get_field(string $field) {
        switch ($field) {
            case 'id':
                return $this->object->get_id();

            case 'type':
                return $this->object->get_type();

            case 'type_label':
                return $this->object->get_type_label();

            case 'name':
                return $this->object->get_name();

            case 'size':
                return $this->object->get_size();

            case 'extra':
                return $this->resolve_extra();
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function has_field(string $field): bool {
        $recognized = [
            'id',
            'type',
            'type_label',
            'name',
            'size',
            'extra'
        ];

        return in_array($field, $recognized);
    }

    /**
     * Resolves the group's extra field.
     *
     * @return string|null a json encoded string or null if there isn't any
     *         custom data.
     */
    private function resolve_extra(): ?string {
        $json_flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT;
        if ($this->object->get_type() === grouping_model::USER) {
            $raw = $this->format_user_card($this->object->get_extra());

            return json_encode($raw, $json_flags);
        }

        return null;
    }

    /**
     * Formats the specified user card.
     *
     * @param card_display $card the card to be formatted.
     *
     * @return array formatted card as an associative array in this format:
     *         [
     *           'profile_picture_url' => ...,
     *           'profile_picture_alt' => ...,
     *           'profile_url' => ...,
     *           'display_fields' => [... see resolve_card_display_fields() below ]
     *         ]
     */
    private function format_user_card(card_display $card): array {
        $formatter = new user_card_formatter($card, $this->context);

        return collection::new(['profile_picture_url', 'profile_picture_alt', 'profile_url'])
            ->reduce(
                function (array $values, string $key) use ($formatter): array {
                    $values[$key] = $formatter->format($key, format::FORMAT_PLAIN);
                    return $values;
                },
                ['display_fields' => $this->format_user_card_display_fields($card)]
            );
    }

    /**
     * Formats the display fields for the given card display.
     *
     * @param card_display $card the card whose display fields are to be formatted.
     *
     * @return array formatted display fields as an array of stdClass objects.
     *         Each stdClass has these fields: label, value, associate_url and
     *         is_custom.
     */
    private function format_user_card_display_fields(card_display $card): array {
        // A user card has multiple card display fields; each *field* has 4 sub values.
        $keys = collection::new(['value', 'label', 'associate_url', 'is_custom']);

        return collection::new($card->get_card_display_fields())
            ->map_to(
                function (card_display_field $field): user_card_display_field_formatter {
                    return new user_card_display_field_formatter($field, $this->context);
                }
            )
            ->map_to(
                function (user_card_display_field_formatter $formatter) use ($keys): stdClass {
                    $display_field = $keys->reduce(
                        function (stdClass $field, string $key) use ($formatter): stdClass {
                            $field->$key = $formatter->format($key, format::FORMAT_PLAIN);
                            return $field;
                        },
                        new stdClass()
                    );

                    return $display_field;
                }
            )
            ->filter(
                function (stdClass $field): bool {
                    // Fields that do not have labels can't be identified by the
                    // caller; so need might as well weed them out.
                    return !empty($field->label);
                }
            )
            ->all();
    }
}