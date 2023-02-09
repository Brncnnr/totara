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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package contentmarketplace_linkedin
 */

namespace contentmarketplace_linkedin\formatter;

use core\orm\formatter\entity_model_formatter;
use core\webapi\formatter\field\date_field_formatter;
use core\webapi\formatter\field\string_field_formatter;

class learning_object extends entity_model_formatter {

    /**
     * @return array
     */
    protected function get_map(): array {
        return [
            'id' => null,
            'name' => string_field_formatter::class,
            'description' => string_field_formatter::class,
            'description_include_html' => string_field_formatter::class,
            'short_description' => string_field_formatter::class,
            'last_updated_at' => date_field_formatter::class,
            'published_at' => date_field_formatter::class,
            'level' => null,
            'display_level' => null,
            'time_to_complete' => timespan_field_formatter::class,
            'asset_type' => null,
            'language' => null,
            'image_url' => null,
            'classifications' => null,
            'subjects' => null,
            'courses' => null,
            'web_launch_url' => null,
            'sso_launch_url' => null
        ];
    }

}
