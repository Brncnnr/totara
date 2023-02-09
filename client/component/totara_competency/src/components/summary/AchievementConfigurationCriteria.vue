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

  @author Riana Rossouw <riana.rossouw@totaralearning.com>
  @module totara_competency
-->

<template>
  <Card :no-border="!path.multiCriteria">
    <div
      class="tui-competencySummaryAchievementCriteria"
      :class="{
        'tui-competencySummaryAchievementCriteria--multi': path.multiCriteria,
      }"
    >
      <template v-for="(criterion, criterionIdx) in path.criteria_summary">
        <Separator
          v-if="criterionIdx"
          :key="criterionIdx + 'and'"
          class="tui-competencySummaryAchievementCriteria__and"
        >
          <AndBox />
        </Separator>

        <div
          :key="criterionIdx"
          class="tui-competencySummaryAchievementCriteria__criterion"
        >
          <div>
            <h5
              class="tui-competencySummaryAchievementCriteria__criterion-header"
            >
              {{ criterion.item_type }}
            </h5>

            <span
              v-if="criterion.item_aggregation"
              class="tui-competencySummaryAchievementCriteria__criterion-aggregation"
            >
              ({{ criterion.item_aggregation }})
            </span>
          </div>

          <div
            class="tui-competencySummaryAchievementCriteria__criterion-items"
          >
            <div v-for="(item, itemIdx) in criterion.items" :key="itemIdx">
              {{ item.description }}
              <span
                v-if="item.error"
                class="tui-competencySummaryAchievementCriteria__warning"
              >
                <WarningIcon :size="200" />
                {{ item.error }}
              </span>
            </div>
          </div>
        </div>
      </template>
    </div>
  </Card>
</template>

<script>
// Components
import AndBox from 'tui/components/decor/AndBox';
import Card from 'tui/components/card/Card';
import Separator from 'tui/components/decor/Separator';
import WarningIcon from 'tui/components/icons/Warning';

export default {
  components: {
    AndBox,
    Card,
    Separator,
    WarningIcon,
  },

  props: {
    path: {
      type: Object,
    },
  },
};
</script>

<style lang="scss">
.tui-competencySummaryAchievementCriteria {
  width: 100%;

  &--multi {
    padding: var(--gap-4);
  }

  &__and {
    width: 50%;
    margin: 0;
  }

  &__criterion {
    &-header {
      display: inline-block;
      width: 40%;
      margin: 0;
      @include tui-font-heading-label();
    }

    &-aggregation {
      @include tui-font-hint();
    }

    &-items {
      margin-top: var(--gap-1);
    }
  }

  &__warning {
    color: var(--color-prompt-warning);
  }
}
</style>
