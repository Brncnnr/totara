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

use core\orm\formatter\entity_model_formatter;
use core\webapi\formatter\field\string_field_formatter;
use core\webapi\formatter\field\text_field_formatter;
use totara_contentmarketplace\webapi\completion_condition_helper;
use mod_contentmarketplace\model\content_marketplace as model;

class content_marketplace extends entity_model_formatter {
    /**
     * @inheritDoc
     */
    protected function get_map(): array {
        return [
            'id' => null,
            'name' => string_field_formatter::class,
            'course' => null,
            'completion_condition' => function(?int $value): ?string {
                if (empty($value)) {
                    return null;
                }

                return completion_condition_helper::get_enum($value);
            },
            'intro' => function (?string $intro, text_field_formatter $formatter): ?string {
                if (is_null($intro)) {
                    return null;
                }
                $formatter->set_text_format($this->object->introformat);

                $model = model::load_by_id($this->object->id);
                $formatter->set_pluginfile_url_options(
                    $model->get_context(),
                    'mod_contentmarketplace',
                    'intro',
                    null
                );
                return $formatter->format($intro);
            },
        ];
    }
}
