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
  @package totara_playlist
-->

<template>
  <div v-if="playlists.length > 0" class="tui-playlistRelated">
    <article
      v-for="{
        bookmarked,
        fullname,
        instanceid,
        image,
        alt,
        name,
        rating,
        resources,
        url,
      } in playlists"
      :key="instanceid"
    >
      <RelatedCard
        :playlist-id="instanceid"
        :bookmarked="bookmarked"
        :fullname="fullname"
        :image="image"
        :image-alt="alt"
        :name="name"
        :rating="rating"
        :resources="resources"
        :url="url"
        @update="update"
      />
    </article>
  </div>
  <span v-else-if="!$apollo.loading" class="tui-playlistRelated__noResults">
    {{ $str('noresults', 'core') }}
  </span>
</template>

<script>
import RelatedCard from 'totara_playlist/components/card/RelatedCard';
import { UrlSourceType } from 'totara_engage/index';
import { config } from 'tui/config';

import getRecommendation from 'ml_recommender/graphql/get_recommended_playlists';
import updateBookmark from 'totara_engage/graphql/update_bookmark';

export default {
  components: {
    RelatedCard,
  },
  props: {
    playlistId: {
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
          playlist_id: this.playlistId,
          source: UrlSourceType.article(this.playlistId),
          theme: config.theme.name,
        };
      },
      update(data) {
        if (data.playlists.length <= 0) {
          return [];
        }

        // Trigger to show related tab on sidepanel only when there are items.
        this.$emit('show-related');

        let results = data.playlists.map(item => {
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
      playlists: [],
    };
  },

  mounted() {
    this.getRecommendations();
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
            playlist_id: this.playlistId,
            source: UrlSourceType.playlist(this.playlistId),
            theme: config.theme.name,
          },
        })
        .then(({ data }) => {
          if (data.playlists.length <= 0) {
            return;
          }

          // Trigger to show related tab on sidepanel only when there are items.
          this.$emit('show-related');

          this.playlists = data.playlists.map(item => {
            const { bookmarked, extra, name, instanceid, user, url } = item;
            const { image, alt, rating, resources } = JSON.parse(extra);
            const { fullname } = user;
            return {
              bookmarked,
              fullname,
              instanceid,
              image,
              alt,
              name,
              rating,
              resources,
              url,
            };
          });
        });
    },

    update(playlistId, bookmarked) {
      this.$apollo.mutate({
        mutation: updateBookmark,
        refetchAll: false,
        variables: {
          itemid: playlistId,
          component: 'totara_playlist',
          bookmarked,
        },
      });
    },
  },
};
</script>

<style lang="scss">
.tui-playlistRelated {
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
