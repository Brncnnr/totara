<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 * @package totara_webapi
 */

namespace totara_webapi;

use Exception;
use GraphQL\Error\ClientAware;
use Throwable;

/**
 * Class client_aware_exception
 *
 * @package totara_webapi
 */
class client_aware_exception extends Exception implements ClientAware {

    /**
     * Default category.
     *
     * @var string
     */
    private $default_category = 'internal';

    /**
     * Exception wrapped as client aware.
     *
     * @var Throwable
     */
    private $exception;

    /**
     * Category for exception.
     *
     * @var string
     */
    private $category;

    /**
     * Is clientSafe.
     *
     * @var string
     */
    private $is_client_safe;

    /**
     * client_aware_exception constructor.
     *
     * @param Throwable $exception
     * @param array $exception_data
     */
    public function __construct(Throwable $exception, array $data = []) {
        $exception_data = $this->parse_data($data);

        $this->exception = $exception;
        $this->process_category($exception_data['category']);
        $this->is_client_safe = $exception_data['category'] !== $this->default_category;
        parent::__construct($exception->getMessage(), $exception->getCode());
    }

    /**
     * Parses the data properties for the exception.
     *
     * @param array $data
     * @return array
     */
    private function parse_data(array $data): array {
        $default = [
            'category' => $this->default_category,
        ];
        return array_merge($default, $data);
    }

    /**
     * @inheritdoc
     */
    // phpcs:ignore
    public function isClientSafe() {
        return $this->is_client_safe;
    }

    /**
     * @inheritdoc
     */
    // phpcs:ignore
    public function getCategory() {
        return $this->category;
    }

    /**
     * Sets and run the category post action.
     *
     * @param string $category
     */
    private function process_category(string $category): void {
        $this->set_category($category);
        $this->run_category_action($category);
    }

    /**
     * Set category.
     *
     * @param string $category
     * @return void
     */
    public function set_category(string $category): void {
        $this->category = $category;
    }

    /**
     * Run action based on the category.
     *
     * @param string $category
     * @return void
     */
    private function run_category_action($category): void {
        global $SESSION;

        switch ($category) {
            case 'require_login':
                $SESSION->wantsurl = get_local_referer(false);
                break;
            default:
                break;
        }
    }
}
