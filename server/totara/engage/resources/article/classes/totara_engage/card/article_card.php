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
 * @package engage_article
 */
namespace engage_article\totara_engage\card;

use engage_article\theme\file\article_image;
use engage_article\totara_engage\resource\article;
use moodle_url;
use theme_config;
use totara_comment\loader\comment_loader;
use totara_engage\card\card;
use totara_engage\timeview\time_view;
use totara_reaction\loader\reaction_loader;
use totara_topic\provider\topic_provider;
use totara_tui\output\component;

final class article_card extends card {
    /**
     * @return component
     */
    public function get_tui_component(): component {
        return new component("engage_article/components/card/ArticleCard");
    }

    /**
     * @param theme_config|null $theme_config
     * @return array
     */
    public function get_extra_data(?theme_config $theme_config = null): array {
        $extra_data = [
            'image' => $this->get_card_image('engage_article_resource', $theme_config)->out(false),
            'usage' => article::get_resource_usage($this->instanceid),
            'timeview' => null,
            'alt' => ''
        ];

        $extra = $this->get_json_decoded_extra();

        if (isset($extra['timeview'])) {
            $extra_data['timeview'] = time_view::get_code($extra['timeview']);
        }

        $extra_data['alt'] = !empty($extra['alt_text']) ? $extra['alt_text'] : '';
        return $extra_data;
    }

    /**
     * @return array
     */
    public function get_topics(): array {
        $id = $this->get_instanceid();
        return topic_provider::get_for_item($id, $this->component, 'engage_resource');
    }

    /**
     * @return int
     */
    public function get_total_reactions(): int {
        $paginator = reaction_loader::get_paginator('engage_article', 'media', $this->instanceid);
        return $paginator->get_total();
    }

    /**
     * @return int
     */
    public function get_total_comments(): int {
        return comment_loader::count_comments(
            $this->instanceid,
            'engage_article',
            'comment'
        );
    }

    /**
     * @param string|null $preview_mode
     * @param theme_config|null $theme_config
     * @return moodle_url|null
     */
    public function get_card_image(?string $preview_mode = null, ?theme_config $theme_config = null): ?moodle_url {
        global $USER;

        $extra = $this->get_json_decoded_extra();
        if (!empty($extra['image'])) {
            $image = new \moodle_url($extra['image'], ['theme' => $theme_config->name]);
        } else {
            $article_image = new article_image($theme_config);
            $article_image->set_tenant_id($USER->tenantid ?? 0);
            $image = $article_image->get_current_or_default_url();
        }

        if ($preview_mode) {
            $image->param('preview', $preview_mode);
        }

        return $image;
    }

    /**
     * @return component
     */
    public function get_card_image_component(): component {
        return new component('engage_article/components/card/ArticleCardImage');
    }
}