<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2021 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD's customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totaralearning.com] for more information.

  @author Brian Barnes <brian.barnes@totaralearning.com>
  @module mod_approval
-->
<!-- TODO TL-33917: shift to core -->
<template>
  <Card
    :clickable="true"
    :has-hover-shadow="true"
    class="tui-mod_approval-responseCard"
    @click.native="respond"
  >
    <Avatar
      class="tui-mod_approval-responseCard__avatar"
      size="xsmall"
      alt=""
      :src="user.profileimageurlsmall"
    />
    <p class="tui-mod_approval-responseCard__username">
      {{ $str('applicant_x', 'mod_approval', user.fullname) }}
    </p>
    <p class="tui-mod_approval-responseCard__submitted">
      {{ $str('submitted_on_x', 'mod_approval', submitted) }}
    </p>
    <h3
      ref="typeName"
      class="tui-mod_approval-responseCard__workflow"
      :class="{
        'tui-mod_approval-responseCard__workflow--loading': !sizeDetected,
      }"
      :title="title"
    >
      <a :href="actionUrl">
        <span aria-hidden="true">{{ displayType }}</span>
        <span
          ref="detector"
          :class="{
            'tui-mod_approval-responseCard__workflowDetector': allShown,
          }"
          aria-hidden="true"
        >
          &#8230;
        </span>
        <span class="tui-mod_approval-responseCard__workflowAccessible">
          {{ title }}
        </span>
      </a>
    </h3>
    <div>
      <Button
        :text="$str('respond', 'mod_approval')"
        class="tui-mod_approval-responseCard__respond"
        :styleclass="{ small: true }"
        :aria-label="
          $str('respond_to_application', 'mod_approval', {
            title,
            user: user.fullname,
          })
        "
      />
    </div>
  </Card>
</template>

<script>
import Avatar from 'tui/components/avatar/Avatar';
import Button from 'tui/components/buttons/Button';
import Card from 'tui/components/card/Card';
import ResizeObserver from 'tui/polyfills/ResizeObserver';
import pending from 'tui/pending';

const THROTTLE_UPDATE = 150;

export default {
  components: {
    Avatar,
    Button,
    Card,
  },

  props: {
    id: [Number, String],
    title: String,
    user: Object,
    submitted: String,
  },

  data() {
    return {
      displayCharacterCount: 0,
      sizeDetected: false,
      allShown: true,
      resizeKillFlag: false,
    };
  },

  computed: {
    /**
     * @returns {String} The characters that are being displayed
     */
    displayType() {
      return this.title.substring(0, this.displayCharacterCount);
    },

    /**
     * @returns {String} A URL that the location of the individual application
     */
    actionUrl() {
      return this.$url('/mod/approval/application/view.php', {
        application_id: this.id,
      });
    },
  },

  mounted() {
    this.displayCharacterCount = this.title.length;

    // Don't use throttle here as it is an async operation (and we may need to kill it while in progress)
    this.resizeObserver = new ResizeObserver(this.$_triggerDetection);
    this.resizeObserver.observe(this.$el);

    this.$_triggerDetection();
  },

  methods: {
    /**
     * Redirects the user to the correct location
     */
    respond(event) {
      if (event.target.tagName === 'A') {
        return;
      }
      window.location = this.actionUrl;
    },

    /**
     * Checks to see if there is a need to re-check how many characters can be displayed of the workflow type
     *
     * Note: the standard throttle is not used here as it triggers an asynchronous function which may need to
     * be cleared
     */
    $_triggerDetection() {
      if (!this.resizeKillFlag) {
        let complete = pending('mod_approval__responseCard_recheck');
        setTimeout(async () => {
          this.resizeKillFlag = false;
          await this.$_detectTypeLength();
          complete();
        }, THROTTLE_UPDATE);
      }

      this.resizeKillFlag = true;
    },

    /**
     * Detects how many characters can be displayed before needing to display ellipsis
     */
    async $_detectTypeLength() {
      this.displayCharacterCount = this.title.length;
      await this.$nextTick();
      if (!this.$refs.detector || !this.$refs.typeName) {
        return;
      }
      let parent = this.$refs.typeName.getBoundingClientRect();
      let detector = this.$refs.detector.getBoundingClientRect();
      let min = 0;
      let max = this.title.length;
      this.sizeDetected = false;
      this.allShown = false;

      if (detector.bottom < parent.bottom) {
        this.sizeDetected = true;
        this.allShown = true;
        return;
      }

      do {
        // Let the DOM be updated
        await this.$nextTick();

        if (this.resizeKillFlag) {
          return;
        }

        // Re-measure and continue working
        detector = this.$refs.detector.getBoundingClientRect();

        if (detector.bottom <= parent.bottom) {
          min = this.displayCharacterCount;
        } else if (detector.bottom > parent.bottom) {
          max = this.displayCharacterCount;
        }
        this.displayCharacterCount = Math.floor((max + min) / 2);
      } while (min + 1 < max);

      this.sizeDetected = true;
    },
  },
};
</script>

<lang-strings>
  {
    "mod_approval": [
      "respond",
      "applicant_x",
      "submitted_on_x",
      "respond_to_application"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-mod_approval-responseCard {
  flex-direction: column;
  height: 100%;
  padding: var(--gap-4);

  &__username {
    @include tui-font-heading-label-small();
    margin-top: var(--gap-2);
    margin-bottom: 0;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
  }

  &__submitted {
    @include tui_font-body-x-small;
    margin-top: var(--gap-1);
    margin-bottom: 0;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
  }

  &__workflow {
    @include tui-font-heading-small-regular();
    height: 3.8em;
    margin: var(--gap-4) 0;
    overflow: hidden;

    &--loading {
      visibility: none;
    }

    a {
      display: block;
      height: 100%;
      color: var(--color-text);
    }
  }

  &__workflowAccessible {
    @include sr-only;
  }

  &__workflowDetector {
    visibility: hidden;
  }
}
</style>
