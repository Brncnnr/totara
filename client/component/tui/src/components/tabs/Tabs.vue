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

  @author Simon Chester <simon.chester@totaralearning.com>
  @module tui
-->

<template>
  <div
    class="tui-tabs"
    :class="[
      'tui-tabs--' + direction,
      contentSpacing ? 'tui-tabs--contentSpacing-' + contentSpacing : null,
      fill ? 'tui-tabs--fill' : null,
    ]"
    :aria-orientation="direction"
  >
    <OverflowDetector v-slot="{ measuring }" @change="overflowChanged">
      <ul role="tablist" class="tui-tabs__tabs">
        <li
          v-for="(tab, i) in tabs"
          :key="i"
          class="tui-tabs__tab"
          :class="{
            'tui-tabs__tab--active': tab.active,
            'tui-tabs__tab--disabled': tab.disabled,
            'tui-tabs__tab--small': smallTabs,
            'tui-tabs__tab--hidden': tab.hidden || (overflowing && !measuring),
          }"
          role="presentation"
        >
          <a
            :id="'tab-' + tab.htmlId"
            ref="tabs"
            :aria-selected="tab.active"
            :aria-disabled="tab.disabled"
            :aria-controls="tab.active ? 'tabpanel-' + tab.htmlId : false"
            href="#"
            class="tui-tabs__link"
            role="tab"
            :tabindex="tab.active ? false : -1"
            @click.prevent="selectTab(tab, $event)"
            @keydown="handleTabKeydown"
          >
            <span class="tui-tabs__tabLabel">{{ tab.name }}</span>
          </a>
        </li>

        <!-- Fallback select list when there isn't enough space -->
        <li v-if="overflowing && !measuring" class="tui-tabs__selector">
          <Form>
            <FormRow
              v-slot="{ id }"
              :label="$str('select_a_tab', 'totara_core')"
            >
              <Select
                :id="id"
                :value="currentSelected"
                :options="selectOptions"
                @input="selectTabId"
              />
            </FormRow>
          </Form>
        </li>
      </ul>
    </OverflowDetector>
    <div class="tui-tabs__panels">
      <PropsProvider :provide="provideSlot">
        <slot />
      </PropsProvider>
    </div>
  </div>
</template>

<script>
import Form from 'tui/components/form/Form';
import FormRow from 'tui/components/form/FormRow';
import OverflowDetector from 'tui/components/util/OverflowDetector';
import PropsProvider from 'tui/components/util/PropsProvider';
import Select from 'tui/components/form/Select';

const events = {
  horizontal: {
    prev: ['Left', 'ArrowLeft'],
    next: ['Right', 'ArrowRight'],
  },
  vertical: {
    prev: ['Left', 'ArrowLeft', 'Up', 'ArrowUp'],
    next: ['Right', 'ArrowRight', 'Down', 'ArrowDown'],
  },
};

