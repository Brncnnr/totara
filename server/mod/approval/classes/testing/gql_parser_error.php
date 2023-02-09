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

use Exception;
use GraphQL\Language\AST\FieldDefinitionNode;
use GraphQL\Language\AST\Node;

/**
 * gql_parser_error exception.
 */
final class gql_parser_error extends Exception {
    /** @var Node|null Offended node */
    private $node;

    /** @var integer First line */
    private $first_line;

    /**
     * @param string $file
     * @param string $message
     * @param Node|null $node
     */
    public function __construct(string $file, string $message, ?Node $node = null) {
        if ($node) {
            $this->node = $node;
            $this->first_line = 1 + substr_count($node->loc->source->body, "\n", 0, $node->loc->start);
            parent::__construct(sprintf("%s:%d : %s", $file, $this->first_line, $message));
        } else {
            parent::__construct(sprintf("%s : %s", $file, $message));
        }
    }

    /**
     * Get the problematic code with line number.
     *
     * @return string
     */
    public function get_code_fragment(): string {
        if (!$this->node) {
            return '';
        }
        $node = $this->node;
        $line = $this->first_line;
        $texts = explode("\n", substr($node->loc->source->body, $node->loc->start, $node->loc->end - $node->loc->start));
        $digits = (int)ceil(log10($line + count($texts)));
        $is_resolver = $node instanceof FieldDefinitionNode;
        foreach ($texts as &$text) {
            if ($is_resolver) {
                $text = preg_replace('/^  /', '', $text);
            }
            $text = sprintf("%{$digits}d: %s", $line++, $text);
        }
        return implode("\n", $texts);
    }
}
