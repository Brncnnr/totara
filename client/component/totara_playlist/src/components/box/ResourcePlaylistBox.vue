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

  @author Kian Nguyen <kian.nguyen@totaralearning.com>
  @module totara_playlist
-->
<!-- A box to display all the playlists that contains the resource. -->
<template>
  <div class="tui-resourcePlaylistBox">
    <VirtualScroll
      :data-list="box.playlists"
      data-key="id"
      :aria-label="$str('playlists', 'totara_playlist')"
      :is-loading="$apollo.loading"
      @scrollbottom="loadMore"
    >
      <template v-slot:item="{ item: playlist }">
        <SummaryPlaylistCard
          :key="playlist.id"
          :data-id="playlist.id"
          :name="playlist.name"
          :url="playlist.url"
          :playlist-id="playlist.id"
          :author="playlist.user.fullname"
          :rated-count="playlist.rating.count"
          :rating="playlist.rating.rating"
          class="tui-resourcePlaylistBox__card"
        />
      </template>
    </VirtualScroll>
  </div>
</template>

<script>
import SummaryPlaylistCard from 'totara_playlist/components/card/SummaryPlaylistCard';
import VirtualScroll from 'tui/components/virtualscroll/VirtualScroll';

// GraphQL queries
import resourcePlaylists from 'totara_playlist/graphql/resource_playlists';

export default {
  components: {
    SummaryPlaylistCard,
    VirtualScroll,
  },

  props: {
    resourceId: {
      type: [Number, String],
      required: true,
    },

    urlSource: {
      type: String,
      default: '',
    },
  },

  apollo: {
    box: {
      query: resourcePlaylists,
      watchLoading(isLoading) {
        this.$emit('load-records', isLoading);
      },
      variables() {
        return {
          resource_id: this.resourceId,
          source: this.urlSource,
        };
      },

      update({ playlists, cursor }) {
        return {
          playlists: playlists,
          cursor: cursor,
        };
      },

      result({
        data: {
          cursor: { total },
        },
      }) {
        total = parseInt(total, 9);
        this.$emit('update-has-playlists', 0 < total);
      },
    },
  },

  data() {
    return {
      box: {
        playlists: [],
        cursor: null,
      },
    };
  },

  methods: {
    loadMore() {
      if (!this.box.cursor.next) {
        return;
      }

      this.$apollo.queries.box.fetchMore({
        variables: {
          cursor: this.box.cursor.next,
          resource_id: this.resourceId,
          source: this.urlSource,
        },

        updateQuery(
          { playlists: oldPlaylists },
          { fetchMoreResult: { playlists, cursor } }
        ) {
          return {
            playlists: Array.prototype.concat(oldPlaylists, playlists),
            cursor: cursor,
          };
        },
      });
    },
  },
};
</script>

<lang-strings>
  {
    "totara_playlist": [
      "playlists"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-resourcePlaylistBox {
  display: flex;
  flex-direction: column;

  &__card {
    padding: var(--gap-2) 0;

    &:not(:last-child) {
      border-bottom: var(--border-width-thin) solid var(--color-neutral-5);
    }
  }
}
</style>
