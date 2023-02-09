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

use contentmarketplace_linkedin\dto\locale;
use moodle_url;

abstract class query {
    /**
     * @var locale|null
     */
    protected $target_locale;

    /**
     * query constructor.
     * @param locale|null $locale
     */
    public function __construct(?locale $locale = null) {
        $this->target_locale = $locale;
    }

    /**
     * Returns the newly built url that get all the current
     * query parameters applied.
     *
     * @param moodle_url $url
     * @return moodle_url
     */
    public function build_url(moodle_url $url): moodle_url {
        $cloned_url = new moodle_url($url);
        $cloned_url->remove_all_params();

        $this->apply_to_url($cloned_url);
        return $cloned_url;
    }

    /**
     * Apply the query parameters to the endpoint url.
     *
     * @param moodle_url $url
     * @return void
     */
    abstract protected function apply_to_url(moodle_url $url): void;

    /**
     * @return void
     */
    public function clear(): void {
        $this->target_locale = null;
    }

    /**
     * @param string $paging_url
     * @return void
     */
    abstract public function set_parameters_from_paging_url(string $paging_url): void;
}