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

namespace core\dml;

use coding_exception, dml_exception, moodle_database;

/**
 * Represents raw SQL fragment or the whole query.
 *
 * This was inspired by Raw SQL expressions in Laravel,
 * see https://laravel.com/docs/5.8/queries#raw-expressions
 *
 * @since Totara 13
 */
class sql implements \ArrayAccess {
    /**
     * The SQL fragment.
     *
     * NOTE: must not be modified after instantiation.
     *
     * @var string
     */
    private $sql;

    /**
     * SQL parameters, all types are supported.
     *
     * NOTE: must not be modified after instantiation.
     *
     * @var array
     */
    private $params;

    /**
     * Create instance of Raw SQL.
     *
     * NOTE: Unused named parameters are removed,
     *       the reason is that fragments must be fully isolated so that we can combine them safely.
     *
     * @param string $sql
     * @param array $params
     */
    public function __construct(string $sql, array $params = []) {
        $this->sql = $sql;
        $this->params = $params;

        if (trim($sql) === '' and !$params) {
            return;
        }

        $count = count($params);

        // Use the same parameter validation logic as in fix_sql_params().
        $named_count = preg_match_all('/(?<!:):[a-z][a-z0-9_]*/', $sql, $named_matches); // :: used in pgsql casts
        $dollar_count = preg_match_all('/\$[1-9][0-9]*/', $sql, $dollar_matches);
        $q_count = substr_count($sql, '?');

        if ($named_count) {
            if ($dollar_count or $q_count) {
                throw new dml_exception('mixedtypesqlparam');
            }
            foreach ($named_matches[0] as $key) {
                $key = substr($key, 1);
                if (!array_key_exists($key, $params)) {
                    throw new dml_exception('missingkeyinsql', $key, '');
                }
                if (strlen($key) > 30) {
                    throw new coding_exception("Placeholder names must be 30 characters or shorter. '" . $key . "' is too long.", $sql);
                }
                unset($params[$key]);
            }
            // Remove params that are not used, they are not considered to be bugs.
            foreach ($params as $k => $v) {
                unset($this->params[$k]);
            }

        } else if ($dollar_count) {
            if ($named_count or $q_count) {
                throw new dml_exception('mixedtypesqlparam');
            }
            if ($dollar_count != $count) {
                $a = new \stdClass();
                $a->expected = $dollar_count;
                $a->actual = $count;
                throw new dml_exception('invalidqueryparam', $a);
            }
            // Normalise and check the format to named parameters so that the rest of code can be simplified.
            $params = array_values($this->params);
            $this->params = [];
            for ($i = $count; $i > 0; $i--) { // 1-based placeholders: $1, $2, $2
                $key = '$' . $i;
                if (!in_array($key, $dollar_matches[0], true)) {
                    throw new dml_exception('missingkeyinsql', '$' . $i, '');
                }
                $param = moodle_database::get_unique_param('param');
                $this->params[$param] = $params[$i - 1];
                $this->sql = str_replace($key, ':' . $param, $this->sql);
            }

        } else if ($q_count) {
            if ($named_count or $dollar_count) {
                throw new dml_exception('mixedtypesqlparam');
            }
            if ($q_count != $count) {
                $a = new \stdClass();
                $a->expected = $q_count;
                $a->actual = $count;
                throw new dml_exception('invalidqueryparam', $a);
            }
            // Normalise the keys to be always numeric.
            $this->params = array_values($this->params);

        } else if ($count) {
            // Ignore parameters if there are no placeholders.
            $this->params = [];
        }
    }

    /**
     * Mostly for backwards compatibility only,
     * returns the SQL fragment.
     *
     * @return string SQL markup
     */
    public function __toString() {
        return $this->get_sql();
    }

    /**
     * SQL fragment.
     *
     * Type of parameters is the same as the constructor parameter,
     * that is ?, :named or $#.
     *
     * @return string
     */
    public function get_sql(): string {
        return $this->sql;
    }

    /**
     * Query parameters, the type is no specified.
     *
     * @return array query parameters, order and key names depend on type
     */
    public function get_params(): array {
        return $this->params;
    }

    /**
     * Is this SQL empty?
     *
     * NOTE: whitespace is ignored
     *
     * @return bool
     */
    public function is_empty(): bool {
        return (trim($this->sql) === '');
    }

