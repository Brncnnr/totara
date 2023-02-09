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
  <Responsive
    v-if="hasRecommendations"
    class="tui-recommendedSpaces"
    :breakpoints="boundaries"
    @responsive-resize="currentBoundaryName = $event"
  >
    <hr class="tui-recommendedSpaces__horizontalLine" />

    <div class="tui-recommendedSpaces__head">
      <h2 class="tui-recommendedSpaces__title">
        <span>{{ $str('recommended_spaces', 'container_workspace') }}</span>
        <Loading v-if="$apollo.loading" />
      </h2>

      <a
        :href="$url('/container/type/workspace/spaces.php')"
        class="tui-recommendedSpaces__link"
      >
        {{ $str('view_all_spaces', 'container_workspace') }}
      </a>
    </div>

    <SpaceCardsGrid
      :max-grid-units="12"
      :workspace-units="cardUnits"
      :workspaces="workspaces"
      no-empty-state-message
      class="tui-recommendedSpaces__grid"
      @join-workspace="joinWorkspace"
    />
  </Responsive>
</template>

<script>
import Responsive from 'tui/components/responsive/Responsive';
import Loading from 'tui/components/icons/Loading';
import SpaceCardsGrid from 'container_workspace/components/grid/SpaceCardsGrid';
import { cardGrid } from 'container_workspace/index';
import { config } from 'tui/config';

// GraphQL Queries
import recommendedSpaces from 'ml_recommender/graphql/get_recommended_user_workspaces';

export default {
  components: {
    Responsive,
    Loading,
    SpaceCardsGrid,
  },

  props: {
    /**
     * This property had been deprecated, it is no longer used.
     * @deprecated since Totara 15.0
     */
    maxGridUnits: {
      type: [Number, String],
      required: false,
    },
  },

  apollo: {
    workspaces: {
      query: recommendedSpaces,
      fetchPolicy: 'network-only',
      variables() {
        return {
          theme: config.theme.name,
        };
      },
    },
  },

  data() {
    return {
      currentBoundaryName: 'l',
      workspaces: [],
    };
  },
  computed: {
    boundaries() {
      return Object.values(cardGrid);
    },
    /**
     * @returns {Number}
     */
    cardUnits() {
      if (!cardGrid[this.currentBoundaryName]) {
        return 2;
      }

      return cardGrid[this.currentBoundaryName].cardUnits;
    },
    /**
     * @return {boolean}
     */
    hasRecommendations() {
      return !this.$apollo.loading && this.workspaces.length > 0;
    },
  },
  methods: {
    /**
     *
     * @param {Number} workspace_id
     */
    joinWorkspace({ workspace_id }) {
      // After everything, we just need to emit an event up tot he parent.
      this.$emit('join-workspace', workspace_id);
    },
  },
};
</script>

<lang-strings>
  {
    "container_workspace": [
      "recommended_spaces",
      "view_all_spaces"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-recommendedSpaces {
  &__head {
    display: block;
    margin-bottom: var(--gap-4);
  }

  &__title {
    @include tui-font-heading-small();
    margin: 0;
  }

  &__link {
    @include tui-font-link();
  }

  @media screen and (min-width: $tui-screen-sm) {
    &__head {
      display: flex;
      justify-content: space-between;
    }
  }
}
</style>
