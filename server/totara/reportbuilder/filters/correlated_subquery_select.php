<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * @package totara_reportbuilder
 */

require_once(__DIR__ . '/select.php');

/**
 * Generic filter for correlated subquery searches via normal simple select.
 *
 * NOTE: this filter requires the following options:
 *        - 'subquery' the correlated subquery with first sprintf placeholder for normal field parameter and second placeholder for search condition
 *        - 'searchfield' the column from subquery used to create search condition via normal number filter
 */
class rb_filter_correlated_subquery_select extends rb_filter_select {
    private $overrideselectfield = null;

    /**
     * Return SQL snippet for field name depending on report cache settings
     */
    public function get_field() {
        if (isset($this->overrideselectfield)) {
            return $this->overrideselectfield;
        }
        return parent::get_field();
    }

    /**
     * Returns the condition to be used with SQL where.
     *
     * @param array $data filter settings
     * @return array containing filtering condition SQL clause and params
     */
    public function get_sql_filter($data) {
        // When the filter is deactivated ('any value' is selected), we don't want to add a subquery at all because it
        // would exclude those rows that don't have a value for the filtered field.
        if ($data['value'] === '') {
            return ['', []];
        }

        $this->overrideselectfield = $this->options['searchfield'];
        list($select, $params) = parent::get_sql_filter($data);
        $this->overrideselectfield = null;

        $select = sprintf($this->options['subquery'], $this->get_field(), $select);

        return array($select, $params);
    }
}
