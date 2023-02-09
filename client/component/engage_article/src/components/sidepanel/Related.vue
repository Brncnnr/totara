<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2020 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD's customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totaralearning.com] for more information.

  @author Alvin Smith <alvin.smith@totaralearning.com>
  @package engage_article
-->

<template>
  <div v-if="articles.length > 0" class="tui-engageArticleRelated">
    <article
      v-for="{
        bookmarked,
        instanceid,
        image,
        alt,
        name,
        reactions,
        timeview,
        url,
      } in articles"
      :key="instanceid"
    >
      <RelatedCard
        :resource-id="instanceid"
        :bookmarked="bookmarked"
        :image="image"
        :image-alt="alt"
        :name="name"
        :reactions="reactions"
        :timeview="timeview"
        :url="url"
        @update="update"
      />
    </article>
  </div>
  <span
    v-else-if="!$apollo.loading"
    class="tui-engageArticleRelated__noResults"
  >
    {{ $str('noresults', 'core') }}
  </span>
</template>

<script>
import RelatedCard from 'engage_article/components/card/RelatedCard';
import { UrlSourceType } from 'totara_engage/index';
import { config } from 'tui/config';

import getRecommendation from 'ml_recommender/graphql/get_recommended_articles';
import updateBookmark from 'totara_engage/graphql/update_bookmark';

export default {
  components: {
    RelatedCard,
  },
  props: {
    resourceId: {
      type: [Number, String],
      required: true,
    },
  },

  apollo: {
    articles: {
      query: getRecommendation,
      refetchAll: false,
      variables() {
        return {
          article_id: this.resourceId,
          source: UrlSourceType.article(this.resourceId),
          theme: config.theme.name,
        };
      },
      update(data) {
        if (data.articles.length <= 0) {
          return [];
        }

        // Trigger to show related tab on sidepanel only when there are items.
        this.$emit('show-related');

        let results = data.articles.map(item => {
          const { bookmarked, extra, name, instanceid, reactions, url } = item;
          const { image, alt, timeview } = JSON.parse(extra);
          return {
            bookmarked,
            instanceid,
            image,
            alt,
            name,
            reactions,
            timeview,
            url,
          };
        });
        return results;
      },
    },
  },

  data() {
    return {
      articles: [],
    };
  },

  methods: {
    /**
     * @deprecated since 15.0
     */
    getRecommendations() {
      this.$apollo
        .query({
          query: getRecommendation,
          refetchAll: false,
          variables: {
            article_id: this.resourceId,
            source: UrlSourceType.article(this.resourceId),
            theme: config.theme.name,
          },
        })
        .then(({ data }) => {
          if (data.articles.length <= 0) {
            return;
          }

          // Trigger to show related tab on sidepanel only when there are items.
          this.$emit('show-related');

          this.articles = data.articles.map(item => {
            const {
              bookmarked,
              extra,
              name,
              instanceid,
              reactions,
              url,
            } = item;
            const { image, timeview } = JSON.parse(extra);
            return {
              bookmarked,
              instanceid,
              image,
              name,
              reactions,
              timeview,
              url,
            };
          });
        });
    },

    update(resourceId, bookmarked) {
      this.$apollo.mutate({
        mutation: updateBookmark,
        refetchAll: false,
        variables: {
          itemid: resourceId,
          component: 'engage_article',
          bookmarked,
        },
      });
    },
  },
};
</script>

<style lang="scss">
.tui-engageArticleRelated {
  & > * + * {
    margin-top: var(--gap-2);
  }
}
</style>

<lang-strings>
{
  "core":[
    "noresults"
  ]
}
</lang-strings>
