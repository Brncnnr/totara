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
namespace contentmarketplace_linkedin\api\v2\service\learning_classification\query;

use contentmarketplace_linkedin\api\v2\service\helper;
use contentmarketplace_linkedin\dto\locale;
use moodle_url;
use coding_exception;

class locale_and_type extends query {
    /**
     * @var array
     */
    private $parameters;

    /**
     * The $classification_type must be either of the following:
     * + @see constants::CLASSIFICATION_TYPE_LIBRARY
     * + @see constants::CLASSIFICATION_TYPE_SUBJECT
     * + @see constants::CLASSIFICATION_TYPE_TOPIC
     *
     * locale_and_type constructor.
     * @param locale $locale
     * @param string $classification_type
     */
    public function __construct(locale $locale, string $classification_type) {
        parent::__construct($locale);
        $this->parameters = [
            'q' => 'localeAndType',
            'type' => $classification_type
        ];
    }

    /**
     * @param int $start
     * @return void
     */
    public function set_start(int $start): void {
        $this->parameters['start'] = $start;
    }

    /**
     * @param int $count
     * @return void
     */
    public function set_count(int $count): void {
        $this->parameters['count'] = $count;
    }

    /**
     * @param moodle_url $url
     * @return void
     */
    public function apply_to_url(moodle_url $url): void {
        if (null === $this->target_locale) {
            throw new coding_exception("Cannot apply the query parameters to url when locale is not set");
        }

        $parameters = $this->parameters;
        $parameters['sourceLocale.language'] = $this->target_locale->get_lang();
        $parameters['sourceLocale.country'] = $this->target_locale->get_country();

        $url->params($parameters);
    }

    /**
     * @return void
     */
    public function clear(): void {
        parent::clear();

        // Reset the parameters, but leave the `q`.
        $this->parameters = ['q' => 'localeAndType'];
    }

    /**
     * @param string $paging_url
     * @return void
     */
    public function set_parameters_from_paging_url(string $paging_url): void {
        $parameters = helper::parse_query_parameters_from_url($paging_url);
        $this->target_locale = new locale(
            $parameters['sourceLocale.language'],
            $parameters['sourceLocale.country'] ?? null
        );

        $this->parameters['start'] = $parameters['start'];
        $this->parameters['count'] = $parameters['count'];
        $this->parameters['type'] = $parameters['type'];
    }
}