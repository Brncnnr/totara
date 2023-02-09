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
use Throwable;

/**
 * Holds an execution result.
 *
 * This is just an {@link https://blog.logrocket.com/javascript-either-monad-error-handling Either}
 * monad that explicitly treats a totara_competency\helpers\error as a failed
 * result.
 *
 * @property-read mixed $value
 */
class result {
    /**
     * @var mixed embedded value.
     */
    private $value;

    /**
     * Creates an instance of this object.
     *
     * @param mixed $value object to embed.
     *
     * @return self the object.
     */
    public static function create($value): self {
        if ($value instanceof self) {
            throw new coding_exception(
                'embedding ' . self::class . ' in another ' . self::class
            );
        }

        return new static($value);
    }

    /**
     * Convenience function that wraps a try-catch block around an arbitrary
     * function and returns a failed result if an exception is thrown.
     *
     * The $fn() execution result is returned in a result if it is not a result
     * already.
     *
     * @param callable|Invokable $fn A,B,...->mixed function to wrap.
     * @param mixed[] $args parameters to pass to the function.
     *
     * @return self the execution result.
     */
    public static function try(
        callable $fn,
        ...$args
    ): self {
        try {
            $result = $fn(...$args);

            return is_object($result) && $result instanceof self
                    ? $result
                    : self::create($result);
        } catch (Throwable $throwable) {
            $error = error::processing_exception($throwable);
            return self::create($error);
        }
    }

    /**
     * Default constructor.
     *
     * @param mixed $value object to embed.
     */
    private function __construct($value) {
        $this->value = $value;
    }

    /**
     * Magic attribute getter
     *
     * @param string $field field whose value is to returned.
     *
     * @return mixed the field value.
     *
     * @throws coding_exception if the field name is unknown.
     */
    public function __get(string $field) {
        $attrs = ['value'];
        if (in_array($field, $attrs)) {
            return $this->$field;
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
        if (is_object($this->value)) {
            if (method_exists($this->value, '__toString')) {
                $value = $this->value->__toString();
            } else {
                $value = get_class($this->value);
            }
        } else if (!is_scalar($this->value)) {
            $value = print_r($this->value, true);
        } else {
            $value = (string) $this->value;
        }
        return sprintf('%s[value: %s]', __CLASS__, $value);
    }

    /**
     * Indicates whether this is a failed result.
     *
     * @return bool true if this a failed result.
     */
    public function is_failed(): bool {
        return $this->value instanceof error;
    }

    /**
     * Indicates whether this is a successful result.
     *
     * @return bool true if this a successful result.
     */
    public function is_successful(): bool {
        return !$this->is_failed();
    }

    /**
     * Executes the given function on the embedded value.
     *
     * @param callable $fn mixed->result<mixed> function that creates a result
     *        with the new value.
     *
     * @return self updated result.
     */
    public function flat_map(callable $fn): self {
        return $this->is_failed() ? $this : $fn($this->value);
    }

    /**
     * Executes the given function on the embedded value.
     *
     * @param callable $fn mixed->mixed function to change the embedded value.
     *
     * @return self updated result.
     */
    public function map(callable $fn): self {
        return $this->is_failed() ? $this : self::create($fn($this->value));
    }

    /**
     * Executes the specified function on the embedded value if the embedded
     * value is an error.
     *
     * @param callable $fn error->mixed function to execute.
     *
     * @return self updated result.
     */
    public function or_else(callable $fn): self {
        return $this->is_failed() ? self::create($fn($this->value)) : $this;
    }
}