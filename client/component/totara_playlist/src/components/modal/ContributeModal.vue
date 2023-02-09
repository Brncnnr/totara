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
  <Modal
    size="large"
    :aria-labelledby="$id('title')"
    :dismissable="dismissable"
  >
    <ModalContent
      class="tui-playlistContributeModal"
      :close-button="false"
      :title="getTitle"
      :content-padding="false"
    >
      <div class="tui-playlistContributeModal__content">
        <CreatePlaylist
          :can-share="canShare"
          @cancel="$emit('request-close')"
          @change-title="stage = $event"
        />
      </div>
    </ModalContent>
  </Modal>
</template>

<script>
import Modal from 'tui/components/modal/Modal';
import ModalContent from 'tui/components/modal/ModalContent';
import CreatePlaylist from 'totara_playlist/components/CreatePlaylist';

export default {
  components: {
    CreatePlaylist,
    Modal,
    ModalContent,
  },

  props: {
    canShare: Boolean,
  },

  data() {
    return {
      size: 'large',
      dismissable: {
        overlayClose: false,
        esc: true,
        backdropClick: false,
      },
      hideTabs: false,
      stage: 0,
    };
  },

  computed: {
    getTitle() {
      if (this.stage === 1) {
        return this.$str('accesssettings', 'totara_playlist');
      }
      return this.$str('contribute', 'totara_playlist');
    },
  },
};
</script>

<lang-strings>
{
  "totara_playlist": [
    "accesssettings",
    "pluginname",
    "contribute"
  ]
}
</lang-strings>

<style lang="scss">
.tui-playlistContributeModal {
  position: relative;

  &__content {
    position: relative;
    display: flex;
    flex: 1;
    flex-direction: column;
    min-height: 550px;
    padding: var(--gap-8);
  }
}
</style>
