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

  @author Qingyang Liu <qingyang.liu@totaralearning.com>
  @module totara_playlist
-->

<template>
  <div class="tui-playlistFootnote">
    <ButtonIcon
      class="tui-playlistFootnote__button"
      :aria-label="$str('removeitem', 'totara_playlist')"
      :styleclass="{
        small: true,
        transparentNoPadding: true,
        alert: true,
      }"
      :disabled="loading"
      @click.prevent="removeResouce"
    >
      <Loading v-if="loading" />
      <Remove v-else :size="300" state="alert" />
    </ButtonIcon>
  </div>
</template>

<script>
import ButtonIcon from 'tui/components/buttons/ButtonIcon';
import Loading from 'tui/components/icons/Loading';
import Remove from 'tui/components/icons/Remove';

// GraphQL
import removeResource from 'totara_playlist/graphql/remove_resource';

export default {
  components: {
    ButtonIcon,
    Loading,
    Remove,
  },
  props: {
    instanceId: {
      type: Number,
      required: true,
    },
    playlistId: {
      type: Number,
      required: true,
    },
  },

  data() {
    return {
      loading: false,
    };
  },

  methods: {
    async removeResouce() {
      if (!this.loading) {
        this.loading = true;
      }

      try {
        await this.$apollo.mutate({
          mutation: removeResource,
          refetchQueries: [
            'totara_playlist_cards',
            'totara_playlist_get_playlist',
          ],
          variables: {
            id: this.playlistId,
            instanceid: this.instanceId,
          },
        });
      } finally {
        this.loading = false;
      }
    },
  },
};
</script>
<lang-strings>
  {
    "totara_playlist": [
      "removeitem"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-playlistFootnote {
  display: flex;
  flex-direction: row;
  justify-content: flex-end;
}
</style>
