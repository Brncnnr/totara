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
 * @package contentmarketplace_linkedidn
 */
namespace contentmarketplace_linkedin\dto;

use moodle_url;

abstract class result {
    /**
     * @var bool
     */
    private $successful;

    /**
     * @var string
     */
    private $message;

    /**
     * @var moodle_url|null
     */
    private $redirect_url;

    /**
     * course_creation_result constructor.
     * @param bool $successful
     */
    public function __construct(bool $successful = false) {
        $this->successful = $successful;
        $this->message = '';
        $this->redirect_url = null;
    }

    /**
     * @return bool
     */
    public function is_successful(): bool {
        return $this->successful;
    }

    /**
     * @param bool $successful
     * @return void
     */
    public function set_successful(bool $successful): void {
        $this->successful = $successful;
    }

    /**
     * @return string
     */
    public function get_message(): string {
        return $this->message;
    }

    /**
     * @param string $message
     * @return void
     */
    public function set_message(string $message): void {
        $this->message = $message;
    }

    /**
     * @return moodle_url|null
     */
    public function get_redirect_url(): ?moodle_url {
        return $this->redirect_url;
    }

    /**
     * @param moodle_url|null $redirect_url
     * @return void
     */
    public function set_redirect_url(?moodle_url $redirect_url): void {
        $this->redirect_url = $redirect_url;
    }
}