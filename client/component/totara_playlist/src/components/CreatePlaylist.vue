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

  @author Qingyang Liu <Qingyang.liu@totaralearning.com>
  @module torara_playlist
-->

<template>
  <div class="tui-createPlaylist">
    <PlaylistForm
      v-show="stage === 0"
      @next="next"
      @cancel="$emit('cancel')"
      @unsaved-changes="$emit('unsaved-changes')"
    />
    <AccessForm
      v-show="stage === 1"
      item-id="0"
      component="totara_playlist"
      :show-back="true"
      :submitting="submitting"
      :selected-access="containerValues.access || defaultAccess"
      :private-disabled="privateDisabled"
      :restricted-disabled="restrictedDisabled"
      :container="container"
      :enable-time-view="false"
      :can-share="canShare"
      @done="done"
      @back="back"
      @cancel="$emit('cancel')"
    />
  </div>
</template>

<script>
import PlaylistForm from 'totara_playlist/components/form/PlaylistForm';
import AccessForm from 'totara_engage/components/form/AccessForm';
import { AccessManager } from 'totara_engage/index';

// Graphql queries
import CreatePlaylist from 'totara_playlist/graphql/create_playlist';

// Mixins
import ContainerMixin from 'totara_engage/mixins/container_mixin';

export default {
  components: {
    PlaylistForm,
    AccessForm,
  },

  mixins: [ContainerMixin],

  props: {
    canShare: Boolean,
  },

  data() {
    return {
      stage: 0,
      maxStage: 1,
      playlist: {
        name: null,
        summary: null,
        summary_format: null,
      },
      submitting: false,
      defaultAccess: 'PRIVATE',
    };
  },

  computed: {
    privateDisabled() {
      return this.containerValues.access
        ? !AccessManager.isPrivate(this.containerValues.access)
        : false;
    },
    restrictedDisabled() {
      return this.containerValues.access
        ? AccessManager.isPublic(this.containerValues.access)
        : false;
    },
  },

  methods: {
    /**
     * @param {String} name
     * @param {String} summary
     * @param {Number} summary_format
     */
    next({ name, summary, summary_format }) {
      if (this.stage < this.maxStage) {
        this.stage += 1;
      }

      this.playlist.name = name;
      this.playlist.summary = summary;
      this.playlist.summary_format = summary_format;

      this.$emit('hide-tabs', true);
      this.$emit('change-title', this.stage);
    },
    back() {
      if (this.stage > 0) {
        this.stage -= 1;
      }

      this.$emit('hide-tabs', false);
      this.$emit('change-title', this.stage);
    },

    /**
     * @param {String} access
     * @param {Array} topics
     * @param {Array} shares
     */
    done({ access, topics, shares }) {
      this.submitting = true;
      this.$apollo
        .mutate({
          mutation: CreatePlaylist,
          variables: {
            name: this.playlist.name,
            summary: this.playlist.summary,
            summary_format: this.playlist.summary_format,
            access: access,
            topics: topics.map(topic => topic.id),
            shares: shares,
          },
          refetchQueries: [
            'totara_engage_contribution_cards',
            'container_workspace_contribution_cards',
            'container_workspace_shared_cards',
            'totara_playlist_playlist_links',
          ],
          awaitRefetchQueries: true,
          update: (cache, { data: { playlist } }) => {
            this.$emit('done', { id: playlist.id });

            window.location.href = this.$url('/totara/playlist/index.php', {
              id: playlist.id,
            });
          },
        })
        .then(() => this.$emit('cancel'))
        .finally(() => (this.submitting = false));
    },
  },
};
</script>

<style lang="scss">
.tui-createPlaylist {
  display: flex;
  flex: 1;
  flex-direction: column;
  width: 100%;
  height: 100%;
}
</style>