export default {
  components: {
    Form,
    FormRow,
    OverflowDetector,
    PropsProvider,
    Select,
  },

  model: {
    prop: 'selected',
    event: 'input',
  },

  props: {
    controlled: Boolean,
    selected: [String, Number],
    direction: {
      type: String,
      default: 'horizontal',
      validator: x => ['horizontal', 'vertical'].includes(x),
    },
    smallTabs: {
      type: Boolean,
    },
    contentSpacing: {
      type: String,
      validator: x => !x || x == 'large',
    },
    /** Fill all available space? */
    fill: Boolean,
  },

  data() {
    return {
      selectOptions: [],
      overflowing: false,
      tabs: [],
      currentSelected: this.selected,
    };
  },

  watch: {
    selected(value) {
      this.currentSelected = value;
    },
  },

  mounted() {
    // exclude child components that were defined in this component (overflowDetector)
    this.tabs = this.$children.filter(x => x.$vnode.context != this);
    this.selectListTabs();

    if (this.currentSelected == null) {
      this.currentSelected = this.$_tabIdFromProps(this.tabs[0]);
    }
  },

  updated() {
    // exclude child components that were defined in this component (overflowDetector)
    this.tabs = this.$children.filter(x => x.$vnode.context != this);
    this.selectListTabs();

    // check if focus is on a tab that is not selected, if so chg
    // use role[tab] and aria-selected to determine
    if (
      this.$refs.tabs !== undefined &&
      this.$refs.tabs.includes(document.activeElement)
    ) {
      const currentSelectedEl = this.$refs.tabs.find(x =>
        x.getAttribute('aria-selected')
      );
      if (currentSelectedEl && currentSelectedEl != document.activeElement) {
        currentSelectedEl.focus();
      }
    }
  },

  methods: {
    provideSlot({ props }) {
      const id = this.$_tabIdFromProps(props);
      return {
        props: {
          active: this.currentSelected != null && this.currentSelected === id,
        },
      };
    },

    $_tabIdFromProps(comp) {
      return comp ? comp.id : null;
    },

    /**
     * Check if a clicked tab has been disabled, if not set it to the currently selected
     *
     * @param {Object} tab
     */
    selectTab(tab) {
      if (tab.disabled) {
        return;
      }
      this.$_setSelectedTab(tab);
    },

    selectTabId(id) {
      if (!this.controlled) {
        this.currentSelected = id;
      }
      this.$emit('input', id);
    },

    handleTabKeydown(e) {
      const currentEvents = events[this.direction] || events.horizontal;
      if (currentEvents.prev.includes(e.key)) {
        e.preventDefault();
        this.navigateTabBy(-1);
      }
      if (currentEvents.next.includes(e.key)) {
        e.preventDefault();
        this.navigateTabBy(1);
      }
      if (e.key == 'Home') {
        e.preventDefault();
        this.$_setSelectedTab(this.tabs[0]);
      }
      if (e.key == 'End') {
        e.preventDefault();
        this.$_setSelectedTab(this.tabs[this.tabs.length - 1]);
      }
    },

    /**
     * Allow selected tab to be changed with left / right arrows
     *
     * @param {Integer} direction
     */
    navigateTabBy(direction) {
      direction = direction < 0 ? -1 : 1;

      let index = this.tabs.findIndex(
        x => this.$_tabIdFromProps(x) == this.currentSelected
      );

      if (index == -1) {
        // ensure index is within this.tabs otherwise we will get an infinite loop
        index = direction < 0 ? 0 : this.tabs.length - 1;
      }

      // find next non-disabled link
      let newIndex = index;
      do {
        newIndex += direction;
        if (newIndex < 0) {
          newIndex = this.tabs.length - 1;
        }
        if (newIndex >= this.tabs.length) {
          newIndex = 0;
        }
      } while (this.tabs[newIndex].disabled && newIndex != index);

      this.$_setSelectedTab(this.tabs[newIndex]);
    },

    /**
     * Create a list of tab options for a select list for when the tabs don't have enough space to display
     *
     * @return {Array}
     */
    selectListTabs() {
      this.selectOptions = this.tabs.map(tab => {
        return {
          id: tab.id,
          label: tab.name,
          disabled: tab.disabled,
        };
      });
    },

    /**
     * Set the currently selected tab ID
     *
     * @param {Object} tab
     */
    $_setSelectedTab(tab) {
      const id = this.$_tabIdFromProps(tab);

      if (!this.controlled) {
        this.currentSelected = id;
      }

      this.$emit('input', id);
    },

    /**
     * Switch vertical Bool to true when content is overflowing
     */
    overflowChanged({ overflowing }) {
      if (this.direction === 'vertical') {
        return;
      }
      this.overflowing = overflowing;
    },
  },
};
</script>

<lang-strings>
  {
    "totara_core": [
      "select_a_tab"
    ]
  }
</lang-strings>

<style lang="scss">
:root {
  --tab-border-width: 1px;
  // Tab inner horizontal padding
  --tab-h-padding: var(--gap-6);
  // Tab inner vertical padding
  --tab-v-padding: var(--gap-3);
  // Size of Highlight
  --tab-highlight-height: var(--gap-1);
  // Add extra spacing for drop shadow to be displayed
  --tab-shadow-offset: var(--gap-3);
  // Tab small version inner horizontal padding
  --tab-small-h-padding: var(--gap-4);
  // Tab small version inner vertical padding
  --tab-small-v-padding: var(--gap-3);
}

