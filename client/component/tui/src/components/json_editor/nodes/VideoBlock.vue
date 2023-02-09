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
  @module totara_core
-->

<template>
  <div class="tui-videoBlock">
    <video ref="videojs" class="video-js">
      <source :src="url" :type="mimeType" />
      <track
        v-if="subtitleUrl"
        kind="captions"
        :src="subtitleUrl"
        :srclang="$str('iso6391', 'langconfig')"
        :label="$str('caption_on', 'editor')"
        default
      />
    </video>
  </div>
</template>

<script>
export default {
  inheritAttrs: false,

  props: {
    mimeType: {
      type: String,
      required: true,
    },

    url: {
      type: String,
      required: true,
    },

    filename: {
      type: String,
      required: true,
    },

    /**
     * The url for subtitle file
     */
    subtitleUrl: String,
  },

  computed: {
    /** @deprecated since 13.3 */
    attributes: () => null,

    config() {
      return {
        controls: true,
        controlBar: {
          fullscreenToggle: true,
        },
        fluid: true,
      };
    },
  },

  async mounted() {
    await this.$nextTick();
    if (!this.$refs.videojs) {
      return;
    }

    const videojs = tui.defaultExport(await tui.import('ext_videojs/videojs'));
    if (this.isDestroyed) {
      return;
    }
    this.player = videojs(this.$refs.videojs, this.config);
  },

  beforeDestroy() {
    this.isDestroyed = true;
    if (this.player) {
      this.player.dispose();
      this.player = null;
    }
  },
};
</script>

<lang-strings>
{
  "editor": [
    "caption_on"
  ],
  "langconfig": [
    "iso6391"
  ]
}
</lang-strings>

<style lang="scss">
.tui-videoBlock {
  display: flex;
  width: 100%;
  max-width: var(--embedded-media-max-width);

  margin: var(--gap-8) 0;

  .video-js .vjs-control {
    white-space: nowrap;
  }
}

// Ensure the controls aren't incorrectly reversed in RTL
[dir='rtl'] {
  .tui-videoBlock {
    .video-js {
      .vjs-progress-holder .vjs-play-progress,
      .vjs-progress-holder .vjs-load-progress,
      .vjs-progress-holder .vjs-load-progress div,
      .vjs-volume-level {
        /*!rtl:ignore*/
        right: auto;
        /*!rtl:ignore*/
        left: 0;
      }

      .vjs-play-progress:before,
      .vjs-slider-horizontal .vjs-volume-level:before {
        /*!rtl:ignore*/
        right: -0.5em;
        /*!rtl:ignore*/
        left: auto;
      }
    }
  }
}
</style>
