<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_engage
 */
namespace totara_engage\resource\input;

/**
 * Data input definition, for the resource input.
 */
final class definition {
    /**
     * @var string
     */
    private $key;

    /**
     * @var string|null
     */
    private $alias;

    /**
     * @var bool
     */
    private $requiredonadd;

    /**
     * @var bool
     */
    private $requiredonupdate;

    /**
     * @var input_validator[]
     */
    private $validators;

    /**
     * @var null|mixed
     */
    private $default;

    /**
     * definition constructor.
     * To instantiate an instance of this class, please use one of the factory
     * method {@see definition::from_parameters()}
     *
     * @param string        $key
     * @param string|null   $alias
     */
    protected function __construct(string $key, ?string $alias = null) {
        $this->key = $key;
        $this->alias = $alias;

        $this->requiredonadd = false;
        $this->requiredonupdate = false;
        $this->default = null;

        $this->validators = [];
    }

    /**
     * The array $parameters should be looking something like the example below:
     *
     * @param string $key
     * @param array  $parameters
     *
     * @return definition
     * @example
     *         $parameters = [
     *              'required-on-add' => false,
     *              'required-on-update' => false,
     *              'default' => 'Hello world',
     *              'alias' => 'some_alias_key',
     *              'validator' => function ($prop): boolean {
     *                  return true;
     *              }
     *         ]
     */
    public static function from_parameters(string $key, array $parameters = []): definition {
        $definition = new static($key);

        if (array_key_exists('alias', $parameters) && !empty($parameters['alias'])) {
            $definition->set_alias($parameters['alias']);
        }

        if (array_key_exists('validators', $parameters)) {
            if (!is_array($parameters['validators'])) {
                debugging(
                    "Invalid validators parameter, as it is expected to be an array ".
                    "of instances of " . input_validator::class,
                    DEBUG_DEVELOPER
                );
            } else {
                $definition->add_validators(...$parameters['validators']);
            }
        }

        if (array_key_exists('required-on-add', $parameters)) {
            // Setting the flag required on adding.

            $required = (bool) $parameters['required-on-add'];
            $definition->set_required_on_add($required);

            unset($required);
        }

        if (array_key_exists('required-on-update', $parameters)) {
            // Setting the flag required on updating.

            $required = (bool) $parameters['required-on-update'];
            $definition->set_required_on_update($required);

            unset($required);
        }

        if (array_key_exists('default', $parameters)) {
            $definition->set_default($parameters['default']);
        }

        return $definition;
    }

    /**
     * @return string
     */
    public function get_key(): string {
        return $this->key;
    }

    /**
     * @return string|null
     */
    public function get_alias(): ?string {
        return $this->alias;
    }

    /**
     * @param string $alias
     * @return void
     */
    public function set_alias(string $alias): void {
        $this->alias = $alias;
    }

    /**
     * @return bool
     */
    public function is_required_on_add(): bool {
        return $this->requiredonadd;
    }

    /**
     * @return bool
     */
    public function is_required_on_update(): bool {
        return $this->requiredonupdate;
    }

    /**
     * @param bool $value
     * @return definition
     */
    public function set_required_on_add(bool $value): definition {
        $this->requiredonadd = $value;
        return $this;
    }

    /**
     * @param bool $value
     * @return definition
     */
    public function set_required_on_update(bool $value): definition {
        $this->requiredonupdate = $value;
        return $this;
    }

    /**
     * @param input_validator $validator
     * @return definition
     */
    public function add_validator(input_validator $validator): definition {
        $this->validators[] = $validator;
        return $this;
    }

    /**
     * @param input_validator ...$validators
     * @return definition
     */
    public function add_validators(input_validator ...$validators): definition {
        foreach($validators as $validator) {
            $this->add_validator($validator);
        }

        return $this;
    }

    /**
     * @return input_validator[]
     */
    public function get_validators(): array {
        return $this->validators;
    }

    /**
     * @return bool
     */
    public function has_validators(): bool {
        return !empty($this->validators);
    }

    /**
     * @return mixed|null
     */
    public function get_default() {
        return $this->default;
    }

    /**
     * @param mixed|null $value
     * @return definition
     */
    public function set_default($value): definition {
        $this->default = $value;
        return $this;
    }
}