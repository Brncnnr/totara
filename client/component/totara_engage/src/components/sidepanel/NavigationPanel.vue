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
  @module totara_engage
-->

<template>
  <div
    :class="[
      'tui-engageNavigationPanel',
      `tui-engageNavigationPanel__${gridDirection}`,
    ]"
  >
    <div v-if="showMenuControl" class="tui-engageNavigationPanel__selected">
      <h2 class="tui-engageNavigationPanel__header">
        <Button
          :text="title"
          :caret="true"
          class="tui-engageNavigationPanel__btn"
          :styleclass="{ transparent: true }"
          :aria-expanded="showMenu.toString()"
          @click="toggleMenu"
        />
      </h2>
    </div>
    <div
      v-show="showMenu"
      ref="navigationPanelMenu"
      class="tui-engageNavigationPanel__menu"
    >
      <h2 class="sr-only">{{ $str('navigation', 'totara_engage') }}</h2>
      <!-- Sections -->
      <template v-if="!navPanelLoading">
        <template v-for="(section, i) in sections">
          <component
            :is="section.component"
            :key="i"
            :selected-id="selectedId"
            :values="values"
            :show-contribute="section.showcontribute"
            :can-share="section.can_share"
            :redirection="redirection"
          />
        </template>
      </template>
      <!-- /Sections -->
    </div>
  </div>
</template>

<script>
import tui from 'tui/tui';
import Button from 'tui/components/buttons/Button';
import Show from 'tui/components/icons/Show';
import EngageSection from 'totara_engage/components/sidepanel/navigation/EngageSection';

// Mixins
import NavigationMixin from 'totara_engage/mixins/navigation_mixin';

// GraphQL
import getNavigationPanelSections from 'totara_engage/graphql/navigation_panel_sections';

const has = Object.prototype.hasOwnProperty;

export default {
  components: {
    Button,
    Show,
    EngageSection,
  },

  mixins: [NavigationMixin],

  props: {
    title: {
      type: String,
      required: true,
    },
    units: {
      type: [String, Number],
      required: true,
    },
    gridDirection: {
      type: String,
      required: true,
    },
    redirection: Boolean,
  },

  data() {
    return {
      navPanelLoading: 0,
      menuVisible: false,
    };
  },

  apollo: {
    sections: {
      query: getNavigationPanelSections,
      loadingKey: 'navPanelLoading',
      result({ data: { sections } }) {
        if (typeof sections !== 'undefined' && 0 < sections.length) {
          this.$_loadTuiComponents(sections);
        }
      },
    },
  },

  computed: {
    /**
     * Show menu control if NavigationPanel is taking up the whole row of the grid.
     */
    showMenuControl() {
      return this.gridDirection === 'vertical';
    },
    /**
     * If NavigationPanel is taking up the whole grid row then return current toggled state.
     */
    showMenu() {
      if (this.showMenuControl) {
        return this.menuVisible;
      }
      return true;
    },
  },

  methods: {
    /**
     *
     * @param {Array} items
     */
    $_loadTuiComponents(items) {
      items.forEach(({ component, tuicomponent }) => {
        if (!has.call(this.$options.components, component)) {
          this.$options.components[component] = tui.asyncComponent(
            tuicomponent
          );
        }
      });
    },

    /**
     * Show/Hide navigation menu.
     */
    toggleMenu() {
      this.menuVisible = !this.menuVisible;
    },
  },
};
</script>

<lang-strings>
{
  "totara_engage": [
    "navigation"
  ]
}
</lang-strings>

<style lang="scss">
.tui-engageNavigationPanel {
  &__selected {
    margin-top: var(--gap-6);
    margin-bottom: var(--gap-2);
  }

  &__menu {
    height: 100%;
    overflow: auto;
  }

  &__category {
    @include tui-font-heading-label;
    display: flex;
    align-items: center;
    margin: var(--gap-8) var(--gap-4);
    margin-bottom: var(--gap-3);

    & > span {
      padding-right: var(--gap-2);
    }
  }

  &__header {
    margin-top: 0;
    margin-bottom: 0;
    @include tui-font-heading-label();
    & .tui-engageNavigationPanel__btn {
      @include tui-font-heading-small;
      color: var(--color-text);
      .tui-caret {
        color: var(--color-state);
      }
    }
  }

  &__contribute {
    align-items: center;
    justify-content: space-between;
  }

  &__link {
    display: flex;
    align-items: center;
    padding: var(--gap-1) var(--gap-4);

    a {
      text-decoration: none;
    }

    &--inactive {
      .tui-engageNavigationPanel__link-text {
        @include tui-font-link;
        color: var(--color-state-focus);
      }

      &:hover,
      &:focus {
        background-color: var(--color-state-highlight-neutral);
      }
    }

    &--active {
      background-color: var(--color-state-active);

      .tui-engageNavigationPanel__link-text {
        @include tui-font-link;
        color: var(--color-neutral-1);

        &:hover,
        &:focus {
          color: var(--color-neutral-1);
        }
      }
    }
  }

  /* Vertical grid styles */
  &__vertical {
    border-bottom: 1px solid var(--color-neutral-5);

    .tui-engageNavigationPanel {
      &__selected {
        padding: 0 var(--gap-4);
      }

      &__search {
        margin-bottom: var(--gap-8);
      }

      &__link {
        padding: var(--gap-2) var(--gap-4);
        border-bottom: 1px solid var(--color-neutral-4);

        &--first {
          border-top: 1px solid var(--color-neutral-4);
        }
      }

      &__menu {
        z-index: var(--zindex-dropdown-menu);
        width: 100%;
        margin-bottom: var(--gap-4);
        background-color: var(--color-neutral-3);
        border: 1px solid var(--color-neutral-5);
      }
    }
  }
}
</style>
