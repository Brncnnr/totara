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
  @module totara_engage
-->

<template>
  <div class="tui-playlistSidePanelContent">
    <PlaylistSidePanel
      :playlist-id="playlistId"
      :interactor="pageProps.interactor"
    />
  </div>
</template>

<script>
import PlaylistSidePanel from 'totara_playlist/components/sidepanel/PlaylistSidePanel';

// Mixins
import LibraryMixin from 'totara_engage/mixins/library_mixin';

export default {
  components: {
    PlaylistSidePanel,
  },

  mixins: [LibraryMixin],

  computed: {
    playlistId() {
      return this.pageProps.playlistId;
    },
  },
};
</script>

<style lang="scss">
.tui-playlistSidePanelContent {
  display: flex;
  flex-direction: column;
  flex-grow: 1;
}
</style>
