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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\model\workflow\interaction\condition;

use mod_approval\model\form\form_data;
use mod_approval\model\json_trait;

/**
 * Simple condition-storage class.
 */
class interaction_condition {

    use json_trait;

    /**
     * @var string
     */
    private $field_key;

    /**
     * @var string
     */
    private $comparison;

    /**
     * @var string
     */
    private $value;

    /**
     * Comparison methods that can be used by is_met().
     *
     * @var string[]
     */
    protected $allowed_comparisons = [
        'equals',
        'exists',
        'not_exists'
    ];

    /**
     * Constructor
     *
     * @param string $condition_key
     * @param string $condition_data
     */
    public function __construct(string $condition_key, string $condition_data) {
        $this->field_key = $condition_key;
        $this->parse_condition_data($condition_data);
    }

    /**
     * Parses JSON condition_data field into properties usable by this object.
     *
     * Properties to be parsed:
     * - Comparison method
     * - Comparison value (if used)
     *
     * @param string $condition_data
     */
    protected function parse_condition_data(string $condition_data): void {
        $options = self::json_decode($condition_data);

        // Condition_data must contain a comparison property.
        if (empty($options['comparison'])) {
            throw new \invalid_parameter_exception('Interaction condition data must include a comparison to use.');
        }

        // Comparison must be allowed via whitelist.
        if (!in_array($options['comparison'], $this->allowed_comparisons)) {
            throw new \invalid_parameter_exception("Interaction condition comparison '{$options['comparison']}' is not allowed.");
        }

        // Assign comparison.
        $this->comparison = $options['comparison'];

        // Is there a value?
        if (!empty($options['value'])) {
            $this->value = $options['value'];
        }
    }

    /**
     * Create an interaction_condition instance from a model object which implements the conditional interface.
     *
     * @param conditional_interface $instance
     * @return interaction_condition
     */
    public static function from_conditional_interface(conditional_interface $instance): self {
        return new self($instance->get_condition_key_field(), $instance->get_condition_data_field());
    }

    /**
     * Given some form data, decide if this condition is met by it.
     *
     * How this works is up to the implementation; in this class, we are simply looking for equality.
     *
     * @param form_data $form_data
     * @return bool
     */
    public function is_met(form_data $form_data): bool {
        // Get value from form_data. A different condition implementation might resolve values from multiple keys.
        $form_value = $form_data->get_value($this->field_key);

        // Call the comparison method with the value.
        return $this->{'comparison_' . $this->comparison}($form_value);
    }

    /**
     * Comparison method 'equals', which returns true if form_value equals condition value.
     *
     * @param string|null $form_value
     * @return bool
     */
    protected function comparison_equals(?string $form_value): bool {
        return !is_null($form_value) && $this->value == $form_value;
    }

    /**
     * Comparison method 'exists', which returns true if form_value is not null (field_key is in form_data)
     *
     * @param string|null $form_value
     * @return bool
     */
    protected function comparison_exists(?string $form_value): bool {
        return !is_null($form_value);
    }

    /**
     * Comparison method 'not_exists', which returns true if form_value is null (field_key is not in form_data)
     *
     * @param string|null $form_value
     * @return bool
     */
    protected function comparison_not_exists(?string $form_value): bool {
        return is_null($form_value);
    }

    /**
     * Returns the form field_key this condition is based on, for entity storage.
     *
     * @return string
     */
    public function condition_key_field(): string {
        return $this->field_key;
    }

    /**
     * Encodes this condition's options as a JSON string for entity storage.
     *
     * @return string
     */
    public function condition_data_field(): string {
        $options = new \stdClass();
        $options->comparison = $this->comparison;
        $options->value = $this->value;
        return self::json_encode($options);
    }
}