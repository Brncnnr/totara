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
  @package totara_contentmarketplace
-->

<template>
  <div
    class="tui-contentMarketplacePageLayoutTwoColumn"
    :class="{
      'tui-contentMarketplacePageLayoutTwoColumn--flush': flush,
    }"
  >
    <slot name="feedback-banner" />

    <slot name="user-overview" />

    <div class="tui-contentMarketplacePageLayoutTwoColumn__heading">
      <slot name="content-nav" />

      <PageHeading :title="title">
        <template v-slot:buttons>
          <slot name="header-buttons" />
        </template>
      </PageHeading>
    </div>

    <div
      v-if="subTitle"
      class="tui-contentMarketplacePageLayoutTwoColumn__subHeading"
    >
      {{ subTitle }}
    </div>

    <slot name="pre-body" />

    <Loader
      :loading="loading"
      class="tui-contentMarketplacePageLayoutTwoColumn__body"
    >
      <Grid :stack-at="stackAt">
        <!-- Left content -->
        <GridItem :units="3">
          <slot name="left-content" />
        </GridItem>
        <!-- Right content -->
        <GridItem :units="9">
          <Loader :loading="loadingRight">
            <slot name="right-content" />
          </Loader>
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
import PageHeading from 'tui/components/layouts/PageHeading';

export default {
  components: {
    Grid,
    GridItem,
    Loader,
    PageHeading,
  },

  props: {
    // Correctly space if using flush design
    flush: Boolean,
    // Display loader over all content
    loading: Boolean,
    // Display loader over right column content
    loadingRight: Boolean,
    // Custom stack at value
    stackAt: {
      type: Number,
      default: 1000,
    },
    // Page title
    title: {
      required: true,
      type: String,
    },
    subTitle: String,
  },
};
</script>

<style lang="scss">
.tui-contentMarketplacePageLayoutTwoColumn {
  @include tui-font-body();
  margin-top: var(--gap-2);

  @include tui-stack-vertical(var(--gap-8));

  &__heading {
    @include tui-stack-vertical(var(--gap-2));
  }

  &__subHeading {
    margin-top: var(--gap-4);
  }

  &--flush {
    margin-top: var(--gap-12);
  }
}
</style>
