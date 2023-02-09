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
  @author Alvin Smith <alvin.smith@totaralearning.com>
  @module totara_playlist
-->

<template>
  <div class="tui-playlistSummary">
    <InlineEditing
      v-if="!editing"
      :full-width="true"
      :update-able="updateAble"
      :button-aria-label="$str('adddescription', 'totara_playlist')"
      @click="editing = true"
    >
      <template v-slot:content>
        <div
          v-if="summary"
          ref="content"
          class="tui-playlistSummary__content"
          v-html="summary"
        />
        <div
          v-else-if="!summary && updateAble"
          class="tui-playlistSummary__placeholder"
        >
          {{ $str('adddescription', 'totara_playlist') }}
        </div>
      </template>
    </InlineEditing>

    <Form v-else>
      <UnsavedChangesWarning
        v-if="!content.isEmpty && !submitting"
        :value="content"
      />
      <Weka
        v-model="content"
        :usage-identifier="{
          component: 'totara_playlist',
          area: 'summary',
          instanceId: instanceId,
        }"
        :compact="true"
        variant="standard"
        :extra-extensions="['hashtag', 'mention']"
        :placeholder="$str('adddescription', 'totara_playlist')"
        class="tui-playlistSummary__editor"
      />
      <DoneCancelGroup
        :loading="submitting"
        :disabled="submitting"
        @done="submit"
        @cancel="editing = false"
      />
    </Form>
  </div>
</template>

<script>
import tui from 'tui/tui';
import { FORMAT_JSON_EDITOR } from 'tui/format';
import Weka from 'editor_weka/components/Weka';
import WekaValue from 'editor_weka/WekaValue';

import DoneCancelGroup from 'totara_engage/components/buttons/DoneCancelGroup';
import InlineEditing from 'totara_engage/components/form/InlineEditing';
import UnsavedChangesWarning from 'totara_engage/components/form/UnsavedChangesWarning';
import getPlaylist from 'totara_playlist/graphql/get_playlist_raw';
import updatePlaylist from 'totara_playlist/graphql/update_playlist_summary';

export default {
  components: {
    DoneCancelGroup,
    InlineEditing,
    Weka,
    UnsavedChangesWarning,
  },

  props: {
    instanceId: {
      type: [String, Number],
      required: true,
    },

    summary: {
      type: String,
      default: null,
    },

    updateAble: {
      type: Boolean,
      default: false,
    },
  },

  data() {
    return {
      editing: false,
      submitting: false,
      content: WekaValue.empty(),
    };
  },

  apollo: {
    playlist: {
      query: getPlaylist,
      fetchPolicy: 'network-only',
      skip() {
        return !this.editing;
      },
      variables() {
        return {
          id: this.instanceId,
        };
      },

      result({ data: { playlist } }) {
        if (playlist) {
          if (playlist.summary) {
            this.content = WekaValue.fromDoc(JSON.parse(playlist.summary));
          }
        }
      },
    },
  },
  mounted() {
    this.$_scan();
  },

  updated() {
    this.$_scan();
  },

  methods: {
    handleUpdate(opt) {
      this.$_readJson(opt);
    },

    submit() {
      this.submitting = true;

      this.$apollo
        .mutate({
          mutation: updatePlaylist,
          variables: {
            id: this.instanceId,
            summary: JSON.stringify(this.content.getDoc()),
            summary_format: FORMAT_JSON_EDITOR,
          },

          /**
           *
           * @param {DataProxy} proxy
           * @param {Object} data
           */
          updateQuery: (proxy, data) => {
            proxy.writeQuery({
              query: getPlaylist,
              variables: {
                id: this.instanceId,
              },
              data,
            });
          },
        })
        .finally(() => {
          this.editing = false;
          this.submitting = false;
        });
    },

    $_scan() {
      if (!this.$refs.content) {
        return;
      }

      let element = this.$refs.content;
      tui.scan(element);
    },
  },
};
</script>

<lang-strings>
  {
    "totara_playlist": [
      "adddescription"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-playlistSummary {
  margin: var(--gap-8) 0;

  > p {
    margin: 0;
  }

  &__placeholder {
    color: var(--color-state);
  }
}
</style>
