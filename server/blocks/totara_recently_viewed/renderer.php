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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @package block_totara_recently_viewed
 */

use block_totara_recently_viewed\card;
use block_totara_recently_viewed\card_resolver;
use block_totara_recently_viewed\repository\interaction_repository;
use block_totara_recently_viewed\settings_helper as settings;

/**
 * Renders cards out
 * Class block_totara_recently_viewed_renderer
 */
class block_totara_recently_viewed_renderer extends plugin_renderer_base {
    /**
     * @var stdClass
     */
    protected $config;

    /**
     * @param stdClass $config
     * @param int $instance_id
     * @return string
     */
    public function render_cards(?stdClass $config, int $instance_id): string {
        global $USER;

        if (!isloggedin() || isguestuser()) {
            return '';
        }

        $this->config = $config ?? new stdClass();
        $count = $this->config->noi ?? settings::DEFAULT_NUMBER_OF_ITEMS;

        // Find the interactions and then the elements to work with
        $interaction_items = interaction_repository::get_recently_viewed($count, $USER->id);
        $display = $this->config->display ?? settings::DEFAULT_DISPLAY_TYPE;
        $tiled = $display == settings::TILE;

        $classes = $tiled ? 'block-trv-tiles' : 'block-trv-list';

        $context = [
            'classes' => $classes,
            'layout' => $tiled ? 'horizontal' : 'vertical',
            'cards' => [],
            'has_cards' => false,
        ];

        $interaction_items = array_values($interaction_items);

        // Grab & then process each interaction item
        foreach ($interaction_items as $i => $item) {
            $card = card_resolver::create_card($item->component, $item->item_id);
            if (!$card) {
                continue;
            }

            $content = $this->render_interaction_item($item->component, $card, $tiled, $i);
            if ($content) {
                $content['instance_id'] = $instance_id;
                $context['cards'][] = $content;
            }
        }

        $context['has_cards'] = !empty($context['cards']);

        return $this->render_from_template('block_totara_recently_viewed/main', $context);
    }

    /**
     * @param string $component
     * @param card $card
     * @param bool $is_tiled
     * @param int $index
     * @return array
     */
    protected function render_interaction_item(string $component, card $card, bool $is_tiled, int $index): array {
        global $PAGE;

        $template = ($is_tiled ? 'tile' : 'list') . '_' . $component;
        $image = $card->get_image($is_tiled, $PAGE->theme);
        if ($image instanceof moodle_url) {
            $image = $image->out(false, [
                'theme' => $PAGE->theme->name,
                'preview' => 'block_totara_recently_viewed_' . $template,
            ]);
        }
        $classes = $index === 0 ? 'block-trv-card-first' : '';

        $is_dashboard = $PAGE->pagelayout === 'dashboard';
        $url = $card->get_url($is_dashboard);
        if ($card->is_library_card() && !$is_dashboard) {
            $url->param('source_url', $PAGE->url->out_as_local_url(true));
        }

        return [
            'is_' . $component => true,
            'classes' => $classes,
            'item_id' => $card->get_id(),
            'component' => $component,
            'url' => $url->out(false),
            'image' => $image,
            'title' => $card->get_title(),
            'subtitle' => $card->get_subtitle(),
            'show_reactions' => $this->config->ratings ?? settings::DEFAULT_SHOW_RATINGS,
            'extra' => $card->get_extra_data(),
            'is_tile' => $is_tiled,
        ];
    }
}
