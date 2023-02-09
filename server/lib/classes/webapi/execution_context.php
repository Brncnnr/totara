<?php
/*
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package core
 */

namespace core\webapi;

use core\date_format;
use core\format;
use core\webapi\formatter\field\date_field_formatter;
use core\webapi\formatter\field\text_field_formatter;
use GraphQL\Type\Definition\ResolveInfo;
use totara_webapi\endpoint_type\base as base_type;
use totara_webapi\endpoint_type\factory as endpoint_type_factory;
use totara_webapi\query_complexity_exception;

/**
 * GraphQL execution context.
 */
class execution_context {

    /** @var base_type */
    private $type;

    /** @var string|null */
    private $operationname;

    /** @var ResolveInfo|null */
    private $resolveinfo;

    /** @var \context */
    private $relevantcontext;

    /** @var array */
    protected $variables = [];

    /**
     * @var array
     */
    private $deprecation_warnings = [];

    /**
     * @var int Running total of complexity of this query so far.
     */
    private $query_complexity_cost = 0;

    /**
     * @var int The global query complexity cost that has not been stored in the database yet.
     */
    private $volatile_global_query_complexity_cost = 0;

    /**
     * @var int The time when the global complexity cost was last flushed to the database.
     */
    private $volatile_global_complexity_cost_last_flushed_time;

    /**
     * @var int The client query complexity cost that has not been stored in the database yet.
     */
    private $volatile_client_query_complexity_cost = 0;

    /**
     * @var int The time when the client complexity cost was last flushed to the database.
     */
    private $volatile_client_complexity_cost_last_flushed_time;

    /**
     * Constructor.
     *
     * @param base_type $type instance of the endpoint type
     * @param string|null $operationname the name of query or mutation, can be null, i.e. in batched queries
     * @param int|null $time Current time
     */
    protected function __construct(base_type $type, ?string $operationname, ?int $time = null) {
        $this->type = $type;
        $this->operationname = $operationname;
        $this->volatile_global_complexity_cost_last_flushed_time =
            $this->volatile_client_complexity_cost_last_flushed_time = $time ?? time();
    }

    /**
     * Factory method for creation of execution context.
     *
     * @param string $type String name of endpoint type, used to instantiate instance of type.
     * @param string|null $operationname the name of query or mutation
     * @return execution_context
     */
    final public static function create(string $type, ?string $operationname = null): self {
        $type = endpoint_type_factory::get_instance($type);
        return new self($type, $operationname);
    }

    /**
     * @internal
     * @param ResolveInfo|null $info
     */
    final public function set_resolve_info(?ResolveInfo $info): void {
        $this->resolveinfo = $info;
    }

    /**
     * Returns advanced information for the current resolve step.
     *
     * @return ResolveInfo|null
     */
    final public function get_resolve_info(): ?ResolveInfo {
        return $this->resolveinfo;
    }

    /**
     * Returns the type of Web API entry point.
     *
     * @return string|null
     */
    final public function get_type(): ?string {
        return $this->type::get_name();
    }

    /**
     * Returns the type of Web API entry point.
     *
     * @return base_type
     */
    final public function get_endpoint_type(): base_type {
        return $this->type;
    }

    /**
     * @param string|null $operationname
     */
    public function set_operationname(?string $operationname): void {
        $this->operationname = $operationname;
    }

    /**
     * Returns persisted query/mutation name.
     *
     * @return string|null
     */
    final public function get_operationname(): ?string {
        return $this->operationname;
    }

    /**
     * Sets the context most relevant to this execution.
     * @param \context $context
     */
    final public function set_relevant_context(\context $context): void {
        if (isset($this->relevantcontext)) {
            throw new \coding_exception('Context can only be set once per execution');
        }
        if ($context->contextlevel == CONTEXT_SYSTEM) {
            // We don't want developers just setting the system context here, that would be bad form.
            // It doesn't provide us with anything.
            // In a multitenant world if a query or mutation has a relevant context then it will always be child of system.
            throw new \coding_exception('Do not use the system context, provide an specific context or do not set a context.');
        }
        $this->relevantcontext = $context;
    }

    /**
     * Returns the context.
     */
    final public function get_relevant_context(): \context {
        if (!isset($this->relevantcontext)) {
            throw new \coding_exception('Context has not been provided for this execution');
        }
        return $this->relevantcontext;
    }

    /**
     * Returns true if the execution has provided a relevant context.
     */
    final public function has_relevant_context(): bool {
        return isset($this->relevantcontext);
    }

