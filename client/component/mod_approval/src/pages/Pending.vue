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
<template>
  <div>
    <div class="tui-mod_approval-pending__back">
      <a :href="$url('/mod/approval/application/index.php')">
        <BackArrow />
        {{ $str('back_to_applications', 'mod_approval') }}
      </a>
    </div>
    <h2 class="tui-mod_approval-pendingHeading">
      {{ $str('applications_awaiting_response', 'mod_approval') }}
    </h2>
    <p
      v-if="!$matches('loading')"
      class="tui-mod_approval-pending__resultsCount"
    >
      {{
        $str(
          'xapplications',
          'mod_approval',
          $selectors.getApplicationsTotal($context)
        )
      }}
    </p>
    <div class="tui-mod_approval-pending">
      <Grid
        v-for="(row, i) in rows"
        :key="i"
        :max-units="$context.gridWidth"
        class="tui-mod_approval-pending__row"
      >
        <GridItem
          v-for="{ id, title, workflow_type, user, submitted } in row.items"
          :key="id"
        >
          <ResponseCard
            :id="id"
            :title="title"
            :workflow-type="workflow_type"
            :user="user"
            :submitted="submitted"
          />
        </GridItem>
      </Grid>

      <div>
        <Loader :loading="isLoading" />
      </div>

      <div
        v-if="$selectors.getHasMore($context)"
        class="tui-mod_approval-pending__loadMore"
      >
        <Button
          :text="$str('loadmore', 'totara_core')"
          :disabled="isLoading"
          @click="loadMore"
        />
      </div>
    </div>
  </div>
</template>

<script>
import BackArrow from 'tui/components/icons/BackArrow';
import Button from 'tui/components/buttons/Button';
import Grid from 'tui/components/grid/Grid';
import GridItem from 'tui/components/grid/GridItem';
import ResponseCard from 'mod_approval/components/cards/ResponseCard';
import Loader from 'tui/components/loading/Loader';
import { throttle } from 'tui/util';
import { getOffsetRect } from 'tui/dom/position';
import ResizeObserver from 'tui/polyfills/ResizeObserver';
import pendingMachine from 'mod_approval/pending/machine';

const THROTTLE_UPDATE = 150;

export default {
  components: {
    BackArrow,
    Button,
    Grid,
    GridItem,
    ResponseCard,
    Loader,
  },

  xState: {
    machine() {
      return pendingMachine();
    },
  },

  computed: {
    rows() {
      return this.$selectors.getRows(this.$context);
    },

    isLoading() {
      return ['loading', 'loadingMore'].some(this.$matches);
    },
  },

  mounted() {
    this.resizeObserver = new ResizeObserver(
      throttle(this.$_measure, THROTTLE_UPDATE)
    );
    this.resizeObserver.observe(
      this.$el.querySelector('.tui-mod_approval-pending')
    );
    this.$_measure();
  },

  methods: {
    loadMore() {
      /** @param {import(../js/pending/actions).LoadMoreEvent} event */
      this.$send({
        type: this.$e.LOAD_MORE,
        page: this.$selectors.getPage(this.$context) + 1,
      });
    },

    $_measure() {
      let width = getOffsetRect(this.$el).width;
      /** @param {import(../js/pending/actions).SetGridWidthEvent} event */
      this.$send({
        type: this.$e.SET_GRID_WIDTH,
        gridWidth: Math.floor(width / 200),
      });
    },
  },
};
</script>
<lang-strings>
{
  "totara_core": [
    "loadmore"
  ],
  "mod_approval": [
    "applications_awaiting_response",
    "back_to_applications",
    "xapplications"
  ]
}
</lang-strings>

<style lang="scss">
.tui-mod_approval-pending {
  &__back {
    display: flex;
    align-items: center;
    margin-bottom: var(--gap-2);
  }

  &__resultsCount {
    @include tui-font-heading-label();
    margin: var(--gap-8) 0 var(--gap-1);
  }

  &__row {
    padding: var(--gap-3) 0;
  }

  &__loadMore {
    margin-top: var(--gap-3);
    text-align: center;
  }
}
</style>
