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
 * @author Simon Chester <simon.chester@totaralearning.com>
 * @package core
 */

namespace core;

/**
 * Transform CSS variables to their static definitions
 */
class cssvars {
    /**
     * @var string Regex to match var() calls.
     */
    const CSS_VAR_REGEX = '/var\(\s*([^),]+)(?:\s*,\s*([^)\s]+)\s*)?\)/s';

    /**
     * @var string Regex to match rules on :root.
     */
    const ROOT_RULE_REGEX = '/:root\s*{(.*?)}/s';

    /**
     * @var string Regex to match CSS comments.
     */
    const COMMENT_REGEX = '/(\\/\\*.*?\\*\\/)/s';

    /**
     * Execute tranformation on the provided CSS.
     *
     * @param string $css
     * @param array $options
     *   - string[] default_values: Default values to use in transform.
     *                              Evaluated as if they were prepended to $css
     *   - string[] override_values: Override values to use in transform.
     *                               Evaluated as if they were appended to $css
     * @return string
     */
    public function transform(string $css, array $options = []): string {
        $options = (object)$options;
        $values = array_merge(
            $options->default_values ?? [],
            $this->get_custom_property_values($css),
            $options->override_values ?? []
        );
        $values = $this->resolve_var_references($values);
        $css = $this->vars_compat($css);
        $css = $this->substitute_provided_values($css, $values);
        $css = $this->replace_nested_calc($css);
        return $css;
    }

    /**
     * Rewrite custom property declarations from `--abc: xyz;` to `-var--abc: xyz;` to allow parsing by IE
     * and attach to :root or non-inherited Node context
     *
     * @param string $css
     * @return string
     */
    private function vars_compat(string $css): string {
        return preg_replace_callback(self::ROOT_RULE_REGEX, [$this, 'replace_custom_css_declarations'], $css);
    }

    private function replace_custom_css_declarations(array $match): string {
        $content = preg_replace(self::COMMENT_REGEX, '', $match[1]);
        $new_root_content = '';
        $new_nonroot_content = '';
        foreach (explode(';', $content) as $line) {
            $line = trim($line);
            if (strlen($line) === 0) {
                continue;
            }
            if (substr($line, 0, 2) === '--') {
                // we need prefixed for IE11
                $new_nonroot_content .= '-var' . $line . ';';
                // and unprefixed for Legacy Edge
                $new_nonroot_content .= $line . ';';
            } else {
                $new_root_content .= $line . ';';
            }
        }

        $output = '';
        if ($new_nonroot_content) {
            $output .= '#cssVarCompatRoot{' . $new_nonroot_content . '}';
        }
        if ($new_root_content) {
            $output .= ':root{' . $new_root_content . '}';
        }
        return $output;
    }

    /**
     * Get custom property values defined on :root
     *
     * @param string $css
     * @return array
     */
    public function get_custom_property_values(string $css): array {
        $matches_found = preg_match_all(self::ROOT_RULE_REGEX, $css, $matches);
        if ($matches_found < 1) {
            return [];
        }
        $result = [];
        foreach ($matches[1] as $match) {
            $content = preg_replace(self::COMMENT_REGEX, '', $match);
            foreach (explode(';', $content) as $line) {
                $line = trim($line);
                if (strlen($line) === 0) {
                    continue;
                }

                list($property, $value) = explode(':', $line);
                $property = trim($property);
                $value = trim($value);

                if (substr($property, 0, 2) === '--') {
                    $result[$property] = $value;
                }
            }
        }
        return $result;
    }

    /**
     * Resolve custom properties that reference other custom properties to their final value
     *
     * @param string[] $values Map of custom properties to values
     * @return string[]
     */
    private function resolve_var_references(array $values): array {
        $sorter = new topological_sorter();

        foreach ($values as $key => $value) {
            $matches_found = preg_match_all(self::CSS_VAR_REGEX, $value, $matches);
            if ($matches_found > 0) {
                // matches[1] will be an array of the first argument to each var() call
                $sorter->add($key, $matches[1]);
            }
        }

        $sorted = $sorter->sort();

        foreach ($sorted as $key) {
            if (!isset($values[$key])) {
                continue;
            }
            $values[$key] = self::substitute_provided_values($values[$key], $values);
        }

        return $values;
    }

    /**
     * Replace var(--custom-prop) references in CSS with the value of the referenced custom property
     *
     * @param string $css
     * @param string[] $values Map of custom properties to values
     * @return string
     */
    private function substitute_provided_values(string $css, array $values): string {
        return preg_replace_callback(self::CSS_VAR_REGEX, function ($match) use ($values) {
            if (isset($values[$match[1]])) {
                // found a value, use that
                return $values[$match[1]];
            } else if (isset($match[2])) {
                // no value, use fallback
                return $match[2];
            } else {
                // no value or fallback, leave it as-is
                return $match[0];
            }
        }, $css);
    }

    /**
     * Replace nested calc() expressions with a single calc expression, as IE 11 does not support nested calc()s.
     *
     * @param string $css
     * @return string
     * @throws \coding_exception on invalid CSS
     */
    private function replace_nested_calc(string $css): string {
        $index = 0;
        $end = strlen($css);
        $out_css = '';
        $last_out_index = 0;

        // search for and replace calc expressions
        while ($index <= $end) {
            // find start of calc expression
            // calc() cannot contain whitespace between the c and the (
            $calc_start_index = strpos($css, 'calc(', $index);
            if ($calc_start_index !== false) {
                // make sure we're not in a comment
                $comment_start_index = strpos($css, '/*', $index);
                $comment_end_index = strpos($css, '*/', $index);
                if ($comment_end_index !== false && ($comment_start_index === false || $comment_start_index > $comment_end_index)) {
                    $index = $comment_end_index + 2;
                    continue;
                }

                // output CSS before calc
                $out_css .= substr($css, $last_out_index, $calc_start_index - $last_out_index);
                $last_out_index = $calc_start_index;

                // find end of property
                $property_end = strpos($css, ';', $calc_start_index);
                if ($property_end === false) {
                    $property_end = strpos($css, '}', $calc_start_index);
                }

                // count opening and closing parentheses to find the end of the calc expression
                $calc_end_index = null;
                $calc_index = $calc_start_index;
                $level = 0;
                while ($calc_index <= $property_end) {
                    if (!preg_match('/\(|\)/', $css, $calc_matches, PREG_OFFSET_CAPTURE, $calc_index) || $calc_matches[0][1] > $property_end) {
                        throw new \coding_exception(
                            "Unbalanced parentheses at index $calc_start_index: " .
                            substr($css, $calc_start_index, 40)
                        );
                        break;
                    }
                    if ($calc_matches[0][0] === '(') {
                        $level++;
                    } else {
                        $level--;
                    }
                    $calc_index = $calc_matches[0][1] + 1;
                    if ($level === 0) {
                        // found the final closing paren
                        $calc_end_index = $calc_index;
                        break;
                    }
                }

                // replace "calc(" with "("
                if ($calc_end_index !== null) {
                    $out_css .= "calc" . preg_replace(
                        '/calc\(/',
                        '(',
                        substr($css, $calc_start_index, $calc_end_index - $calc_start_index)
                    );
                    $last_out_index = $calc_end_index;
                    $index = $calc_end_index;
                } else {
                    $index = $calc_start_index + strlen('calc(');
                }
            } else {
                // no more "calc(" matches left
                break;
            }
        }

        // output remaining CSS
        $out_css .= substr($css, $last_out_index);

        return $out_css;
    }
}
