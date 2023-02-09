<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTDvs
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package totara_competency
 */

namespace totara_competency\helpers;

use coding_exception;
use moodle_exception;
use Throwable;

/**
 * Holds details of an error that happened during a competency related process.
 *
 * @property-read string $code
 * @property-read moodle_exception $exception
 * @property-read string $message
 */
class error {
    // Error codes.
    public const CODE_CANNOT_QUEUE_TASK = 'CODE_CANNOT_QUEUE_TASK';
    public const CODE_COMPETENCIES_NOT_IN_FRAMEWORKS = 'CODE_COMPETENCIES_NOT_IN_FRAMEWORKS';
    public const CODE_MISSING_COMPETENCIES = 'CODE_MISSING_COMPETENCIES';
    public const CODE_MISSING_FRAMEWORKS = 'CODE_MISSING_FRAMEWORKS';
    public const CODE_NO_SELECTED_COMPETENCIES = 'CODE_NO_SELECTED_COMPETENCIES';
    public const CODE_PROCESSING_EXCEPTION = 'CODE_PROCESSING_EXCEPTION';

    // Mapping of error codes to lang keys.
    private const LANG_KEYS = [
        self::CODE_CANNOT_QUEUE_TASK => 'error_cannot_queue_task',
        self::CODE_COMPETENCIES_NOT_IN_FRAMEWORKS => 'error_selected_competencies_not_in_frameworks',
        self::CODE_MISSING_COMPETENCIES => 'error_missing_selected_competencies',
        self::CODE_MISSING_FRAMEWORKS => 'error_missing_selected_frameworks',
        self::CODE_NO_SELECTED_COMPETENCIES => 'error_no_selected_competencies',
        self::CODE_PROCESSING_EXCEPTION => 'error_processing_exception',
    ];

    /**
     * @var moodle_exception an exception with the localized error message.
     */
    private moodle_exception $exception;

    /**
     * @var string error code.
     */
    private string $code;

    /**
     * Creates an instance of this object.
     *
     * @param string $code error code.
     * @param string $msg_key key to use to create a localized error message.
     * @param array $msg_args additional key-value pairs to pass to the localized
     *        message generator.
     *
     * @return self the object.
     */
    public static function create(
        string $code,
        string $msg_key,
        array $msg_args = []
    ): self {
        return new self(
            $code,
            new moodle_exception($msg_key, 'totara_competency', null, $msg_args)
        );
    }

    /**
     * Creates an instance of this object.
     *
     * @param string $task the adhoc task that cannot be queued.
     *
     * @return self the object.
     */
    public static function cannot_queue_task(string $task): self {
        $code = self::CODE_CANNOT_QUEUE_TASK;
        return self::create($code, self::LANG_KEYS[$code], ['task' => $task]);
    }

    /**
     * Creates an instance of this object.
     *
     * @param int $count the number of competencies not in target frameworks.
     *
     * @return self the object.
     */
    public static function competencies_not_in_frameworks(
        int $count = 1
    ): self {
        $code = self::CODE_COMPETENCIES_NOT_IN_FRAMEWORKS;
        $base = self::LANG_KEYS[$code];

        [$lang_key, $format_args] = $count === 1
            ? ["{$base}_single", []]
            : ["{$base}_plural", ['count' => $count]];

        return self::create($code, $lang_key, $format_args);
    }

    /**
     * Creates an instance of this object.
     *
     * @param int $count the number of missing target competencies.
     *
     * @return self the object.
     */
    public static function missing_competencies(int $count = 1): self {
        $code = self::CODE_MISSING_COMPETENCIES;
        $base = self::LANG_KEYS[$code];

        [$lang_key, $format_args] = $count === 1
            ? ["{$base}_single", []]
            : ["{$base}_plural", ['count' => $count]];

        return self::create($code, $lang_key, $format_args);
    }

    /**
     * Creates an instance of this object.
     *
     * @param int $count the number of missing frameworks.
     *
     * @return self the object.
     */
    public static function missing_frameworks(int $count = 1): self {
        $code = self::CODE_MISSING_FRAMEWORKS;
        $base = self::LANG_KEYS[$code];

        [$lang_key, $format_args] = $count === 1
            ? ["{$base}_single", []]
            : ["{$base}_plural", ['count' => $count]];

        return self::create($code, $lang_key, $format_args);
    }

    /**
     * Creates an instance of this object.
     *
     * @return self the object.
     */
    public static function no_selected_competencies(): self {
        $code = self::CODE_NO_SELECTED_COMPETENCIES;
        return self::create($code, self::LANG_KEYS[$code]);
    }

    /**
     * Creates an instance of this object.
     *
     * @param Throwable $throwable the raised exception.
     *
     * @return self the object.
     */
    public static function processing_exception(Throwable $throwable): self {
        $code = self::CODE_PROCESSING_EXCEPTION;

        // moodle_exceptions have localized messages, so use that if possible.
        return $throwable instanceof moodle_exception
            ? new self($code, $throwable)
            : self::create(
                $code,
                self::LANG_KEYS[$code],
                ['message' => $throwable->getMessage()]
            );
    }

    /**
     * Default constructor.
     *
     * @param string $code error code.
     * @param moodle_exception $exception convenient object to store lang key,
     *        etc.
     */
    private function __construct(
        string $code,
        moodle_exception $exception
    ) {
        $this->code = $code;
        $this->exception = $exception;
    }

    /**
     * Magic attribute getter.
     *
     * @param string $field field whose value is to returned.
     *
     * @return mixed the field value.
     *
     * @throws coding_exception if the field name is unknown.
     */
    public function __get(string $field) {
        $attrs = ['code', 'exception'];
        if (in_array($field, $attrs)) {
            return $this->$field;
        }

        if ($field === 'message') {
            return $this->exception->getMessage();
        }

        throw new coding_exception(
            'Unknown ' . self::class . " attribute: $field"
        );
    }

    /**
     * Stringifies this object.
     *
     * @return string the stringified version.
     */
    public function __toString() {
        return sprintf(
            '%s[code: %s, message: %s]', __CLASS__, $this->code, $this->message
        );
    }
}