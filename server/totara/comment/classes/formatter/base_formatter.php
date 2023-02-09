<?php
/**
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_comment
 */
namespace totara_comment\formatter;

use totara_comment\comment;
use totara_comment\resolver_factory;
use core\webapi\formatter\field\text_field_formatter;
use core\webapi\formatter\formatter;
use totara_comment\formatter\field\time_description_formatter;

/**
 * Class base_formatter
 * @package totara_comment\formatter
 */
abstract class base_formatter extends formatter {

    /** @var comment */
    protected $object;

    /**
     * base_formatter constructor.
     * @param comment $comment
     */
    public function __construct(comment $comment) {
        $resolver = resolver_factory::create_resolver($comment->get_component());
        $area = $comment->get_area();

        $context_id = $resolver->get_context_id($comment->get_instanceid(), $area);
        $context = \context::instance_by_id($context_id);

        parent::__construct($comment, $context);
    }

    /**
     * @param string $field
     * @return mixed|null
     */
    protected function get_field(string $field) {
        $field = $this->get_field_name($field);

        switch ($field) {
            case 'edited':
                $value = $this->object->is_edited();
                break;
            case 'deleted':
                $value = $this->object->is_soft_deleted();
                break;
            default:
                $method_name = 'get_'.$field;
                if (method_exists($this->object, $method_name)) {
                    $value = $this->object->{$method_name}();
                } else {
                    throw new \coding_exception('Unknown field name ' . $field);
                }
                break;
        }

        return $value;
    }

    /**
     * @param string $field
     * @return bool
     */
    protected function has_field(string $field): bool {
        $has_field = false;
        $field = $this->get_field_name($field);

        switch ($field) {
            case 'edited':
            case 'deleted':
                $has_field = true;
                break;
            default:
                $method_name = 'get_'.$field;
                if (method_exists($this->object, $method_name)) {
                    $has_field = true;
                }
                break;
        }

        return $has_field;
    }

    /**
     * @return array
     */
    protected function get_map(): array {
        return [
            'user' => null,
            'id' => null,
            'updateable' => null,
            'deleteable' => null,
            'edited' => null,
            'deleted' => null,
            'reportable' => null,
            'totalreactions' => null,
            'content' => function (?string $content, text_field_formatter $formatter): string {
                if (empty($content) && $this->object->is_soft_deleted()) {
                    $reason = $this->object->get_reason_deleted();
                    // Different phrasing for removed versus user deleted comments
                    if (null !== $reason && comment::REASON_DELETED_REPORTED == $reason) {
                        return get_string('removedcomment', 'totara_comment');
                    }
                    return get_string('deletedcomment', 'totara_comment');
                } else if (null === $content) {
                    debugging("Content is empty, even though the comment was not deleted yet", DEBUG_DEVELOPER);
                    return '';
                }
                $textformat = $this->object->get_format();

                $formatter->set_additional_options(['formatter' => 'totara_tui']);
                $formatter->set_pluginfile_url_options(
                    $this->context,
                    'totara_comment',
                    $this->object->get_comment_area(),
                    $this->object->get_id()
                );

                $formatter->set_text_format($textformat);
                return $formatter->format($content);
            },

            'timedescription' => time_description_formatter::class,
            'reasondeleted' => null,
        ];
    }

    /**
     * Some fields have different names on the model
     *
     * @param string $field
     * @return string
     */
    protected function get_field_name(string $field): string {
        // Some fields go under a different name
        switch ($field) {
            case 'timedescription':
                $field = 'timecreated';
                break;
            case 'reasondeleted':
                $field = 'reason_deleted';
                break;
            case 'totalreactions':
                $field = 'total_reactions';
                break;
        }

        return $field;
    }
}