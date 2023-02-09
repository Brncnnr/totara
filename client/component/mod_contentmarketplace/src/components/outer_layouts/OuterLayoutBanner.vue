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

  @author Arshad Anwer <arshad.anwer@totaralearning.com>
  @package mod_contentmarketplace
-->

<template>
  <!-- 
    Outer layout, doesn't provide title or other standard slots
    Slots provided
      * banner-content, (Optional) for adding content over the banner
      * main-content, for main content
      * modals, for any page level modals
  -->
  <div class="tui-marketplaceOuterLayoutBanner">
    <Loader :loading="loading">
      <Grid :max-units="24" :use-vertical-gap="false">
        <!-- main content (banner and inner page layout) -->
        <GridItem :units="24">
          <!-- Banner -->
          <div class="tui-marketplaceOuterLayoutBanner__banner">
            <!-- Banner image (set to ratio) -->
            <div
              class="tui-marketplaceOuterLayoutBanner__banner-image"
              :style="{ 'background-image': bannerImage }"
            />

            <!-- Banner extra content  -->
            <div
              v-if="$scopedSlots['banner-content']"
              class="tui-marketplaceOuterLayoutBanner__banner-content"
            >
              <div class="tui-marketplaceOuterLayoutBanner__banner-contentArea">
                <slot name="banner-content" :outer-stacked="false" />
              </div>
            </div>
          </div>

          <!-- Body content -->
          <div class="tui-marketplaceOuterLayoutBanner__body">
            <slot name="main-content" :outer-stacked="false" />
          </div>
        </GridItem>
      </Grid>
    </Loader>

    <slot name="modals" />
  </div>
</template>

<script>
import Grid from 'tui/components/grid/Grid';
import GridItem from 'tui/components/grid/GridItem';
import Loader from 'tui/components/loading/Loader';

export default {
  components: {
    Grid,
    GridItem,
    Loader,
  },

  props: {
    // Image url for banner
    bannerImageUrl: [Boolean, String],
    // Display loader over all content
    loading: Boolean,
  },

  computed: {
    /**
     * Construct a background image URL
     *
     * @return {String}
     */
    bannerImage() {
      let url = this.bannerImageUrl;
      return !url || url === null ? '' : 'url("' + encodeURI(url) + '")';
    },
  },
};
</script>

<style lang="scss">
.tui-marketplaceOuterLayoutBanner {
  @include tui-font-body();

  &__banner {
    position: relative;

    &-image {
      min-height: 120px;
      padding-bottom: 18.04%;
      background-color: var(--color-neutral-3);
      background-position: center;
      background-size: cover;
    }

    &-content {
      position: absolute;
      top: 0;
      width: 100%;
      height: 100%;
      padding: var(--gap-4);
    }

    &-contentArea {
      display: flex;
    }
  }

  &__body {
    margin: var(--gap-10) var(--gap-4) var(--gap-10) 0;
  }
}
</style>