    /**
     * Adds deprecation warning for later processing
     *
     * @param string $type_name the graphql type name
     * @param string $field_name the graphql field name
     * @param string $message the deprecation message
     */
    public function add_deprecation_warning(string $type_name, string $field_name, string $message): void {
        $this->deprecation_warnings[$type_name][$field_name] = $message;
    }

    /**
     * Get all deprecation warning triggered in request
     *
     * @return array
     */
    public function get_deprecation_warnings(): array {
        return $this->deprecation_warnings;
    }

    // === Utility functions for resolvers ===

    /**
     * Format timestamp for core_date scalar fields using current user timezone.
     *
     * @deprecated
     * @param int|null $timestamp unix timestamp
     * @param array $args field arguments
     * @return string|null
     */
    public function format_core_date(?int $timestamp, array $args) {
        debugging('format_core_date() in execution_context is deprecated, please use the new \core\webapi\formatter\field\date_field_formatter class', DEBUG_DEVELOPER);

        $format = $args['format'] ?? date_format::FORMAT_TIMESTAMP;

        $formatter = new date_field_formatter($format, \context_system::instance());
        return $formatter->format($timestamp);
    }

    /**
     * Format text to HTML and link files to pluginfile.php script if all options specified.
     *
     * @deprecated
     * @param string $text
     * @param string $format
     * @param array $options - includes 'context', 'component' and 'filearea' for pluginfile.php relinking
     * @return string
     */
    public function format_text(?string $text, $format = FORMAT_HTML, array $options = []) {
        debugging('format_text() in execution_context is deprecated, please use the new \core\webapi\formatter\field\text_field_formatter class', DEBUG_DEVELOPER);

        $context = $options['context'] ?? \context_system::instance();

        $formatter = new text_field_formatter(format::FORMAT_HTML, $context);
        $formatter->set_text_format($format)
            ->set_additional_options($options);

        if (!empty($options['context']) && !empty($options['component']) && !empty($options['filearea'])) {
            $itemid = $options['itemid'] ?? null;
            $formatter->set_pluginfile_url_options($options['context'], $options['component'], $options['filearea'], $itemid);
        } else {
            $formatter->disabled_pluginfile_url_rewrite();
        }

        return $formatter->format($text);
    }

    /**
     * @param int $amount
     * @return void
     */
    public function increment_query_complexity_cost(int $amount): void {
        $max_complexity = get_config('totara_api', 'max_query_complexity');
        $this->query_complexity_cost += $amount;
        $this->volatile_global_query_complexity_cost += $amount;
        $this->volatile_client_query_complexity_cost += $amount;

        // Fail if max complexity is now exceeded.
        if ($max_complexity !== false && $this->query_complexity_cost > (int)$max_complexity) {
            throw new query_complexity_exception("Query complexity exceeded maximum allowed complexity of ".$max_complexity.".");
        }
    }

    /**
     * @return int
     */
    public function get_query_complexity_cost(): int {
        return $this->query_complexity_cost;
    }

    /**
     * Return the current value of the volatile global complexity cost and reset it back to 0.
     * @return int
     */
    public function flush_volatile_global_query_complexity_cost(): int {
        $cost = $this->volatile_global_query_complexity_cost;
        $this->volatile_global_query_complexity_cost = 0;
        $this->volatile_global_complexity_cost_last_flushed_time = time();
        return $cost;
    }

    /**
     * @return int
     */
    public function get_volatile_global_complexity_cost_last_flushed_time(): int {
        return $this->volatile_global_complexity_cost_last_flushed_time;
    }

    /**
     * Return the current value of the volatile client complexity cost and reset it back to 0.
     * @return int
     */
    public function flush_volatile_client_query_complexity_cost(): int {
        $cost = $this->volatile_client_query_complexity_cost;
        $this->volatile_client_query_complexity_cost = 0;
        $this->volatile_client_complexity_cost_last_flushed_time = time();
        return $cost;
    }

    /**
     * @return int
     */
    public function get_volatile_client_complexity_cost_last_flushed_time(): int {
        return $this->volatile_client_complexity_cost_last_flushed_time;
    }

    /**
     * Returns a single variable, null if it does not exist
     *
     * @param string $name
     * @return mixed|null
     */
    public function get_variable(string $name) {
        return $this->variables[$name] ?? null;
    }

    /**
     * Sets a single variable by name, overwrites existing variables
     *
     * @param string $name
     * @param mixed $value
     * @return mixed
     */
    public function set_variable(string $name, $value) {
        return $this->variables[$name] = $value;
    }
}