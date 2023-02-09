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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralearning.com>
 * @package engage_article
 */
namespace engage_article\observer;

use engage_article\event\article_created;
use engage_article\event\article_updated;
use engage_article\totara_engage\resource\article;
use totara_core\content\content_handler;
use engage_article\event\article_viewed;
use totara_engage\resource\resource_completion;
use ml_recommender\local\seen_recommended_item;

/**
 * Observer for article component
 */
final class article_observer {
    /**
     * article_observer constructor.
     */
    private function __construct() {
        // Preventing this class from construction
    }

    /**
     * @param article_created $event
     * @return void
     */
    public static function on_created(article_created $event): void {
        $resource_id = $event->get_item_id();
        $actor_id = $event->get_user_id();

        $article = article::from_resource_id($resource_id);
        static::handle_article($article, $actor_id);
    }

    /**
     * @param article_updated $event
     * @return void
     */
    public static function on_updated(article_updated $event): void {
        $resource_id = $event->get_item_id();
        $actor_id = $event->get_user_id();

        $article = article::from_resource_id($resource_id);
        static::handle_article($article, $actor_id);
    }

    /**
     * Pass content through content handlers
     *
     * @param article   $article
     * @param int       $actor_id
     *
     * @return void
     */
    private static function handle_article(article $article, int $actor_id): void {
        $handler = content_handler::create();

        // Note that we trust the owner of the article is the responsible one to trigger
        // the whole process of content handler.
        $handler->handle_with_params(
            $article->get_name(),
            $article->get_content(),
            $article->get_format(),
            $article->get_id(),
            'engage_article',
            article::CONTENT_AREA,
            $article->get_context()->id,
            $article->get_url(),
            $actor_id
        );
    }

    /**
     * @param article_viewed $event
     * @return void
     */
    public static function on_view_created(article_viewed $event): void {
        global $DB;

        $actor_id = $event->get_user_id();
        $others = $event->other;
        $owner_id = $others['owner_id'];
        $resource_id = $event->get_item_id();

        $instance = resource_completion::instance($resource_id, $owner_id);

        $transaction = $DB->start_delegated_transaction();
        if ($instance->can_create($actor_id)) {
            $instance->create();
        }
        $transaction->allow_commit();

        // Flag article as seen if it is on users recommendations list.
        seen_recommended_item::seen_recommended_article($event);

        // Clear instance.
        unset($instance);
    }
}