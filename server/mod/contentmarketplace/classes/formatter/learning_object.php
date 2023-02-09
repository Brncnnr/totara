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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package mod_contentmarketplace
 */

namespace mod_contentmarketplace\formatter;

use context;
use core\orm\formatter\entity_model_formatter;
use core\webapi\formatter\field\string_field_formatter;
use core\webapi\formatter\field\text_field_formatter;
use totara_contentmarketplace\learning_object\abstraction\metadata\detailed_model;
use totara_contentmarketplace\learning_object\abstraction\metadata\model;
use totara_contentmarketplace\learning_object\text;

/**
 * This formatter should only work with the instance of {@see model}
 */
class learning_object extends entity_model_formatter {
    /**
     * @param string $field
     * @return bool
     */
    protected function has_field(string $field): bool {
        if ('description' === $field) {
            return true;
        }

        return parent::has_field($field);
    }

    /**
     * @param string $field
     * @return mixed|null
     */
    protected function get_field(string $field) {
        if ('description' === $field) {
            if ($this->object instanceof detailed_model) {
                return $this->object->get_description();
            }

            return null;
        }

        return parent::get_field($field);
    }

    /**
     * @inheritDoc
     */
    protected function get_map(): array {
        return [
            'id' => null,
            'name' => string_field_formatter::class,
            'language' => null,
            'image_url' => null,
            'description' => function (?text $text, text_field_formatter $formatter): ?string {
                if (null === $text) {
                    return null;
                }

                $formatter->disabled_pluginfile_url_rewrite();
                $formatter->set_text_format($text->get_format());

                return $formatter->format($text->get_raw_value());
            }
        ];
    }
}