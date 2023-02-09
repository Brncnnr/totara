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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_engage
 */
namespace totara_engage\query;

use context_user;
use core\pagination\base_cursor;
use core\pagination\offset_cursor;
use totara_engage\access\access;
use totara_engage\query\option\{section, sort, source, type};

final class query {

    /** @var int|null */
    private $access;

    /** @var string|null */
    private $type;

    /** @var int|null */
    private $page;

    /** @var int|null */
    private $perpage;

    /** @var int|null */
    private $userid;

    /** @var int|null */
    private $sort;

    /** @var int|null */
    private $topic;

    /** @var int|null */
    private $section;

    /** @var int|null */
    private $source;

    /** @var string|null */
    private $search = null;

    /** @var bool|null */
    private $restricted = null;

    /** @var null|string */
    private $component = null;

    /** @var null|string */
    private $area = null;

    /**
     * When looking up shared resources, the id of the user who was shared with
     * @var int|null
     */
    private $share_recipient_id;

    /**
     * @var offset_cursor|null
     */
    private $cursor;

    /**
     * @param array $filters
     */
    public function set_filters(array $filters): void {
        // Set filters.
        foreach ($filters as $key => $value) {
            // Skip null values.
            if ($value === null) {
                continue;
            }

            // Set value.
            $method = "set_{$key}";
            if (method_exists($this, $method)) {
                $this->$method($value);
            } else {
                throw new \coding_exception("Unable to set value for '{$key}' filter");
            }
        }
    }

    /**
     * This function will be invoking
     * @see query::get_access_filter_options()
     * @see query::get_type_filter_options()
     * @see query::get_section_filter_options()
     * @see query::get_sort_filter_options()
     *
     * @param string $filter
     * @return array
     */
    public function get_filter_options(string $filter): array {
        $filter = strtolower($filter);
        $method = "get_{$filter}_filter_options";

        if (method_exists(get_class($this), $method)) {
            return $this->$method();
        }

        debugging("No options for filter '{$filter}'", DEBUG_DEVELOPER);
        return [];
    }

    /**
     * @return array|array[]
     */
    private function get_access_filter_options(): array {
        return [
            [
                'id' => null,
                'value' => null,
                'label' => get_string('all', 'totara_engage')
            ],
            [
                'id' => access::get_code(access::PUBLIC),
                'value' => access::get_code(access::PUBLIC),
                'label' => access::get_string(access::PUBLIC)
            ],
            [
                'id' => access::get_code(access::PRIVATE),
                'value' => access::get_code(access::PRIVATE),
                'label' => access::get_string(access::PRIVATE)
            ],
            [
                'id' => access::get_code(access::RESTRICTED),
                'value' => access::get_code(access::RESTRICTED),
                'label' => access::get_string(access::RESTRICTED)
            ]
        ];
    }

    /**
     * @return array|array[]
     */
    private function get_sort_filter_options(): array {
        $options = [
            [
                'id' => sort::CREATED,
                'value' => sort::get_code(sort::CREATED),
                'label' => sort::get_string(sort::CREATED)
            ],
            [
                'id' => sort::POPULAR,
                'value' => sort::get_code(sort::POPULAR),
                'label' => sort::get_string(sort::POPULAR)
            ],
            [
                'id' => sort::ALPHABET,
                'value' => sort::get_code(sort::ALPHABET),
                'label' => sort::get_string(sort::ALPHABET)
            ]
        ];

        if ($this->is_shared() || $this->is_library_from_workspace()) {
            $options[] =
                [
                    'id' => sort::DATESHARED,
                    'value' => sort::get_code(sort::DATESHARED),
                    'label' => sort::get_string(sort::DATESHARED)
                ];
        }

        return $options;
    }

    /**
     * @return array
     */
    private function get_type_filter_options(): array {
        $options = [];

        // All types
        $options[] = [
            'id' => null,
            'value' => null,
            'label' => get_string('all', 'totara_engage')
        ];

        // Get components that supply type filter.
        $components = type::get_all($this);
        if (!empty($components)) {
            foreach ($components as $component) {
                $options[] = [
                    'id' => $component,
                    'value' => $component,
                    'label' => type::get_string($component)
                ];
            }
        }

        return $options;
    }

    /**
     * @return array
     */
    public function get_section_filter_options(): array {
        return section::get_all_options($this);
    }

    /**
     * @param int $sort
     * @return string
     */
    public function get_sort_column(int $sort): string {
        return sort::get_sort_column($sort);
    }

    /**
     * @return base_cursor
     */
    public function get_cursor(): base_cursor {
        if (null === $this->cursor) {
            $this->cursor = offset_cursor::create();
        }

        return $this->cursor;
    }

    /**
     * @param base_cursor $cursor
     * @return void
     */
    public function set_cursor(base_cursor $cursor): void {
        $this->cursor = $cursor;
    }

    /**
     * @param string $type
     * @return void
     */
    public function set_type(string $type): void {
        $this->type = $type;
    }

    /**
     * @return string|null
     */
    public function get_type(): ?string {
        return $this->type;
    }

    /**
     * @param int $source
     * @return void
     */
    public function set_source(int $source): void {
        if (!source::is_valid($source)) {
            debugging("The source value is invalid '{$source}'", DEBUG_DEVELOPER);
        }

        $this->source = $source;
    }

