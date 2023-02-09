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
namespace contentmarketplace_linkedin\api\response;

use contentmarketplace_linkedin\exception\json_validation_exception;
use core\json\validation_adapter;
use contentmarketplace_linkedin\core_json\structure\pagination as pagination_structure;
use stdClass;

class pagination {
    /**
     * The json data.
     *
     * @var stdClass
     */
    protected $json_data;

    /**
     * pagination constructor.
     * @param stdClass $json_data
     */
    protected function __construct(stdClass $json_data) {
        $this->json_data = $json_data;
    }

    /**
     * @param stdClass $json_data       The pagination json data.
     * @param bool     $skip_validation This flag is in place to help preventing the validation running multiple times.
     *                                  It is happening because within the collection schema, the pagination schema is also a
     *                                  sub schema of it, and we would not want to run validation again as the json data is already
     *                                  been validated. By default, it will not skip the validation.
     * @return pagination
     */
    public static function create(stdClass $json_data, bool $skip_validation = false): pagination {
        // Clear any pass by reference.
        $json_data = clone $json_data;

        if (!$skip_validation) {
            $validator = validation_adapter::create_default();
            $result = $validator->validate_by_structure_class_name($json_data, pagination_structure::class);


            if (!$result->is_valid()) {
                $error_message = $result->get_error_message();
                throw new json_validation_exception($error_message);
            }
        }

        return new static($json_data);
    }

    /**
     * @return int
     */
    public function get_total(): int {
        return $this->json_data->total;
    }

    /**
     * @return int
     */
    public function get_count(): int {
        return $this->json_data->count;
    }

    /**
     * @return int
     */
    public function get_start(): int {
        return $this->json_data->start;
    }

    /**
     * @param string $rel
     * @return string|null
     */
    protected function find_link_from_rel(string $rel): ?string {
        $links = $this->json_data->links;;
        foreach ($links as $link) {
            if ($link->rel === $rel) {
                return $link->href;
            }
        }

        return null;
    }

    /**
     * @return string|null
     */
    public function get_previous_link(): ?string {
        return $this->find_link_from_rel('prev');
    }

    /**
     * @return string|null
     */
    public function get_next_link(): ?string {
        return $this->find_link_from_rel('next');
    }

    /**
     * @return bool
     */
    public function has_next(): bool {
        $url = $this->get_next_link();
        return null !== $url;
    }
}