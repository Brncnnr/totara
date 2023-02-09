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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package contentmarketplace_linkedin
 */
namespace contentmarketplace_linkedin\api\v2\service\learning_classification\response;

use contentmarketplace_linkedin\api\response\element as base_element;
use contentmarketplace_linkedin\core_json\structure\classification_element;
use contentmarketplace_linkedin\dto\locale;
use stdClass;

/**
 * @method static element create(stdClass $json_data, bool $skip_validation = false)
 */
class element extends base_element {
    /**
     * @return string
     */
    public function get_urn(): string {
        return $this->json_data->urn;
    }

    /**
     * @return string
     */
    protected static function get_json_structure(): string {
        return classification_element::class;
    }

    /**
     * @return locale
     */
    public function get_name_locale(): locale {
        $name = $this->json_data->name;
        $locale_json = $name->locale;

        return new locale(
            $locale_json->language,
            $locale_json->country ?? null
        );
    }

    /**
     * @return string
     */
    public function get_name_value(): string {
        $name = $this->json_data->name;
        return $name->value;
    }

    /**
     * @return string
     */
    public function get_type(): string {
        return $this->json_data->type;
    }
}