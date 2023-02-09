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
  @package mod_contentmarketplace
-->

<template>
  <BannerLayout
    :banner-image-url="bannerImageUrl"
    class="tui-marketplaceLayoutBannerSidepanelTwoColumn"
    :loading="loadingFullPage"
  >
    <!-- Page modal area -->
    <template v-slot:modals>
      <slot name="modals" />
    </template>

    <!-- Side content area -->
    <template v-slot:side-panel="{ outerStacked }">
      <aside class="tui-marketplaceLayoutBannerSidepanelTwoColumn__sidePanel">
        <slot name="side-panel" :stacked="outerStacked" />
      </aside>
    </template>

    <!-- Banner image content area -->
    <template
      v-if="$scopedSlots['banner-content']"
      v-slot:banner-content="{ outerStacked }"
    >
      <slot name="banner-content" :stacked="outerStacked" />
    </template>

    <template v-slot:main-content>
      <Loader
        :loading="loadingMainContent"
        class="tui-marketplaceLayoutBannerSidepanelTwoColumn__inner"
      >
        <Responsive
          :breakpoints="[
            { name: 'small', boundaries: [0, 852] },
            { name: 'medium', boundaries: [850, 972] },
            { name: 'large', boundaries: [970, 1122] },
            { name: 'xLarge', boundaries: [1120, 1681] },
          ]"
          @responsive-resize="resize"
        >
          <Grid
            class="tui-marketplaceLayoutBannerSidepanelTwoColumn__grid"
            :class="{
              'tui-marketplaceLayoutBannerSidepanelTwoColumn__grid--stacked': stacked,
            }"
            :direction="gridDirection"
            :max-units="24"
          >
            <!-- Left content -->
            <GridItem
              class="tui-marketplaceLayoutBannerSidepanelTwoColumn__main"
              :units="gridUnitsLeft"
            >
              <slot name="feedback-banner" />

              <slot name="user-overview" />

              <!-- Header content -->
              <div
                class="tui-marketplaceLayoutBannerSidepanelTwoColumn__heading"
              >
                <slot name="content-nav" />

                <PageHeading :title="title">
                  <template v-slot:buttons>
                    <slot name="header-buttons" />
                  </template>
                </PageHeading>
              </div>

              <slot name="main-content" />
            </GridItem>

            <!-- Right side content -->
            <GridItem
              class="tui-marketplaceLayoutBannerSidepanelTwoColumn__side"
              :units="gridUnitsRight"
            >
              <slot name="side-content" />
            </GridItem>
          </Grid>
        </Responsive>
      </Loader>
    </template>
  </BannerLayout>
</template>

<script>
import BannerLayout from 'mod_contentmarketplace/components/outer_layouts/OuterLayoutBannerSidepanel';
import Grid from 'tui/components/grid/Grid';
import GridItem from 'tui/components/grid/GridItem';
import Loader from 'tui/components/loading/Loader';
import PageHeading from 'tui/components/layouts/PageHeading';
import Responsive from 'tui/components/responsive/Responsive';

export default {
  components: {
    BannerLayout,
    Grid,
    GridItem,
    Loader,
    PageHeading,
    Responsive,
  },

  props: {
    // Image url for banner
    bannerImageUrl: [Boolean, String],
    // Display loader over all content
    loadingFullPage: Boolean,
    // Display loader over right column content
    loadingMainContent: Boolean,
    // Page title
    title: {
      required: true,
      type: String,
    },
  },

  data() {
    return {
      boundaryDefaults: {
        small: {
          gridDirection: 'vertical',
          gridUnitsLeft: 24,
          gridUnitsRight: 24,
        },
        medium: {
          gridDirection: 'horizontal',
          gridUnitsLeft: 16,
          gridUnitsRight: 8,
        },
        large: {
          gridDirection: 'horizontal',
          gridUnitsLeft: 17,
          gridUnitsRight: 7,
        },
        xLarge: {
          gridDirection: 'horizontal',
          gridUnitsLeft: 18,
          gridUnitsRight: 6,
        },
      },
      currentBoundary: 'xLarge',
    };
  },

  computed: {
    /**
     * Return the grid direction
     *
     * @return {Number}
     */
    gridDirection() {
      if (!this.currentBoundary) {
        return;
      }
      return this.boundaryDefaults[this.currentBoundary].gridDirection;
    },

    /**
     * Return the number of grid units for side panel
     *
     * @return {Number}
     */
    gridUnitsLeft() {
      if (!this.currentBoundary) {
        return;
      }

      return this.boundaryDefaults[this.currentBoundary].gridUnitsLeft;
    },

    /**
     * Return the number of grid units for main content
     *
     * @return {Number}
     */
    gridUnitsRight() {
      if (!this.currentBoundary) {
        return;
      }

      return this.boundaryDefaults[this.currentBoundary].gridUnitsRight;
    },

    /**
     * Return if the grid is stacked
     *
     * @return {Bool}
     */
    stacked() {
      return this.gridDirection === 'vertical';
    },
  },

  methods: {
    /**
     * Handles responsive resizing which wraps the grid layout for this page
     *
     * @param {String} boundaryName
     */
    resize(boundaryName) {
      this.currentBoundary = boundaryName;
    },
  },
};
</script>

<style lang="scss">
.tui-marketplaceLayoutBannerSidepanelTwoColumn {
  &__heading {
    @include tui-stack-vertical(var(--gap-2));
  }

  &__grid {
    & > * {
      margin-left: var(--gap-8);
      @include tui-wordbreak--hard();
    }

    &--stacked {
      & > * {
        margin-left: var(--gap-4);
      }
    }
  }

  &__main {
    @include tui-stack-vertical(var(--gap-9));
  }

  &__sidePanel {
    height: 100%;
    padding: var(--gap-4);
    background: var(--color-neutral-3);
    @include tui-wordbreak--hard();
  }
}
</style>
