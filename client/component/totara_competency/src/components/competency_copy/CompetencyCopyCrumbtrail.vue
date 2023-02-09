<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2022 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD's customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totaralearning.com] for more information.

  @author Kevin Hottinger <kevin.hottinger@totaralearning.com>
  @module totara_competency
-->

<template>
  <nav
    :aria-label="$str('a11y_competency_crumbtrail', 'totara_competency')"
    class="tui-competencyCopyPathwayCrumbtrail"
  >
    <!-- Crumbtrail -->
    <ol class="tui-competencyCopyPathwayCrumbtrail__list">
      <li
        v-for="(crumb, index) in crumbtrailData"
        :key="index"
        class="tui-competencyCopyPathwayCrumbtrail__list-item"
      >
        <Button
          :styleclass="{ transparent: true }"
          :text="crumb.name"
          @click="$emit('change-competency-level', crumb.id)"
        /><span aria-hidden="true">{{
          $str('crumbtrail_separator', 'totara_competency')
        }}</span>
      </li>

      <!-- Current level -->
      <li
        aria-current="location"
        :class="{
          'tui-competencyCopyPathwayCrumbtrail__list-multipleItems': currentLevel,
        }"
      >
        <h5 class="tui-competencyCopyPathwayCrumbtrail__list-current">
          <SkeletonContent
            v-if="loading"
            :char-length="15"
            :has-overlay="true"
          />

          <!-- Search results -->
          <span v-else-if="searching">
            {{ $str('search_results', 'totara_competency') }}
          </span>

          <!-- Current parent name -->
          <span v-else-if="currentLevel">
            {{ currentLevel }}
          </span>
          <span v-else>
            {{ $str('framework', 'totara_competency') }}
          </span>
        </h5>
      </li>
    </ol>
  </nav>
</template>

<script>
import Button from 'tui/components/buttons/Button';
import SkeletonContent from 'tui/components/loading/SkeletonContent';

export default {
  components: {
    Button,
    SkeletonContent,
  },

  props: {
    // List of crumbtrail items
    crumbtrailData: {
      type: Array,
    },

    // Current competency level
    currentLevel: {
      type: String,
    },

    // Competency content is currently loading
    loading: {
      type: Boolean,
    },

    // Has an active search filter
    searching: {
      type: Boolean,
    },
  },
};
</script>

<lang-strings>
  {
    "totara_competency": [
      "a11y_competency_crumbtrail",
      "crumbtrail_separator",
      "framework",
      "search_results"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-competencyCopyPathwayCrumbtrail {
  &__list {
    margin: 0;
    list-style: none;

    &-current {
      margin: 0;
      @include tui-font-heading-x-small();
    }

    &-item {
      @include tui-font-body-small();
      display: inline;
    }

    &-multipleItems {
      margin-top: var(--gap-4);
    }
  }
}
</style>