    /**
     * @return int|null
     */
    public function get_source(): ?int {
        return $this->source;
    }

    /**
     * @return bool
     */
    public function is_owned(): bool {
        return $this->area === 'owned';
    }

    /**
     * @param int $value
     * @return void
     */
    public function set_perpage(int $value): void {
        $this->perpage = $value;
    }

    /**
     * @return int
     */
    public function get_perpage(): int {
        return $this->perpage ?? 0;
    }

    /**
     * @param int $userid
     * @return void
     */
    public function set_userid(int $userid): void {
        $this->userid = $userid;
    }

    /**
     * @return int
     */
    public function get_userid(): int {
        global $USER;
        return $this->userid ?? $USER->id;
    }

    /**
     * @return int
     */
    public function get_page(): int {
        return $this->page ?? 1;
    }

    /**
     * @param int $value
     * @return void
     */
    public function set_page(int $value): void {
        $this->page = $value;
    }

    /**
     * @param int $topic
     */
    public function set_topic(int $topic): void {
        $this->topic = $topic;
    }

    /**
     * @return int|null
     */
    public function get_topic(): ?int {
        return $this->topic;
    }

    /**
     * @param $sort
     * @return void
     */
    public function set_sort($sort): void {
        if (is_string($sort)) {
            $this->sort = sort::get_value($sort);
        } else {
            $this->sort = $sort;
        }
    }

    /**
     * @return int
     */
    public function get_sort(): int {
        if (empty($this->sort)) {
            return $this->is_shared() ? sort::DATESHARED : sort::CREATED;
        }

        return $this->sort;
    }

    /**
     * @param int $section
     */
    public function set_section(int $section): void {
        $this->section = $section;
    }

    /**
     * @return int|null
     */
    public function get_section(): ?int {
        return $this->section;
    }

    /**
     * @param $access
     * @return void
     */
    public function set_access($access): void {
        if (is_string($access)) {
            $access = access::get_value($access);
        }
        $this->access = $access;
    }

    /**
     * @return int|null
     */
    public function get_access(): ?int {
        return $this->access;
    }

    /**
     * @return bool
     */
    public function is_saved(): bool {
        return $this->area === 'saved';
    }

    /**
     * @return bool
     */
    public function is_shared(): bool {
        return $this->area === 'shared';
    }

    /**
     * @param string $search
     */
    public function set_search(string $search): void {
        $this->search = $search;
    }

    /**
     * @return string|null
     */
    public function get_search(): ?string {
        return $this->search;
    }

    /**
     * Include the entire "your library" resources and playlists if there are no
     * filters selected or when the section filter selected is the 'all site'
     * option.
     *
     * @return bool
     */
    public function include_entire_library(): bool {
        $filters = $this->is_saved() || $this->is_shared() || $this->is_owned() || $this->section || $this->is_other_users_resources();
        return !$filters || $this->is_allsite();
    }

    /**
     * @return bool
     */
    public function is_adder(): bool {
        return $this->area === 'adder';
    }

    /**
     * @return bool
     */
    public function is_allsite(): bool {
        return $this->section === section::ALLSITE;
    }

    /**
     * @return bool
     */
    public function is_search(): bool {
        return $this->area === 'search' || ($this->is_library_from_workspace() && !empty($this->search));
    }

    /**
     * @param bool $restricted
     */
    public function set_restricted(bool $restricted): void {
        $this->restricted = $restricted;
    }

    /**
     * @return bool
     */
    public function include_restricted(): bool {
        return $this->restricted ?? false;
    }

    /**
     * @param string|null $component
     */
    public function set_component(?string $component): void {
        $this->component = $component;
    }

    /**
     * @return string|null
     */
    public function get_component(): ?string {
        return $this->component;
    }

    /**
     * @param string|null $area
     */
    public function set_area(?string $area): void {
        $this->area = $area;
    }

    /**
     * @return string|null
     */
    public function get_area(): ?string {
        return $this->area;
    }

    /**
     * @return bool
     */
    public function is_library_from_workspace(): bool {
        return $this->component === 'container_workspace' && strtolower($this->area) === 'library';
    }

    /**
     * @return bool
     */
    public function is_other_users_resources(): bool {
        return $this->area === 'otheruserlib';
    }

    /**
     * @return int|null
     */
    public function get_share_recipient_id(): ?int {
        return $this->share_recipient_id;
    }

    /**
     * @param int|null $share_recipient_id
     */
    public function set_share_recipient_id(?int $share_recipient_id): void {
        $this->share_recipient_id = $share_recipient_id;
    }

    /**
     * @return array
     */
    public function get_exclude_component(): array {
        $components = [];
        $user_id = $this->get_userid();

        if (!has_capability('totara/playlist:share', context_user::instance($user_id))) {
            $components[] = 'totara_playlist';
        }

        if (!has_capability('engage/article:share', context_user::instance($user_id))) {
            $components[] = 'engage_article';
        }

        if (!has_capability('engage/survey:share', context_user::instance($user_id))) {
            $components[] = 'engage_survey';
        }
        return $components;
    }
}