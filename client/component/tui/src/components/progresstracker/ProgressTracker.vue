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

  @author Kevin Hottinger <kevin.hottinger@totaralearning.com>
  @module tui
  @deprecated Since Totara 15.0
-->

<template>
  <div class="tui-progressTracker">
    <OverflowDetector v-slot="{ measuring }" @change="overflowChanged">
      <ol
        class="tui-progressTrackerItems"
        :class="{
          'tui-progressTrackerItems--vertical': !measuring && vertical,
        }"
      >
        <ProgressTrackerItem
          v-for="(item, index) in itemsWithState"
          :key="index"
          :gap="gap"
          :label="item.label"
          :state="item.state"
          :target="item.target"
          :current="item.current"
          :popover-trigger-type="popoverTriggerType"
          :achieved-goal-string="achievedGoalString"
          :target-string="targetString"
        >
          <template v-slot:popover-content>
            <template v-if="!$scopedSlots['custom-popover-content']">
              <div v-html="item.description" />
            </template>
            <slot
              name="custom-popover-content"
              :description="item.description"
              :label="item.label"
              :target="item.target"
            />
          </template>
        </ProgressTrackerItem>
      </ol>
    </OverflowDetector>
  </div>
</template>

<script>
import OverflowDetector from 'tui/components/util/OverflowDetector';
import ProgressTrackerItem from 'tui/components/progresstracker/ProgressTrackerItem';

export default {
  components: {
    OverflowDetector,
    ProgressTrackerItem,
  },

  props: {
    gap: String,
    currentId: [Number, String],
    targetId: [Number, String],
    items: {
      type: Array,
      required: true,
    },
    popoverTriggerType: Array,
    achievedGoalString: String,
    targetString: String,
  },

  data() {
    return {
      vertical: false,
    };
  },

  computed: {
    /**
     * Create item data structure with correct state based on provided currentID & targetID
     *
     * @return {Array}
     */
    itemsWithState() {
      let stateArray;
      let stateString = 'pending';
      let target = false;
      let currentIndex;
      let targetIndex;

      // Check if minimum target item exists
      if (this.targetId) {
        targetIndex = this.items.findIndex(({ id }) => id === this.targetId);
      }

      // Check if current item exists
      if (this.currentId) {
        currentIndex = this.items.findIndex(({ id }) => id === this.currentId);
        if (currentIndex > -1) {
          stateString = 'complete';
        }
      }

      // Check if target has been met
      if (
        targetIndex > -1 &&
        currentIndex > -1 &&
        currentIndex >= targetIndex
      ) {
        stateString = 'achieved';
      }

      stateArray = this.items.map(function(elem, index) {
        if (currentIndex > -1 && index > currentIndex) {
          stateString = 'pending';
        }

        if (targetIndex > -1 && index >= targetIndex) {
          target = true;
        }

        return {
          current: currentIndex === index,
          description: elem.description,
          id: elem.id,
          label: elem.label,
          state: stateString,
          target: target,
        };
      });
      return stateArray;
    },
  },

  methods: {
    /**
     * Switch vertical Bool to true when content is overflowing
     */
    overflowChanged({ overflowing }) {
      this.vertical = overflowing;
    },
  },
};
</script>

<style lang="scss">
$progress-tracker-line-sm: 100px !default;
$progress-tracker-line-md: 200px !default;
$progress-tracker-line-lg: 250px !default;
$progress-tracker-half-circle: 14px !default;

.tui-progressTrackerItems {
  display: flex;
  justify-content: center;
  margin: 0;
  list-style: none;

  // Line styles
  & > * + * {
    &.tui-progressTrackerItem {
      .tui-progressTrackerItem__content::before {
        position: absolute;
        top: $progress-tracker-half-circle;
        left: -50%;
        display: block;
        width: 100%;
        border-style: solid;
        border-width: var(--border-width-thin) 0 0 0;
        content: '';
      }

      &--pending {
        .tui-progressTrackerItem__content::before {
          border-color: var(--progresstracker-color-pending);
          border-style: dotted;
        }
      }

      &--complete {
        .tui-progressTrackerItem__content::before {
          top: ($progress-tracker-half-circle - 1);
          border-color: var(--progresstracker-color-complete);
          border-width: var(--border-width-thick) 0 0 0;
        }
      }

      &--achieved {
        .tui-progressTrackerItem__content::before {
          top: ($progress-tracker-half-circle - 1);
          border-color: var(--progresstracker-color-achieved);
          border-width: var(--border-width-thick) 0 0 0;
        }
      }
    }
  }
  &--vertical {
    & > * + * {
      &.tui-progressTrackerItem {
        .tui-progressTrackerItem__content::before {
          top: -50%;
          left: ($progress-tracker-half-circle - 1);
          width: 0;
          height: 100%;
          border-width: 0 0 0 var(--border-width-thick);
        }
      }
    }
  }

  .tui-progressTrackerItem {
    position: relative;
    flex-shrink: 0;

    &--small {
      width: $progress-tracker-line-sm;
    }

    &--medium {
      width: $progress-tracker-line-md;
    }

    &--large {
      width: $progress-tracker-line-lg;
    }

    &__content {
      position: relative;
      display: flex;
      flex-direction: column;
      align-items: center;
      width: 100%;
    }

    &__label {
      position: relative;
      width: 100%;
      margin: 0 auto;
      padding: var(--gap-2);
      text-align: center;

      &-current {
        .tui-formBtn {
          color: var(--color-state-active);
          font-weight: bold;
        }
      }
    }

    &__status,
    &__target {
      @include sr-only();
    }

    // Spacing after for overflow detector
    :last-child {
      &--small {
        width: ($progress-tracker-line-sm * 2);
        padding-right: $progress-tracker-line-sm;
      }

      &--medium {
        width: ($progress-tracker-line-md * 2);
        padding-right: $progress-tracker-line-md;
      }

      &--large {
        width: ($progress-tracker-line-lg * 2);
        padding-right: $progress-tracker-line-lg;
      }
    }
  }

  &--vertical {
    flex-direction: column;

    .tui-progressTrackerItem {
      width: auto;
      height: 80px;

      &__content {
        flex-direction: row;
        height: 100%;
      }

      &__label {
        margin: 0;
        padding: var(--gap-1) var(--gap-2);
        text-align: left;
      }
    }
  }
}
</style>
