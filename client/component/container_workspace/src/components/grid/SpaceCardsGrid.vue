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
  @module container_workspace
-->
<template>
  <div class="tui-spaceCardsGrid">
    <VirtualScroll
      data-key="index"
      :data-list="rows"
      :page-mode="true"
      :aria-label="$str('workspace_cards_grid', 'container_workspace')"
      :is-loading="isLoading"
      @scrollbottom="onScrollToBottom"
    >
      <template v-slot:item="{ item: row }">
        <Grid class="tui-spaceCardsGrid__row" :max-units="maxGridUnits">
          <GridItem
            v-for="(workspace, i) in row.items"
            :key="i"
            :units="workspaceUnits"
            class="tui-spaceCardsGrid__row__card"
          >
            <OriginalSpaceCard
              :workspace="workspace"
              @request-to-join-workspace="
                $emit('request-to-join-workspace', $event)
              "
              @join-workspace="$emit('join-workspace', $event)"
              @leave-workspace="$emit('leave-workspace', $event)"
            />
          </GridItem>
        </Grid>
      </template>
      <template v-slot:footer>
        <PageLoader :fullpage="false" :loading="isLoading" />
      </template>
    </VirtualScroll>
    <div
      v-if="!noEmptyStateMessage && !isLoading && cursor.total === 0"
      class="tui-spaceCardsGrid__emptyResult"
    >
      {{ $str('space_empty_result', 'container_workspace') }}
    </div>
    <div
      v-if="loadMoreVisibility"
      class="tui-spaceCardsGrid__loadMoreContainer"
    >
      <div class="tui-spaceCardsGrid__viewedSpaces">
        <template>
          {{ $str('vieweditems', 'container_workspace', workspaces.length) }}
          {{ $str('total_space_x', 'container_workspace', cursor.total) }}
        </template>
      </div>
      <Button
        class="tui-spaceCardsGrid__loadMore"
        :text="$str('loadmore', 'container_workspace')"
        @click="loadMore"
      />
    </div>
  </div>
</template>

<script>
import Grid from 'tui/components/grid/Grid';
import GridItem from 'tui/components/grid/GridItem';
import { calculateRow } from 'totara_engage/grid';
import OriginalSpaceCard from 'container_workspace/components/card/OriginalSpaceCard';
import VirtualScroll from 'tui/components/virtualscroll/VirtualScroll';
import PageLoader from 'tui/components/loading/Loader';
import Button from 'tui/components/buttons/Button';

export default {
  components: {
    OriginalSpaceCard,
    Grid,
    GridItem,
    VirtualScroll,
    PageLoader,
    Button,
  },

  props: {
    maxGridUnits: {
      type: [Number, String],
      required: true,
    },

    workspaces: {
      type: Array,
      required: true,
    },

    workspaceUnits: {
      type: [Number, String],
      default: 2,
    },

    isLoading: Boolean,

    cursor: {
      type: Object,
      default() {
        return { total: 0, next: '' };
      },
    },
    /**
     * Don't show an empty state message when ere are no workspaces supplied.
     */
    noEmptyStateMessage: Boolean,
  },

  data() {
    return {
      isLoadMoreVisible: false,
    };
  },

  computed: {
    loadMoreVisibility() {
      return this.isLoadMoreVisible && this.cursor.next && !this.isLoading;
    },

    rows() {
      return calculateRow(this.workspaces, this.workspacesPerRow);
    },

    /**
     * Given the maximum units and the number of units per card. We can figure out the number of cards per row
     * quite easily.
     * @return {Number}
     */
    workspacesPerRow() {
      return Math.floor(this.maxGridUnits / this.workspaceUnits);
    },
  },

  methods: {
    async onScrollToBottom() {
      if (this.isLoadMoreVisible) {
        return;
      }
      await this.$emit('loadmoreitems');
      this.isLoadMoreVisible = true;
    },

    async loadMore() {
      await this.$emit('loadmoreitems');
      this.isLoadMoreVisible = false;
    },
  },
};
</script>

<lang-strings>
  {
    "container_workspace": [
      "loadmore",
      "space_empty_result",
      "total_space_x",
      "vieweditems",
      "workspace_cards_grid"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-spaceCardsGrid {
  &__row {
    // Override the margin.
    &.tui-grid {
      margin-bottom: var(--gap-4);
    }
  }

  &__card {
    height: var(---engage-card-height);
  }

  &__loadMoreContainer {
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding-top: var(--gap-6);
    padding-bottom: var(--gap-8);
  }

  &__emptyResult {
    @include tui-font-body;
  }

  &__viewedSpaces {
    display: flex;
    align-self: center;
    margin-bottom: var(--gap-1);
  }

  &__loadMore {
    display: flex;
    align-self: center;
  }
}
</style>