    /**
     * @internal
     * @param $name
     * @param $value
     */
    public function __set($name, $value) {
        throw new coding_exception('sql instance cannot be modified');
    }

    /**
     * @internal
     * @param $name
     */
    public function __unset($name) {
        throw new coding_exception('sql instance cannot be modified');
    }

    /**
     * Convert this raw sql to named parameters.
     *
     * NOTE: $this is not modified
     *
     * @param string $prefix
     * @return sql
     */
    public function to_named_params(string $prefix = 'param'): sql {
        if (!$this->params or strpos($this->sql, '?') === false) {
            return $this;
        }

        $sql = $this->sql;
        $params = [];

        foreach ($this->params as $v) {
            $param = moodle_database::get_unique_param($prefix);
            $params[$param] = $v;
            $sql = preg_replace('/\?/', ':' . $param, $sql, 1);
        }

        return new sql($sql, $params);
    }

    /**
     * Add suffix to SQL.
     *
     * NOTE: $this is not modified
     *
     * @param sql|string $othersql
     * @param string $glue
     * @param bool $ignoreempty true means do not append if $this sql empty or $othersql is empty
     * @return sql
     */
    public function append($othersql, string $glue = ' ', bool $ignoreempty = true): sql {
        if (strpos($glue, '?') !== false or strpos($glue, '$') !== false or strpos($glue, ':') !== false) {
            throw new \coding_exception('Bound parameters and :: casts are not supported in append glue');
        }

        if ($ignoreempty and $this->is_empty()) {
            return $this;
        }

        if ($othersql instanceof sql) {
            if ($ignoreempty and $othersql->is_empty()) {
                return $this;
            }
            $oparams = $othersql->get_params();
            $osql = $othersql->get_sql();

            if (!$this->params) {
                return new self($this->sql . $glue . $osql, $oparams);
            }
            if (!$oparams) {
                return new self($this->sql . $glue . $osql, $this->params);
            }

            if (strpos($this->sql, '?') !== false and strpos($osql, '?') !== false) {
                // Both sqls using ? placeholders, simple merge will do.
                return new self($this->sql . $glue . $osql, array_merge($this->params, $oparams));
            }

            $first = $this->to_named_params();
            $firstsql = $first->get_sql();
            $firstparams = $first->get_params();
            $second = $othersql->to_named_params();
            $secondsql = $second->get_sql();
            $secondparams = $second->get_params();

            if (array_intersect_key($firstparams, $secondparams)) {
                $fixer = function ($placeholder) use (&$secondparams, &$firstparams) {
                    $param = substr($placeholder[0], 1);
                    if (!array_key_exists($param, $firstparams)) {
                        // No conflict.
                        return $placeholder[0];
                    }
                    $i = 0;
                    if (preg_match('/^uq_([a-z0-9_]+)_\d+$/', $param, $matches)) {
                        // Looks like unique param, we should not be getting conflicts, anyway just get a new number.
                        $prefix = $matches[1];
                        if ($prefix === '') {
                            $prefix = 'param';
                        }
                    } else {
                        // Just invent something new but still similar.
                        $prefix = preg_replace('/[^a-z_]/', '', $param);
                        $i = 2;
                    }
                    do {
                        if ($i) {
                            $newparam = $prefix . $i;
                            $i++;
                        } else {
                            $newparam = moodle_database::get_unique_param($prefix);
                        }
                    } while (array_key_exists($newparam, $firstparams) or array_key_exists($newparam, $secondparams));
                    $secondparams[$newparam] = $secondparams[$param];
                    unset($secondparams[$param]);
                    return ':' . $newparam;
                };
                $secondsql = preg_replace_callback('/(?<!:):[a-z][a-z0-9_]*/', $fixer, $secondsql);
            }

            return new self($firstsql . $glue . $secondsql, array_merge($firstparams, $secondparams));

        } else {
            $othersql = (string)$othersql;
            if ($ignoreempty and trim($othersql) === '') {
                return $this;
            }
            if (strpos($othersql, '?') !== false or strpos($othersql, '$') !== false or strpos($othersql, ':') !== false) {
                throw new \coding_exception('Bound parameters and :: casts are not supported in append string othersql');
            }

            return new self($this->get_sql() . $glue . $othersql, $this->get_params());
        }
    }

