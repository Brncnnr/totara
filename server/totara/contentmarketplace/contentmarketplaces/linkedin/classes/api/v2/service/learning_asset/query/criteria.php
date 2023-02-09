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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package contentmarketplace_linkedin
 */
namespace contentmarketplace_linkedin\api\v2\service\learning_asset\query;

use coding_exception;
use contentmarketplace_linkedin\api\v2\service\helper;
use contentmarketplace_linkedin\constants;
use contentmarketplace_linkedin\dto\locale;
use moodle_url;

class criteria extends query {
    /**
     * An array of constants ASSET_TYPE_* .
     *
     * @var string[]
     */
    private $asset_types;

    /**
     * An array of classification URNs.
     *
     * @var string[]
     */
    private $classifications;

    /**
     * @var string[]
     */
    private $difficulty_levels;

    /**
     * @var locale[]
     */
    private $locales;

    /**
     * criteria constructor.
     */
    public function __construct() {
        parent::__construct();

        $this->asset_types = [];
        $this->classifications = [];
        $this->difficulty_levels = [];
        $this->locales = [];

        $this->set_scalar_parameter('q', 'criteria');
    }

    /**
     * @param string $asset_type
     * @return void
     */
    public function add_asset_type(string $asset_type): void {
        constants::validate_asset_type($asset_type);

        $this->asset_types[] = $asset_type;
    }

    /**
     * @param string[] $asset_types
     * @return void
     */
    public function set_asset_types(array $asset_types): void {
        $this->asset_types = [];

        foreach ($asset_types as $asset_type) {
            $this->add_asset_type($asset_type);
        }
    }

    /**
     * @param string $level
     * @return void
     */
    public function add_difficulty_level(string $level): void {
        constants::validate_difficulty_level($level);

        $this->difficulty_levels[] = $level;
    }

    /**
     * Set the array of difficulty levels.
     * @param string[] $difficulty_levels
     * @return void
     */
    public function set_difficulty_levels(array $difficulty_levels): void {
        $this->difficulty_levels = [];

        foreach ($difficulty_levels as $difficulty_level) {
            $this->add_difficulty_level($difficulty_level);
        }
    }

    /**
     * @param string|null $sort_by
     * @return void
     */
    public function set_sort_by(?string $sort_by): void {
        if (null !== $sort_by && !constants::is_valid_sort_by($sort_by)) {
            throw new coding_exception("Invalid sort by: {$sort_by}");
        }

        $this->set_scalar_parameter('assetPresentationCriteria.sortBy', $sort_by);
    }

    /**
     * @param int|null $start
     * @return void
     */
    public function set_start(?int $start): void {
        $this->set_scalar_parameter('start', $start);
    }

    /**
     * @param int|null $count
     * @return void
     */
    public function set_count(?int $count): void {
        $this->set_scalar_parameter('count', $count);
    }

    /**
     * @param bool|null $value
     */
    public function set_licensed_only(?bool $value): void {
        $this->set_scalar_parameter('assetFilteringCriteria.licensedOnly', $value);
    }

    /**
     * @param int|null $value
     * @return void
     */
    public function set_last_modified_after(?int $value): void {
        $this->set_scalar_parameter('assetFilteringCriteria.lastModifiedAfter', $value);
    }

    /**
     * @param string|null $keyword
     * @return void
     */
    public function set_keyword(?string $keyword): void {
        $this->set_scalar_parameter('assetFilteringCriteria.keyword', $keyword);
    }

    /**
     * @param array $locales
     * @return void
     */
    public function set_locales(array $locales): void {
        $this->locales = $locales;
    }

    /**
     * @param bool|null $value
     * @return void
     */
    public function set_include_retired(?bool $value): void {
        $this->set_scalar_parameter('assetRetrievalCriteria.includeRetired', $value);
    }

    /**
     * @param int|null $value
     * @return void
     */
    public function set_expand_depth(?int $value): void {
        $this->set_scalar_parameter('assetRetrievalCriteria.expandDepth', $value);
    }

    /**
     * @param array $classification_urns
     * @return void
     */
    public function set_classifications(array $classification_urns): void {
        $this->classifications = $classification_urns;
    }


    /**
     * @param moodle_url $url
     */
    public function apply_to_url(moodle_url $url): void {
        $parameters = $this->scalar_parameters;

        foreach ($this->asset_types as $i => $asset_type) {
            $parameters["assetFilteringCriteria.assetTypes[{$i}]"] = $asset_type;
        }

        foreach ($this->difficulty_levels as $i => $difficulty_level) {
            $parameters["assetFilteringCriteria.difficultyLevels[{$i}]"] = $difficulty_level;
        }

        foreach ($this->classifications as $i => $classification) {
            $parameters["assetFilteringCriteria.classifications[{$i}]"] = $classification;
        }

        foreach ($this->locales as $i => $locale) {
            $parameters["assetFilteringCriteria.locales[{$i}].language"] = $locale->get_lang();
            $country = $locale->get_country();

            if (!empty($country)) {
                $parameters["assetFilteringCriteria.locales[{$i}].country"] = $country;
            }
        }

        if (array_key_exists('assetFilteringCriteria.licensedOnly', $parameters)) {
            // Apply formatting from boolean to string for field licnesedOnly,
            // as it only accepts string of 'true' or 'false'.
            $value = $parameters['assetFilteringCriteria.licensedOnly'];

            if (is_bool($value)) {
                $parameters['assetFilteringCriteria.licensedOnly'] = $value ? 'true' : 'false';
            }
        }

        if (array_key_exists('assetRetrievalCriteria.includeRetired', $parameters)) {
            // Apply formatting from boolean to string for field includeRetired,
            // as it only accepts string of 'true' or 'false'.
            $value = $parameters['assetRetrievalCriteria.includeRetired'];

            if (is_bool($value)) {
                $parameters['assetRetrievalCriteria.includeRetired'] = $value ? 'true' : 'false';
            }
        }

        $url->params($parameters);
    }

    /**
     * @param string $paging_href
     * @return void
     */
    public function set_parameters_from_paging_url(string $paging_href): void {
        $parameters = helper::parse_query_parameters_from_url($paging_href);

        foreach ($parameters as $key => $value) {
            if (is_array($value)) {
                switch ($key) {
                    case 'assetFilteringCriteria.assetTypes':
                        $this->set_asset_types($value);
                        break;

                    case 'assetFilteringCriteria.locales':
                        $locales = [];
                        foreach ($value as $locale_data) {
                            $locales[] = new locale(
                                $locale_data['language'],
                                $locale_data['country'] ?? null
                            );
                        }

                        $this->set_locales($locales);
                        break;

                    case 'assetFilteringCriteria.classifications':
                        $this->set_classifications($value);
                        break;

                    case 'q':
                        // Do not set the parameter `q`. It should had been done as part of this class.
                        break;
                }

                continue;
            }

            $this->set_scalar_parameter($key, $value);
        }
    }

    /**
     * @return void
     */
    public function clear(): void {
        parent::clear();

        $this->classifications = [];
        $this->locales = [];
        $this->asset_types = [];

        // Default the parameter 'q'.
        $this->set_scalar_parameter('q', 'criteria');
    }
}