.tui-tabs {
  $mod-fill: #{&}--fill;
  $mod-horizontal: #{&}--horizontal;
  $mod-vertical: #{&}--vertical;

  &--fill {
    display: flex;
    flex-direction: column;
    flex-grow: 1;
    height: 100%;
  }

  &--vertical {
    display: flex;
    flex-direction: row;
  }

  &__selector {
    list-style: none;
  }

  &__tabs {
    display: flex;
    align-items: flex-end;
    margin: 0;
    padding: 0;

    #{$mod-horizontal} & {
      border-bottom: var(--tab-border-width) solid;
      border-bottom-color: var(--tabs-border-color);
    }

    #{$mod-vertical} & {
      border-right: var(--tab-border-width) solid;
      border-right-color: var(--tabs-border-color);
    }
  }

  #{$mod-vertical} &__tabs {
    flex-direction: column;
    align-items: stretch;
  }

  &__tab {
    display: block;
    overflow: hidden;
    pointer-events: none;

    #{$mod-horizontal} & {
      margin: calc(var(--tab-shadow-offset) * -1);
      margin-bottom: calc(var(--tab-border-width) * -1);
      padding: var(--tab-shadow-offset);
      padding-bottom: var(--tab-border-width);
    }

    #{$mod-vertical} & {
      max-width: 220px;
      margin-right: calc(var(--tab-border-width) * -1);
      margin-bottom: calc(var(--tab-shadow-offset) * -1);
      padding-right: var(--tab-border-width);
      padding-bottom: var(--tab-shadow-offset);
    }

    &--hidden {
      display: none;
    }
  }

  a&__link {
    @include tui-font-link-large();
    display: flex;
    padding: var(--tab-v-padding) var(--tab-h-padding);
    color: var(--tabs-text-color);
    text-decoration: none;

    border: var(--tab-border-width) solid;
    border-color: transparent;

    pointer-events: auto;

    &:hover {
      color: var(--tabs-text-color-focus);
      background: var(--tabs-bg-color-focus);
    }

    &:focus {
      color: var(--tabs-text-color-focus);
      background: var(--tabs-bg-color-focus);
      outline: dashed 1px var(--color-state-focus);
      outline-offset: -0.75rem;
    }

    &:active,
    &:active:focus,
    &:active:hover {
      color: var(--tabs-text-color-active);
      outline: none;
    }

    #{$mod-horizontal} & {
      margin-top: var(--tab-highlight-height);
      // overlap edges to avoid double border
      margin-right: calc(var(--tab-border-width) * -1);
      border-bottom: none;
    }

    #{$mod-vertical} & {
      // overlap edges to avoid double border
      margin-bottom: calc(var(--tab-border-width) * -1);
      margin-left: var(--tab-highlight-height);
      border-right: none;
    }
  }

  &__tab--disabled a&__link {
    color: var(--tabs-text-color-disabled);
    cursor: default;
    pointer-events: none;
  }

  &__tab--active a&__link {
    position: relative;
    color: var(--tabs-text-color-selected);
    background: var(--tabs-bg-color-selected);

    #{$mod-horizontal} & {
      top: var(--tab-border-width);
      padding-top: calc(var(--tab-v-padding) - var(--tab-border-width));
      padding-bottom: calc(var(--tab-v-padding) + var(--tab-border-width));
      border-color: var(--tabs-border-color);
      box-shadow: var(--shadow-3);
    }

    #{$mod-vertical} & {
      left: var(--tab-border-width);
      padding-right: calc(var(--tab-v-padding) + var(--tab-border-width));
      padding-left: calc(var(--tab-h-padding) - var(--tab-border-width));
      border-color: var(--tabs-border-color);
      box-shadow: var(--shadow-2);
    }

    &::after {
      position: absolute;
      background: var(--tabs-selected-bar-color);
      content: '';

      #{$mod-horizontal} & {
        top: calc(var(--tab-highlight-height) * -1);
        right: 0;
        left: -1px;
        width: calc(100% + 2px);
        height: var(--tab-highlight-height);
      }

      #{$mod-vertical} & {
        top: 0;
        bottom: 0;
        left: calc(var(--tab-border-width) * -2);
        width: var(--tab-highlight-height);
        height: calc(100% + 1px);
      }
    }
  }

  &__tabLabel {
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
  }

  #{$mod-fill} > &__panels {
    flex-grow: 1;
    > .tui-tabContent {
      height: 100%;
    }
  }
}

.tui-tabs {
  $mod-horizontal: #{&}--horizontal;
  $block: #{&};

  // Small tab
  &__tab--small {
    #{$mod-horizontal} & {
      #{$block}__link {
        @include tui-font-body-small;
        padding: var(--tab-small-v-padding) var(--tab-small-h-padding);
      }
    }
  }

  // Active small tab
  &__tab--active&__tab--small {
    #{$mod-horizontal} & {
      #{$block}__link {
        padding-top: calc(var(--tab-small-v-padding) - var(--tab-border-width));
        // prettier-ignore
        padding-bottom: calc(var(--tab-small-v-padding) + var(--tab-border-width));
        color: var(--tabs-text-color-selected);
      }
    }
  }

  // Disabled small tab
  &__tab--disabled&__tab--small {
    #{$mod-horizontal} & {
      #{$block}__link {
        color: var(--tabs-text-color-disabled);
        cursor: default;
        pointer-events: none;
      }
    }
  }
}

.tui-tabContent {
  .tui-tabs--horizontal & {
    padding-top: var(--gap-4);
  }

  .tui-tabs--horizontal.tui-tabs--contentSpacing-large & {
    padding-top: var(--gap-8);
  }

  .tui-tabs--vertical & {
    padding-left: var(--gap-4);
  }

  .tui-tabs--vertical.tui-tabs--contentSpacing-large & {
    padding-left: var(--gap-8);
  }
}
</style>