    /**
     * Add prefix to SQL.
     *
     * NOTE: $this is not modified
     *
     * @param sql|string $othersql
     * @param string $glue
     * @param bool $ignoreempty true means do not prepend if $this sql empty or $othersql is empty
     * @return sql
     */
    public function prepend($othersql, string $glue = ' ', bool $ignoreempty = true): sql {
        if (strpos($glue, '?') !== false or strpos($glue, '$') !== false or strpos($glue, ':') !== false) {
            throw new \coding_exception('Bound parameters and :: casts are not supported in prepend glue');
        }

        if ($ignoreempty and $this->is_empty()) {
            return $this;
        }

        if ($othersql instanceof sql) {
            if ($ignoreempty and $othersql->is_empty()) {
                return $this;
            }
            return $othersql->append($this, $glue, false);

        } else {
            $othersql = (string)$othersql;
            if ($ignoreempty and trim($othersql) === '') {
                return $this;
            }
            if (strpos($othersql, '?') !== false or strpos($othersql, '$') !== false or strpos($othersql, ':') !== false) {
                throw new \coding_exception('Bound parameters and :: casts are not supported in prepend string othersql');
            }

            return new self($othersql . $glue . $this->get_sql(), $this->get_params());
        }
    }

    /**
     * Merge SQLs and normalise parameters to named format,
     * colliding parameters are renamed automatically.
     *
     * NOTE: $parts instances are not modified
     *
     * @param array $parts either strings or sql instances
     * @param string $glue
     * @param bool $ignoreempty true means skip sql parts that are empty
     * @return sql
     */
    public static function combine(array $parts, string $glue = ' ', bool $ignoreempty = true): sql {
        foreach ($parts as $k => $rawsql) {
            if (is_string($rawsql)) {
                $rawsql = new sql($rawsql);
                $parts[$k] = $rawsql;
            } else if (!($rawsql instanceof sql)) {
                throw new \coding_exception('Invalid $parts parameter, must be array of sql instances or strings');
            }
            if ($ignoreempty and $rawsql->is_empty()) {
                unset($parts[$k]);
            }
        }

        if (!$parts) {
            return new sql('');
        }

        $result = array_shift($parts);
        foreach ($parts as $rawsql) {
            $result = $result->append($rawsql, $glue, false);
        }

        return $result;
    }

    /**
     * Wraps the given SQL in parenthesis and returns it as a new sql object.
     *
     * @param array|sql|string $sql
     * @param string $glue
     * @param bool $ignoreempty true means skip sql parts that are empty
     * @return sql
     */
    public static function wrap($sql, string $glue = ' ', bool $ignoreempty = true) {
        if (is_array($sql)) {
            $sql = self::combine($sql, $glue, $ignoreempty);
        } else if (!$sql instanceof sql) {
            $sql = new sql($sql);
        }
        if ($ignoreempty && $sql->is_empty()) {
            return new sql('');
        }
        return $sql->prepend('(')->append(')');
    }

    // NOTE: following ArrayAccess methods add support for: list($sql, $params) = new \code\dml\sql('SELECT ..' , [...]);

    /**
     * Whether a offset exists
     * @param mixed $offset <p>
     * @return boolean true on success or false on failure.
     */
    #[\ReturnTypeWillChange]
    public function offsetExists($offset) {
        return ($offset === 0 or $offset === '0' or $offset === 1 or $offset === '1' or $offset === 'sql' or $offset === 'params');
    }

    /**
     * Offset to retrieve
     * @param mixed $offset <p>
     * @return mixed Can return all value types.
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset) {
        if ($offset === '0' or $offset === 0 or $offset === 'sql') {
            return $this->get_sql();
        }
        if ($offset === '1' or $offset === 1 or $offset === 'params') {
            return $this->get_params();
        }
        return null;
    }

    /**
     * Offset to set
     * @param mixed $offset <p>
     * @param mixed $value <p>
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value) {
        throw new coding_exception('sql instance cannot be modified');
    }

    /**
     * Offset to unset
     * @param mixed $offset <p>
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function offsetUnset($offset) {
        throw new coding_exception('sql instance cannot be modified');
    }
}
