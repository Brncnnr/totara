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

  @author Kevin Hottinger <kevin.hottinger@totaralearning.com>
  @module tui
-->

<template>
  <div class="tui-hideShow" :class="{ 'tui-hideShow--mobile': mobileOnly }">
    <!-- Toggled content (before) -->
    <div
      v-if="contentBeforeToggle"
      :id="$id('region')"
      ref="content"
      :aria-label="ariaRegionLabel"
      class="tui-hideShow__content"
      :class="{ 'tui-hideShow__content--show': expanded }"
      role="region"
      tabindex="-1"
    >
      <slot name="content" />
    </div>

    <!-- Toggle control -->
    <div
      ref="toggle"
      class="tui-hideShow__toggle"
      :class="{ 'tui-hideShow__toggle--sticky': sticky }"
    >
      <slot
        name="trigger"
        :controls="$id('region')"
        :expanded="expanded"
        :text="toggleButtonString"
        :toggle-content="toggleContent"
      />
    </div>

    <!-- Toggled content (after) -->
    <div
      v-if="!contentBeforeToggle"
      :id="$id('region')"
      :aria-label="ariaRegionLabel"
      class="tui-hideShow__content"
      :class="{ 'tui-hideShow__content--show': expanded }"
      role="region"
    >
      <slot ref="content" name="content" />
    </div>
  </div>
</template>

<script>
export default {
  props: {
    // String describing the region content
    ariaRegionLabel: String,
    // String displayed for hiding content
    hideContentText: String,
    // Collapsible is for mobile viewports only
    mobileOnly: Boolean,
    // Optional string displayed for showing content
    showContentText: String,
    // Makes the toggle sticky to window (not supported in IE)
    sticky: Boolean,
    // Display the toggle after the content
    contentBeforeToggle: Boolean,
    // Start with content showing.
    initiallyExpanded: {
      default: false,
      type: Boolean,
    },
  },

  data() {
    return {
      expanded: this.initiallyExpanded,
    };
  },

  computed: {
    /**
     * Provide string for toggle button
     *
     * @return {String}
     */
    toggleButtonString() {
      return this.expanded ? this.hideContentText : this.showContentText;
    },
  },

  methods: {
    /**
     * When hiding content on mobile only it is usually larger blocks
     * So set the view back to the toggle when collapsing content
     *
     */
    scrollToToggle() {
      let toggle = this.$refs['toggle'];
      this.$nextTick(() => {
        toggle.scrollIntoView();
      });
    },

    /**
     * Toggle visibility of content
     *
     */
    toggleContent() {
      this.expanded = !this.expanded;

      // Set focus onto expanded items
      if (this.expanded && this.contentBeforeToggle) {
        this.$nextTick(() => {
          this.$refs['content'].focus();
        });
      }

      if (!this.expanded && this.mobileOnly) {
        this.scrollToToggle();
      }
    },
  },
};
</script>

<style lang="scss">
.tui-hideShow {
  display: flex;
  flex-direction: column;
  background: var(--color-neutral-1);

  &__content {
    display: none;
    &--show {
      display: block;
      outline: none;
    }
  }

  &__toggle {
    display: flex;
    padding: var(--gap-2) 0;

    &--sticky {
      position: sticky;
      top: 0;
      z-index: 1;
      background: inherit;
    }
  }
}

@media screen and (min-width: $tui-screen-sm) {
  .tui-hideShow--mobile {
    & > .tui-hideShow__toggle {
      display: none;
    }

    & > .tui-hideShow__content {
      display: block;
    }
  }
}
</style>
