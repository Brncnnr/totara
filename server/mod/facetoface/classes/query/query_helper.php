<?php
/*
 * This file is part of Totara LMS
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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface\query;

use core\orm\query\builder;

defined('MOODLE_INTERNAL') || die();

/**
 * query_helper class.
 */
final class query_helper {
    const KEYWORD = '#03c';
    const STRING = '#080';
    const NUMBER = '#900';
    const ENTITY = '#906';
    const SYMBOL = '#960';

    /**
     * Substitute parameter strings in the SQL statement.
     *
     * @param string $sql
     * @param array $params
     * @return string
     */
    public static function substitute(string $sql, array $params): string {
        $keys = array_keys($params);
        usort($keys, function ($x, $y) {
            return strlen($y) <=> strlen($x);
        });
        foreach ($keys as $key) {
            $param = $params[$key];
            if (preg_match('/^(\d+|\d+\.\d*)$/', $param)) {
                $sql = str_replace(':'.$key, $param, $sql);
            } else {
                $sql = str_replace(':'.$key, '"'.addslashes($param).'"', $sql);
            }
        }
        return $sql;
    }

    /**
     * Syntax highlighter & formatter for SQL.
     *
     * @param string $sql query string
     * @param array $params as [key => value]
     * @return string html
     */
    public static function highlight(string $sql, array $params): string {
        // Substitute params.
        $sql = self::substitute($sql, $params);
        // Highlight & indent.
        static $pats = [
            // [regexp, colour, indent]
            ['/(\"([^\"\\\\]|\\\\.)*\")/', self::STRING, false],
            ['/(\'([^\'\\\\]|\\\\.)*\')/', self::STRING, false],
            ['/(\\{[^\\}]*\\})/', self::ENTITY, false],
            ['/(FROM|((LEFT|RIGHT|INNER|OUTER|CROSS|FULL)\s+)?JOIN|(UNION\s+)?(ALL\s+)?SELECT|((AND|OR)\s+)?(NOT\s+)?EXISTS|(CASE\s+)?WHEN|ELSE|END|WHERE|ORDER|AND|OR|GROUP|HAVING)\b/i', self::KEYWORD, true],
            ['/(ON|NOT|IN|IS|AS|LIKE|BETWEEN|DISTINCT|THEN|BY|ASC|DESC|NULL|COUNT|MAX|MIN|AVG|SUM|ROUND|COALESCE)\b/i', self::KEYWORD, false],
            ['/\b(\d+|\d+\.\d*)\b/', self::NUMBER, false],
            ['/\b(\w+(\.\w+)*)\b/', self::SYMBOL, false],
            ['/(\\<|\\&|\\>)/', '', false],
        ];
        $len = strlen($sql);
        $indent = 0;
        $out = '';
        for ($i = 0; $i < $len;) {
            foreach ($pats as $pat) {
                if (preg_match($pat[0], $sql, $matches, PREG_OFFSET_CAPTURE, $i) && $matches[0][1] == $i) {
                    if ($pat[2]) {
                        $out .= "\n".str_repeat(' ', $indent);
                    }
                    $match = $matches[0][0];
                    $code = htmlspecialchars($match);
                    if ($pat[1] !== '') {
                        $out .= "<span style=\"color:{$pat[1]}\">{$code}</span>";
                    } else {
                        $out .= $code;
                    }
                    $i += strlen($match);
                    continue 2;
                }
            }
            $ch = substr($sql, $i, 1);
            if ($ch == '(') {
                $indent++;
            } else if ($ch == ')') {
                $indent--;
            }
            $out .= $ch;
            $i++;
        }
        return '<pre><code style="font-size:11px">'.trim($out).'</code></pre>';
    }
}
