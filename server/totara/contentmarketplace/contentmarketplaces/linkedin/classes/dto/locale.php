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

namespace contentmarketplace_linkedin\dto;

final class locale {
    /**
     * @var string
     */
    private $lang;

    /**
     * @var string|null
     */
    private $country;

    /**
     * locale constructor.
     * @param string      $lang
     * @param string|null $country
     */
    public function __construct(string $lang, ?string $country = null) {
        $this->lang = $lang;
        $this->country = $country;
    }

    /**
     * @return string
     */
    public function get_lang(): string {
        return $this->lang;
    }

    /**
     * @return string|null
     */
    public function get_country(): ?string {
        return $this->country;
    }

    /**
     * @return string
     */
    public function __toString(): string {
        if (!empty($this->country)) {
            return "{$this->lang}_{$this->country}";
        }

        return $this->lang;
    }
}