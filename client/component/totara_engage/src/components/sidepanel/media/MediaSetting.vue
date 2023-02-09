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

  @author Alvin Smith <alvin.smith@totaralearning.com>
  @module totara_engage
-->

<template>
  <div class="tui-mediaSetting">
    <SidePanelLike
      v-if="showLikeButton"
      :instance-id="instanceId"
      :component="componentName"
      :button-aria-label="likeButtonAriaLabel"
      :liked="liked"
      area="media"
      @created-like="$emit('update-like-status', true)"
      @removed-like="$emit('update-like-status', false)"
    />
    <Share
      v-if="showShareButton"
      :instance-id="instanceId"
      :component="componentName"
      :button-aria-label="shareButtonAriaLabel"
      :owned="owned"
      :access-value="accessValue"
      :shared-by-count="sharedByCount"
      @access-modal="openAccessModal"
    />
  </div>
</template>

<script>
import Share from 'totara_engage/components/sidepanel/media/Share';
import SidePanelLike from 'totara_reaction/components/SidePanelLike';
import { AccessManager } from 'totara_engage/index';

export default {
  components: {
    Share,
    SidePanelLike,
  },

  props: {
    owned: {
      type: Boolean,
      required: true,
    },
    accessValue: {
      type: String,
      required: true,
      validator(prop) {
        return AccessManager.isValid(prop);
      },
    },
    instanceId: {
      type: [String, Number],
      required: true,
    },
    likeButtonAriaLabel: {
      type: String,
      default: '',
    },
    showLikeButton: {
      type: Boolean,
      default: true,
    },
    showShareButton: {
      type: Boolean,
      default: true,
    },
    componentName: {
      type: String,
      required: true,
    },
    shareButtonAriaLabel: {
      type: String,
      default: '',
    },
    sharedByCount: {
      type: Number,
      required: true,
    },
    liked: Boolean,
  },

  methods: {
    openAccessModal() {
      this.$emit('access-modal');
    },
  },
};
</script>

<style lang="scss">
.tui-mediaSetting {
  display: inline-flex;
  margin-top: var(--gap-8);
  > * {
    margin-right: var(--gap-3);
  }
}
</style>
