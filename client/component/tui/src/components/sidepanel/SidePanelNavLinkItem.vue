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

  @author Kevin Hottinger <kevin.hottinger@totaralearning.com>
  @module tui
-->

<template>
  <li
    v-focus-within
    class="tui-sidePanelNavLinkItem"
    :class="{ 'tui-sidePanelNavLinkItem--active': activeItem }"
  >
    <span v-if="notification" class="tui-sidePanelNavLinkItem__notificationDot">
      <span class="tui-sidePanelNavButtonItem__notificationDot-inner" />
      <span :id="notificationTextId" class="sr-only">{{
        notificationText
      }}</span>
    </span>

    <a
      :href="url"
      class="tui-sidePanelNavLinkItem__action"
      :aria-current="activeItem ? 'location' : null"
      :aria-describedby="notification ? notificationTextId : null"
      @click="$emit('select', { action: url, id: id })"
    >
      {{ text }}
    </a>

    <div v-if="$scopedSlots.default" class="tui-sidePanelNavLinkItem__side">
      <slot />
    </div>
  </li>
</template>

<script>
export default {
  props: {
    active: [Boolean, Number, String],
    id: {
      type: [Number, String],
      required: true,
    },
    text: String,
    url: String,

    notification: Boolean,
    notificationText: {
      type: String,
      default() {
        return this.$str('updated_recently', 'totara_core');
      },
    },
  },

  data() {
    return {
      notificationTextId: this.$id('notification-dot'),
    };
  },

  computed: {
    /**
     * Check if this is the active item
     *
     * @return {Boolean}
     */
    activeItem() {
      if (this.active == this.id) {
        return true;
      }
      return false;
    },
  },
};
</script>

<lang-strings>
{
  "totara_core": [
    "updated_recently"
  ]
}
</lang-strings>

<style lang="scss">
.tui-sidePanelNavLinkItem {
  position: relative;
  display: flex;
  align-items: center;
  color: var(--side-panel-nav-item-text-color);
  background: var(--side-panel-nav-item-bg-color);
  border-color: var(--side-panel-nav-item-border-color);

  // Item li
  &.tui-focusWithin,
  &:hover {
    color: var(--side-panel-nav-item-text-color-focus);
    background: var(--side-panel-nav-item-bg-color-focus);
    border-color: var(--side-panel-nav-item-border-color-focus);
  }

  &__side {
    margin-left: auto;
    padding-right: var(--gap-4);
  }

  &__notificationDot {
    position: absolute;
    // The same as padding left of actioin plus another tui-gap-4 to make sure there is a padding between
    // the action link and the dot.
    width: var(--sidepanel-navigation-item-padding-left);
    pointer-events: none;

    &-inner {
      display: block;
      width: 0.6rem;
      height: 0.6rem;
      margin-left: var(--gap-3);
      background-color: var(--color-prompt-alert);
      border-radius: 100%;
    }
  }

  // Item link
  &__action {
    flex-grow: 1;
    padding: var(--gap-2) var(--gap-4);
    padding-left: var(--sidepanel-navigation-item-padding-left);
    color: inherit;
    line-height: 1;
    -ms-word-break: break-all;
    word-break: break-word;

    &:hover,
    &:focus,
    &:focus:hover {
      color: inherit;
    }
  }

  &--active {
    color: var(--side-panel-nav-item-text-color-selected);
    background: var(--side-panel-nav-item-bg-color-selected);
    border-color: var(--side-panel-nav-item-border-color-selected);

    &.tui-focusWithin,
    &:hover {
      color: var(--side-panel-nav-item-text-color-selected);
      background: var(--side-panel-nav-item-bg-color-selected);
      border-color: var(--side-panel-nav-item-border-color-selected);
    }
  }
}
</style>
