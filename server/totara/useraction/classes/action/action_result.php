<?php
/**
 * This file is part of Totara Talent Experience Platform
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Cody Finegan <cody.finegan@totara.com>
 * @package totara_useraction
 */

namespace totara_useraction\action;

/**
 * Captures the result of an individual action.
 */
final class action_result {
    /**
     * @var bool
     */
    protected bool $success;

    /**
     * Additional information about the failure.
     *
     * @var string
     */
    protected string $message;

    /**
     * @param bool $success
     * @param string|null $message
     */
    private function __construct(bool $success, string $message = '') {
        $this->success = $success;
        $this->message = $message;
    }

    /**
     * The action had a failure.
     *
     * @param string $message
     * @return static
     */
    public static function failure(string $message): self {
        return new self(false, $message);
    }

    /**
     * The action ran successfully.
     *
     * @return static
     */
    public static function success(): self {
        return new self(true);
    }

    /**
     * @return string
     */
    public function get_message(): string {
        return $this->message;
    }

    /**
     * @return bool
     */
    public function is_success(): bool {
        return $this->success;
    }
}
