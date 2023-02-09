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
 * @package totara_contentmarketplace
 */

namespace totara_contentmarketplace\course;

use coding_exception;
use Throwable as throwable;

class result {
    /**
     * Constant for successfully create a course.
     *
     * @var int
     */
    public const SUCCESS = 0;

    /**
     * Constant for error when create a course.
     *
     * @var int
     */
    public const ERROR_ON_COURSE_CREATION = 1;

    /**
     * Constant for error when create a module.
     *
     * @var int
     */
    public const ERROR_ON_MODULE_CREATION = 2;

    /**
     * Constant for error when setup course's settings.
     *
     * @var int
     */
    public const ERROR_ON_COURSE_SETTINGS = 3;

    /**
     * A localised string.
     *
     * @var string|null
     */
    private $message;

    /**
     * @var int
     */
    private $code;

    /**
     * The course's id that had been created out of the learning object.
     * However, null if the course is not able to be created, and error should be set.
     *
     * @var int|null
     */
    private $course_id;

    /**
     * @var throwable|null
     */
    private $exception;

    /**
     * result constructor.
     * @param int            $code
     * @param string|null    $message
     * @param int|null       $course_id
     * @param throwable|null $exception
     */
    protected function __construct(int $code, ?string $message, ?int $course_id, ?throwable $exception) {
        $this->code = $code;
        $this->message = $message;
        $this->course_id = $course_id;
        $this->exception = $exception;
    }

    /**
     * @param int|null       $course_id
     * @param int            $code
     * @param string|null    $message
     * @param throwable|null $exception
     *
     * @return result
     */
    public static function create(
        ?int $course_id = null,
        int $code = self::SUCCESS,
        ?string $message = null,
        ?throwable $exception = null
    ): result {
        if (self::SUCCESS === $code && empty($course_id)) {
            throw new coding_exception(
                "Cannot create a result of success with no course's id set"
            );
        }

        return new static(
            $code,
            $message,
            $course_id,
            $exception
        );
    }

    /**
     * @return bool
     */
    public function is_error(): bool {
        return !$this->is_successful();
    }

    /**
     * @return bool
     */
    public function is_successful(): bool {
        return static::SUCCESS === $this->code;
    }

    /**
     * @return int
     */
    public function get_course_id(): ?int {
        return $this->course_id;
    }

    /**
     * @return throwable|null
     */
    public function get_exception(): ?throwable {
        return $this->exception;
    }

    /**
     * @return string|null
     */
    public function get_message(): ?string {
        return $this->message;
    }

    /**
     * @return int
     */
    public function get_code(): int {
        return $this->code;
    }
}