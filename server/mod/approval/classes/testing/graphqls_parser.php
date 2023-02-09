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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\testing;

use GraphQL\Language\AST\FieldDefinitionNode;
use GraphQL\Language\AST\Node;
use GraphQL\Language\Parser;
use GraphQL\Language\Visitor;

/**
 * graphqls_parser class
 */
class graphqls_parser {
    private const INIT = 0;
    private const ENUM = 1;
    private const TYPE = 2;
    private const UNION = 3;
    private const INPUT = 4;
    private const EXTEND = 5;
    private const RESOLVER = 6;

    /** @var array Parser state transition */
    private static $parser_state_map = [
        self::INIT => [
            self::ENUM => 'enter',
            self::TYPE => 'enter',
            self::UNION => 'enter',
            self::INPUT => 'enter',
            self::EXTEND => 'enter',
        ],
        self::ENUM => [self::INIT => 'leave'],
        self::TYPE => [self::INIT => 'leave'],
        self::UNION => [self::INIT => 'leave'],
        self::INPUT => [self::INIT => 'leave'],
        self::EXTEND => [self::RESOLVER => 'enter_resolver', self::INIT => 'leave_resolver'],
        self::RESOLVER => [self::EXTEND => 'leave'],
    ];

    /** @var array Definition state transition */
    private static $def_state_map = [
        self::INIT => [self::ENUM, self::TYPE, self::UNION, self::INPUT, self::EXTEND],
        self::ENUM => [self::TYPE, self::UNION, self::INPUT, self::EXTEND],
        self::TYPE => [self::UNION, self::INPUT, self::EXTEND],
        self::UNION => [self::INPUT, self::EXTEND],
        self::INPUT => [self::EXTEND],
        self::EXTEND => [self::RESOLVER],
        self::RESOLVER => [],
    ];

    /** @var string Full path to .graphqls file */
    private $file;

    /** @var integer Overall parser state */
    private $parser_state;

    /** @var integer Type definition state */
    private $def_state;

    /** @var string Last name in the state */
    private $last_name;

    /** @var string Last resolver name in the state */
    private $last_resolver_name;

    /**
     * Parse the GraphQL schema.
     *
     * @param string $file Full path to a file
     * @throws gql_parser_error
     */
    public function parse(string $file): void {
        $contents = file_get_contents($file);
        if (!$contents) {
            throw new gql_parser_error($file, 'Cannot open file');
        }
        $this->file = $file;
        $this->parse_internal($contents);
    }

    /**
     * @param string $contents
     */
    private function parse_internal(string $contents): void {
        $this->parser_state = self::INIT;
        $this->def_state = self::INIT;
        $this->last_name = '';
        $this->last_resolver_name = '';

        $doc = Parser::parse($contents);
        Visitor::visit($doc, [
            'ObjectTypeDefinition' => [
                'enter' => function ($node) {
                    $this->transition(self::TYPE, $node);
                },
                'leave' => function ($node) {
                    $this->transition(self::INIT, $node);
                }
            ],
            'InputObjectTypeDefinition' => [
                'enter' => function ($node) {
                    $this->transition(self::INPUT, $node);
                },
                'leave' => function ($node) {
                    $this->transition(self::INIT, $node);
                }
            ],
            'EnumTypeDefinition' => [
                'enter' => function ($node) {
                    $this->transition(self::ENUM, $node);
                },
                'leave' => function ($node) {
                    $this->transition(self::INIT, $node);
                }
            ],
            'UnionTypeDefinition' => [
                'enter' => function ($node) {
                    $this->transition(self::UNION, $node);
                },
                'leave' => function ($node) {
                    $this->transition(self::INIT, $node);
                }
            ],
            'ObjectTypeExtension' => [
                'enter' => function ($node) {
                    $this->transition(self::EXTEND, $node);
                },
                'leave' => function ($node) {
                    $this->transition(self::INIT, $node);
                }
            ],
            'FieldDefinition' => [
                'enter' => function ($node) {
                    $this->transition(self::RESOLVER, $node);
                },
                'leave' => function ($node) {
                    $this->transition(self::EXTEND, $node);
                }
            ],
        ]);
    }

    /**
     * @param integer $state
     * @param Node $node
     */
    private function transition(int $state, Node $node): void {
        $method = self::$parser_state_map[$this->parser_state][$state] ?? null;
        if ($method !== null) {
            $this->parser_state = $state;
            $this->{$method}($state, $node);
        }
    }

    /**
     * @param integer $state
     * @param Node $node
     */
    private function enter(int $state, Node $node): void {
        $node_name = property_exists($node, 'name') ? $node->name->value : null;
        if ($this->def_state === $state) {
            if ($node_name !== null) {
                if ($state === self::EXTEND) {
                    if ($this->last_name === 'Mutation' && $node_name === 'Query') {
                        $hint = self::state_hint($this->def_state);
                        throw new gql_parser_error($this->file, "The {$hint} {$node_name} must precede {$this->last_name}", $node);
                    }
                } else {
                    if (strcasecmp($this->last_name, $node_name) > 0) {
                        $hint = self::state_hint($this->def_state);
                        throw new gql_parser_error($this->file, "The {$hint} '{$node_name}' must precede '{$this->last_name}'", $node);
                    }
                }
                $this->last_name = $node_name;
            }
        } else if (in_array($state, self::$def_state_map[$this->def_state])) {
            $this->def_state = $state;
            $this->last_name = $node_name ?? '';
            $this->last_resolver_name = '';
        } else {
            $from = self::state_hint($this->def_state);
            $to = self::state_hint($state);
            throw new gql_parser_error($this->file, "The {$to} definition must precede the {$from} definitions", $node);
        }
    }

    /**
     * @param integer $state
     */
    private function leave(int $state): void {
        // Nothing to do.
    }

    /**
     * @param integer $state
     * @param FieldDefinitionNode $node
     */
    private function enter_resolver(int $state, FieldDefinitionNode $node): void {
        $node_name = $node->name->value;
        if (strcasecmp($this->last_resolver_name, $node_name) > 0) {
            throw new gql_parser_error($this->file, "The resolver '{$node_name}' must precede '{$this->last_resolver_name}'", $node);
        }
        $this->last_resolver_name = $node_name;
    }

    /**
     * @param integer $state
     */
    private function leave_resolver(int $state): void {
        $this->last_resolver_name = '';
    }

    /**
     * @param integer $state
     * @return string
     */
    private static function state_hint(int $state): string {
        $hints = [
            self::INIT => '?',
            self::ENUM => 'enum',
            self::TYPE => 'type',
            self::UNION => 'union',
            self::INPUT => 'input',
            self::EXTEND => 'extend type',
            self::RESOLVER => 'resolver'
        ];
        return $hints[$state];
    }

    /**
     * @param integer $state
     * @param Node $node
     */
    private function dump_node(int $state, Node $node): void {
        $node_name = property_exists($node, 'name') ? $node->name->value : '?';
        if ($state === self::RESOLVER) {
            echo "  {$node_name}\n";
        } else if ($state !== self::INIT) {
            $hint = self::state_hint($state);
            echo "{$hint} {$node_name}\n";
        }
    }
}
