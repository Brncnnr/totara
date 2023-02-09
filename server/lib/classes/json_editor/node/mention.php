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
 * @author Simon Chester <simon.chester@totaralearning.com>
 * @package core
 */
namespace core\json_editor\node;

use core\json_editor\helper\node_helper;
use core\json_editor\node\abstraction\inline_node;
use html_writer;
use moodle_url;
use core\json_editor\formatter\formatter;

/**
 * Json node for mention string.
 */
final class mention extends node implements inline_node {
    /**
     * @var string
     */
    private $display;

    /**
     * @var int
     */
    private $userid;

    /**
     * @param array $node
     *
     * @return node
     */
    public static function from_node(array $node): node {
        if (!array_key_exists('attrs', $node) || !is_array($node['attrs'])) {
            throw new \coding_exception("No property 'attrs' found");
        }

        /** @var mention $innernode */
        $innernode = parent::from_node($node);
        $attrs = $node['attrs'];

        $innernode->display = $attrs['display'];
        $innernode->userid = (int) $attrs['id'];

        return $innernode;
    }

    /**
     * @return int
     */
    public function get_userid(): int {
        return $this->userid;
    }

    /**
     * @return string|null
     */
    public function get_display(): ?string {
        return $this->display;
    }

    /**
     * @param formatter $formatter
     * @return string
     */
    public function to_html(formatter $formatter): string {
        $url = new moodle_url('/user/profile.php', ['id' => $this->userid]);
        return html_writer::tag('a', s($this->display), ['href' => $url->out(false)]);
    }

    /**
     * @param formatter $formatter
     * @return string
     */
    public function to_text(formatter $formatter): string {
        return "@{$this->display}";
    }

    /**
     * @return string
     */
    protected static function do_get_type(): string {
        return "mention";
    }

    /**
     * @param int $user_id
     * @return array
     */
    public static function create_raw_node(int $user_id): array {
        global $DB;
        $user_record = $DB->get_record('user', ['id' => $user_id], '*', MUST_EXIST);

        return [
            'type' => static::get_type(),
            'attrs' => [
                'id' => $user_id,
                'display' => fullname($user_record)
            ]
        ];
    }

    /**
     * @param array $raw_node
     * @return bool
     */
    public static function validate_schema(array $raw_node): bool {
        if (!array_key_exists('attrs', $raw_node)) {
            return false;
        }

        $attrs = $raw_node['attrs'];
        if (!array_key_exists('display', $attrs) || !array_key_exists('id', $attrs)) {
            return false;
        }

        $attribute_keys = array_keys($attrs);
        if (!node_helper::check_keys_match($attribute_keys, ['display', 'id'])) {
            return false;
        }

        $input_keys = array_keys($raw_node);
        return node_helper::check_keys_match($input_keys, ['type', 'attrs']);
    }

    /**
     * @param array $raw_node
     * @return array|null
     */
    public static function clean_raw_node(array $raw_node): ?array {
        $cleaned_raw_node = parent::clean_raw_node($raw_node);

        if (null === $cleaned_raw_node) {
            return null;
        }

        if (!array_key_exists('attrs', $cleaned_raw_node)) {
            throw new \coding_exception("Invalid node structure", static::get_type());
        }

        $cleaned_raw_node['attrs']['id'] = clean_param($cleaned_raw_node['attrs']['id'], PARAM_INT);

        return $cleaned_raw_node;
    }
}