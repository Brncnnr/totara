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
namespace contentmarketplace_linkedin\data_provider;

use contentmarketplace_linkedin\dto\locale;
use core\orm\collection;
use core\orm\query\builder;
use contentmarketplace_linkedin\entity\learning_object;

/**
 * A data provider class for the available locales in the system.
 */
class locales {
    /**
     * The query builder that is used to fetch the locales from
     * linkedin's learning objects.
     *
     * @var builder
     */
    private $builder;

    /**
     * @var array
     */
    private static $default_locales;

    /**
     * locales constructor.
     */
    public function __construct() {
        // Construct the builder.
        $this->builder = builder::table(learning_object::TABLE);
        $this->builder->select_raw("DISTINCT locale_language, locale_country");
        $this->builder->results_as_arrays();
        $this->builder->map_to([self::class, "create_locale"]);
        $this->builder->order_by('locale_language');
    }

    /**
     * @return locale[]
     */
    public function get(): array {
        return $this->builder->fetch(true);
    }

    /**
     * Please do not use this outside publicly. It was set for public accessible
     * to serve the query builder API only.
     *
     * @internal
     *
     * @param array $data
     * @return locale
     */
    public static function create_locale(array $data): locale {
        return new locale($data["locale_language"], $data["locale_country"]);
    }

    /**x
     * Returns the list of hardcoded locales, which are wrapped in {@see locale}.
     * Please note that this function returns the hard-coded list of available locales,
     * which allows us to perform the sync classifications.
     *
     * To get the list of available locales within the system, please prefer to
     * {@see locales::get()}
     *
     * @return locale[]
     */
    public static function get_default(): array {
        if (!isset(self::$default_locales)) {
            self::$default_locales = self::do_get_default();
        }

        return self::$default_locales;
    }

    /**
     * This function returns the static locales that are supported by the linkedin side only.
     * Please use this for resting tests only.
     *
     * @return locale[]
     */
    protected static function do_get_default(): array {
        return [
            new locale("en", "US"),
            new locale("pt", "BR"),
            new locale("de", "DE"),
            new locale("es", "ES"),
            new locale("fr", "FR"),
            new locale("zh", "CN"),
            new locale("ja", "JP"),
            new locale("nl", "NL"),
            new locale("it", "IT"),
            new locale("pl", "PL"),
            new locale("tr", "TR"),
        ];
    }
}