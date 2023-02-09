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

  @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
  @module totara_playlist
-->

<template>
  <Responsive
    :breakpoints="breakPoints"
    class="tui-contributionPlaylistResources"
    @responsive-resize="size = $event"
  >
    <ContributionBaseContent
      :units="units"
      :loading="!!$apollo.loading"
      :loading-more="loadingMore"
      :total-cards="contribution.cursor.total"
      :show-heading="!$apollo.loading && gridDirection !== 'vertical'"
      :grid-direction="gridDirection"
    >
      <template v-slot:heading>
        <HeaderBox
          :playlist-id="pageProps.playlistId"
          :title="playlist.name"
          :owned="playlist.owned"
          @update-playlist="updatePlaylist"
        />
      </template>
      <template v-slot:bookmark>
        <BookmarkButton
          v-if="pageProps.interactor.can_bookmark"
          :primary="false"
          :circle="true"
          :bookmarked="bookmarked"
          size="300"
          @click="updateBookmark"
        />
      </template>
      <template v-if="contribution.cards && playlist.access" v-slot:cards>
        <PlaylistResourcesGrid
          :max-units="units"
          :playlist-id="pageProps.playlistId"
          :size="size"
          :library-view="true"
          :cards="contribution.cards"
          :contributable="playlist.contributable"
          :access="playlist.access"
          :update-able="playlist.updateable"
          :is-loading="$apollo.loading"
          :interactor="pageProps.interactor"
          @resource-reordered="resourceReordered"
        />
      </template>
    </ContributionBaseContent>
  </Responsive>
</template>

<script>
import Responsive from 'tui/components/responsive/Responsive';
import ContributionBaseContent from 'totara_engage/components/contribution/BaseContent';
import PlaylistResourcesGrid from 'totara_playlist/components/grid/PlaylistResourcesGrid';
import HeaderBox from 'totara_playlist/components/page/HeaderBox';
import BookmarkButton from 'totara_engage/components/buttons/BookmarkButton';
import { UrlSourceType } from 'totara_engage/index';
import { config } from 'tui/config';

// GraphQL
import getPlaylist from 'totara_playlist/graphql/get_playlist';
import getCards from 'totara_playlist/graphql/cards';
import updateBookmark from 'totara_engage/graphql/update_bookmark';
import updateCardOrder from 'totara_playlist/graphql/update_card_order';

// Mixins
import LibraryMixin from 'totara_engage/mixins/library_mixin';
import { playlistGrid } from 'totara_playlist/index';

export default {
  components: {
    Responsive,
    ContributionBaseContent,
    PlaylistResourcesGrid,
    HeaderBox,
    BookmarkButton,
  },

  mixins: [LibraryMixin],

  props: {
    units: {
      type: [Number, String],
      required: true,
    },
    gridDirection: {
      type: String,
      required: true,
    },
    pageProps: Object,
  },

  data() {
    return {
      playlist: {},
      contribution: {
        cursor: {
          total: 0,
          next: null,
        },
        cards: [],
      },
      size: '',
      bookmarked: false,
      loadingMore: false,
    };
  },

  computed: {
    breakPoints() {
      let breakPoints = Object.values(playlistGrid);
      return breakPoints.map(({ name, boundaries }) => {
        return { name, boundaries };
      });
    },
  },

  apollo: {
    playlist: {
      query: getPlaylist,
      fetchPolicy: 'network-only',
      variables() {
        return {
          id: this.pageProps.playlistId,
        };
      },
      result({ data: { playlist } }) {
        this.bookmarked = playlist.bookmarked;
      },
    },
    contribution: {
      query: getCards,
      fetchPolicy: 'network-only',
      variables() {
        return {
          id: this.pageProps.playlistId,
          source: UrlSourceType.playlist(this.pageProps.playlistId),
          footnotes_type: 'playlist',
          footnotes_item_id: this.pageProps.playlistId,
          include_footnotes: true,
          theme: config.theme.name,
        };
      },
    },
  },

  methods: {
    updateBookmark() {
      this.bookmarked = !this.bookmarked;
      this.$apollo.mutate({
        mutation: updateBookmark,
        refetchQueries: ['savedplaylists'],
        variables: {
          itemid: this.pageProps.playlistId,
          component: 'totara_playlist',
          bookmarked: this.bookmarked,
        },
      });
    },

    updatePlaylist(event) {
      // We want to propagate the title change as it could be used in other areas
      this.$emit('updateTitle', event.name);
    },

    resourceReordered(obj) {
      const { list, instanceid, destinationIndex, playlistId } = obj;
      this.contribution = Object.assign({}, this.contribution, { cards: list });
      this.$apollo.mutate({
        mutation: updateCardOrder,
        refetchQueries: [
          {
            query: getCards,
            variables: {
              id: playlistId,
              source: UrlSourceType.playlist(playlistId),
              theme: config.theme.name,
              include_footnotes: true,
            },
          },
        ],
        variables: {
          id: playlistId,
          instanceid,
          order: destinationIndex,
        },
      });
    },
  },
};
</script>

<lang-strings>
{
  "totara_engage": [
    "resourcecount"
  ],
  "engage_article":[
    "loadmore",
    "viewedresources"
  ]
}
</lang-strings>

<style lang="scss">
.tui-contributionPlaylistResources {
  display: flex;
  width: 100%;
  height: 100%;

  .tui-loader {
    flex-grow: 1;
  }

  &__loadMoreContainer {
    display: flex;
    flex-direction: column;
    justify-content: center;
  }

  &__viewedResources {
    display: flex;
    align-self: center;
    margin-bottom: var(--gap-1);
  }

  &__loadMore {
    display: flex;
    align-self: center;
  }

  .tui-contributionBaseContent {
    display: flex;
    flex-direction: column;
    width: 100%;

    &__counterContainer {
      margin-bottom: var(--gap-4);

      &__counter {
        position: relative;
        top: 0;
      }
    }
  }
}
</style>
