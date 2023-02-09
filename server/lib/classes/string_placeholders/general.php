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

namespace core\string_placeholders;

use lang_string;

/**
 * Class placeholders instance backwards compatible with $a in lang strings.
 */
final class general implements \core_string_placeholders {
    /**
     * @var string|lang_string
     */
    protected $a;

    /**
     * @var array
     */
    protected $replacements = [];

    /**
     * Simple placeholders constructor.
     *
     * @param mixed $a
     * @param bool $clean true means use clean_string() on all data
     */
    public function __construct($a, bool $clean = false) {
        if (!is_array($a) and (!is_object($a) or ($a instanceof lang_string))) {
            $a = (string)$a;
            if ($clean) {
                $a = clean_string($a);
            }
            $this->a = $a;
            return;
        }

        if (is_object($a) and method_exists($a, 'to_array')) {
            $a = $a->to_array();
        } else {
            $a = (array)$a;
        }

        // Record just the single-level properties and ignore arrays and (non-lang_string) objects.
        $replacements = [];

        foreach ($a as $k => $v) {
            if (!is_array($v) and (!is_object($v) or ($v instanceof lang_string))) {
                $v = (string)$v;
                if ($clean) {
                    $v = clean_string($v);
                }
                $replacements[$k] = $v;
            }
        }

        $this->replacements = $replacements;
    }

    /**
     * Replace placeholders with values.
     *
     * @param string $string
     * @return string
     */
    public function replace(string $string): string {
        if (!is_null($this->a)) {
            // Special case of {$a} placeholder, this should not be used in new classes for placeholders.
            return str_replace('{$a}', $this->a, $string);
        }
        if (!$this->replacements) {
            return $string;
        }

        // Do not use the base::replace() to keep compatibility with previous implementation.
        $search = [];
        $replace = [];
        foreach ($this->replacements as $key => $value) {
            $search[] = '{$a->' . $key . '}';
            $replace[] = $value;
        }
        // This is UTF8 safe search because we allow only lower ascii chars in placeholders.
        return str_replace($search, $replace, $string);
    }
}