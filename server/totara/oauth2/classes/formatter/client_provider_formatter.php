<?php
/**
 * This file is part of Totara Core
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
 * @package totara_oauth2
 */

namespace totara_oauth2\formatter;
use core\orm\formatter\entity_model_formatter;
use core\webapi\formatter\field\string_field_formatter;
use core\webapi\formatter\field\text_field_formatter;

class client_provider_formatter extends entity_model_formatter {
    /**
     * @return array
     */
    protected function get_map(): array {
        return [
            'name' => string_field_formatter::class,
            'description' => function (?string $description, text_field_formatter $formatter): string {
                if (is_null($description)) {
                    return '';
                }

                $formatter->disabled_pluginfile_url_rewrite();
                $formatter->set_text_format($this->object->description_format);
                return $formatter->format($description);
            },
            'client_secret' => null,
            'client_id' => null,
            'id' => null,
            'scope' => null,
            'detail_scope' => null
        ];
    }
}