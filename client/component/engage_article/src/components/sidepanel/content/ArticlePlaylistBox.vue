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
  @module engage_article
-->
<!-- A box to display playlists that this article is being added into -->
<template>
  <div class="tui-engageArticlePlaylistBox">
    <template v-if="show">
      <p class="tui-engageArticlePlaylistBox__label">
        <span>
          {{ $str('appears_in', 'engage_article') }}
        </span>
        <Loading v-if="loading" />
      </p>

      <ResourcePlaylistBox
        class="tui-engageArticlePlaylistBox__playlistsBox"
        :resource-id="resourceId"
        :url-source="urlSource"
        @update-has-playlists="show = $event"
        @load-records="loading = $event"
      />
    </template>
  </div>
</template>

<script>
import ResourcePlaylistBox from 'totara_playlist/components/box/ResourcePlaylistBox';
import Loading from 'tui/components/icons/Loading';
import { UrlSourceType } from 'totara_engage/index';

export default {
  components: {
    ResourcePlaylistBox,
    Loading,
  },

  props: {
    resourceId: {
      type: [String, Number],
      required: true,
    },
  },

  data() {
    return {
      loading: false,
      show: true,
    };
  },

  computed: {
    urlSource() {
      return UrlSourceType.article(this.resourceId);
    },
  },
};
</script>

<lang-strings>
  {
    "engage_article": [
      "appears_in"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-engageArticlePlaylistBox {
  &__playlistsBox {
    max-height: 300px;
    overflow-y: auto;
    border-top: var(--border-width-thin) solid var(--color-neutral-5);
    border-bottom: var(--border-width-thin) solid var(--color-neutral-5);
  }

  &__label {
    @include tui-font-heading-label-small();
    margin: 0;
    margin-bottom: var(--gap-2);
  }
}
</style>
