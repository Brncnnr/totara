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
  <section class="tui-playlistPageHeaderBox">
    <InlineEditing
      v-if="!editing"
      :update-able="updateAble"
      :button-aria-label="$str('edit_playlist_title', 'totara_playlist')"
      :focus-button="focusEditButton"
      class="tui-playlistPageHeaderBox__titleBox"
      @click="editing = true"
    >
      <h1 slot="content" class="tui-playlistPageHeaderBox__title">
        {{ title }}
      </h1>
    </InlineEditing>

    <PlaylistTitleForm
      v-else
      :title="title"
      :focus-input="true"
      :submitting="submitting"
      @submit="updatePlaylistTitle"
      @cancel="editing = false"
    />
  </section>
</template>

<script>
import InlineEditing from 'totara_engage/components/form/InlineEditing';
import PlaylistTitleForm from 'totara_playlist/components/form/PlaylistTitleForm';
import { notify } from 'tui/notifications';

// GraphQL queries
import updatePlaylistName from 'totara_playlist/graphql/update_playlist_name';

export default {
  components: {
    InlineEditing,
    PlaylistTitleForm,
  },

  props: {
    playlistId: {
      type: Number,
      required: true,
    },
    title: {
      type: String,
      required: true,
    },

    updateAble: {
      type: Boolean,
      default: true,
    },
  },

  data() {
    return {
      editing: false,
      submitting: false,
      focusEditButton: false,
    };
  },

  watch: {
    /**
     * @param {Boolean} value
     */
    editing(value) {
      // Only make the edit button focus when we are not editing anymore.
      this.focusEditButton = !value;
    },
  },

  methods: {
    /**
     *
     * @param {String} title
     */
    async updatePlaylistTitle({ title }) {
      if (this.submitting) {
        return;
      }

      this.submitting = true;

      try {
        const {
          data: { playlist },
        } = await this.$apollo.mutate({
          mutation: updatePlaylistName,
          refetchAll: false,
          variables: {
            id: this.playlistId,
            name: title,
          },
        });

        this.editing = false;
        this.$emit('update-playlist', playlist);
      } catch (e) {
        await notify({
          message: this.$str('error:update', 'totara_playlist'),
          type: 'error',
        });
      } finally {
        this.submitting = false;
      }
    },
  },
};
</script>
<lang-strings>
  {
    "totara_playlist": [
      "error:update",
      "edit_playlist_title"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-playlistPageHeaderBox {
  &__titleBox {
    width: 100%;
  }

  &__title {
    @include tui-font-heading-medium;
    margin: 0;
  }
}
</style>
