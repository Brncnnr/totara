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
  <section class="tui-playlistNavigation">
    <!-- Playlists -->
    <div
      class="tui-engageNavigationPanel__category tui-engageNavigationPanel__contribute"
    >
      <h3 class="tui-engageNavigationPanel__header">
        {{ $str('yourplaylists', 'totara_playlist') }}
      </h3>
      <Contribute
        v-if="showContribute"
        :show-text="false"
        :show-icon="true"
        :styleclass="{ circle: true, xsmall: true, primary: false }"
        :aria-label="$str('contributeplaylist', 'totara_playlist')"
      >
        <template v-slot:modal>
          <ContributeModal :can-share="canShare" />
        </template>
      </Contribute>
    </div>
    <template v-if="!$apollo.loading">
      <div
        v-for="(playlist, i) in collection.own"
        :key="`playlistLink_${i}`"
        :class="getNavigationLinkClass('playlist_' + playlist.id, i)"
      >
        <a class="tui-engageNavigationPanel__link-text" :href="playlist.url">
          {{ playlist.name }}
        </a>
      </div>
    </template>

    <!-- Saved Playlists -->
    <div class="tui-engageNavigationPanel__category">
      <h3 class="tui-engageNavigationPanel__header">
        {{ $str('savedplaylists', 'totara_playlist') }}
      </h3>
    </div>
    <template v-if="!$apollo.loading">
      <div
        v-for="(playlist, i) in collection.saved"
        :key="`playlistSaved_${i}`"
        :class="getNavigationLinkClass('playlist_' + playlist.id, i)"
      >
        <a class="tui-engageNavigationPanel__link-text" :href="playlist.url">
          {{ playlist.name }}
        </a>
      </div>
    </template>
  </section>
</template>

<script>
import Contribute from 'totara_engage/components/contribution/Contribute';
import ContributeModal from 'totara_playlist/components/modal/ContributeModal';

// GraphQL
import playlistLinks from 'totara_playlist/graphql/playlist_links';

// Mixins
import NavigationMixin from 'totara_engage/mixins/navigation_mixin';

export default {
  components: {
    Contribute,
    ContributeModal,
  },

  mixins: [NavigationMixin],

  data() {
    return {
      collection: {
        own: [],
        saved: [],
      },
    };
  },

  apollo: {
    collection: {
      query: playlistLinks,
      update({ saved_playlists, own_playlists }) {
        return {
          saved: saved_playlists,
          own: own_playlists,
        };
      },
    },
  },
};
</script>

<lang-strings>
{
  "totara_playlist": [
    "yourplaylists",
    "savedplaylists",
    "contributeplaylist"
  ]
}
</lang-strings>

<style lang="scss">
.tui-playlistNavigation {
  .tui-iconBtn--small {
    width: 2rem;
    height: 2rem;
  }
}
</style>
