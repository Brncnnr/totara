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
namespace contentmarketplace_linkedin\dto;

class classification_with_path {
    /**
     * @var classification[]
     */
    private $path;

    /**
     * @var classification
     */
    private $classification;

    /**
     * classification_with_path constructor.
     * @param classification $classification
     * @param classification[] $path
     */
    public function __construct(classification $classification, array $path) {
        $this->classification = $classification;
        $this->path = $path;
    }

    /**
     * @return string
     */
    public function get_type(): string {
        return $this->classification->get_type();
    }

    /**
     * @return string
     */
    public function get_urn(): string {
        return $this->classification->get_urn();
    }

    /**
     * @return classification[]
     */
    public function get_path(): array {
        return $this->path;
    }
}