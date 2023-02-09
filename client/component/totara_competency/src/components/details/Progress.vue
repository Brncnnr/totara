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
  @module totara_competency
-->

<template>
  <div class="tui-competencyDetailProgress">
    <ProgressTracker
      :items="formattedForTracker"
      :popover-trigger-type="['click']"
      :label-opens-popover="true"
    >
      <!-- Label -->
      <template v-slot:label="{ entry }">
        {{ entry.label }}
      </template>
      <!-- Popover content -->
      <template v-slot:custom-popover-content="{ description, label, target }">
        <div class="tui-competencyDetailProgress__popover">
          <div
            v-if="target"
            class="tui-competencyDetailProgress__popover-target"
          >
            <Lozenge
              :text="$str('proficient_level', 'totara_competency')"
              type="success"
            />
          </div>

          <h5 class="tui-competencyDetailProgress__popover-header">
            {{ label }}
          </h5>

          <div
            v-if="description"
            class="tui-competencyDetailProgress__popover-body"
            v-html="description"
          />
          <div v-else class="tui-competencyDetailProgress__popover-body">
            {{ $str('no_description', 'totara_competency') }}
          </div>
        </div>
      </template>
    </ProgressTracker>
  </div>
</template>

<script>
// Components
import Lozenge from 'tui/components/lozenge/Lozenge';
import ProgressTracker from 'tui/components/progresstracker/ProgressTrackerNav';

// GraphQL
import ScaleDetailsQuery from 'totara_competency/graphql/scale';

export default {
  components: {
    Lozenge,
    ProgressTracker,
  },
  props: {
    myValue: {
      type: [Number, String],
    },
    minValue: {
      type: [Number, String],
    },
    competencyId: {
      required: true,
      type: Number,
    },
  },

  data: function() {
    return {
      scale: {},
    };
  },

  apollo: {
    scale: {
      query: ScaleDetailsQuery,
      context: { batch: true },
      variables() {
        return {
          competency_id: this.competencyId,
        };
      },
      update({ totara_competency_scale: scale }) {
        this.$emit('loaded');
        return scale;
      },
    },
  },

  computed: {
    /**
     * Provide ID for the minimum scale at which you can be classed proficient
     *
     * @return {Int}
     */
    minProficientValueId() {
      if (this.minValue) {
        return this.minValue;
      }
      if (!this.scale.values) return null;
      return this.scale.values.find(({ proficient }) => proficient).id;
    },

    /**
     * Format scale values for progress tracker
     *
     * @return {Array}
     */
    formattedForTracker() {
      if (!this.scale.values) return [];

      return this.scale.values.map(elem => {
        let itemStates = this.getItemStates(elem),
          itemStateString = this.getItemStateString(itemStates);

        return {
          description: elem.description,
          id: elem.id,
          label: elem.name,
          states: itemStates,
          stateString: itemStateString,
        };
      });
    },
  },

  methods: {
    /**
     * Implementation-specific logic to decide what states each progress tracker
     * item should have, based on incoming GraphQL scale values
     *
     * @param {Object} item
     * @return {Array}
     **/
    getItemStates(item) {
      let stateArray = [];
      let id = parseInt(item.id);
      let target = parseInt(this.minProficientValueId);
      let current = parseInt(this.myValue);
      let targetMet = current <= target;

      // is the item our target?
      if (target && id <= target) {
        stateArray.push('target');
      }

      // is this our current item?
      if (id === current) {
        stateArray.push('current');
      }

      // do we have some sort of completed state on this item, or is it pending?
      if (current && id >= current) {
        if (targetMet) {
          // we do, and we've met our target, we have done more than complete,
          // we have achieved
          stateArray.push('achieved');
        } else {
          // we have, but we haven't met our target, just complete for now
          stateArray.push('complete');
        }
      } else {
        // nope, still work to do
        stateArray.push('pending');
      }

      // all collected states will be passed to the item in the progress tracker
      return stateArray;
    },

    /**
     * Accessibility string for each progress item state
     *
     * @param {Object} itemStates
     * @return {String}
     */
    getItemStateString(itemStates) {
      let strVar;

      if (itemStates.includes('pending')) {
        strVar = this.$str('completion-n', 'completion');
      } else if (itemStates.includes('complete')) {
        strVar = this.$str('completion-y', 'completion');
      } else {
        strVar = this.$str('a11y_achievedrequiredgoal', 'totara_core');
      }

      if (itemStates.includes('target')) {
        return this.$str(
          'a11y_achievement_target_with_status',
          'totara_core',
          strVar
        );
      } else {
        return this.$str('ally_status_with_value', 'totara_core', strVar);
      }
    },
  },
};
</script>

<lang-strings>
{
  "totara_competency": [
    "no_description",
    "proficient_level"
  ],
  "completion": [
    "completion-n",
    "completion-y"
  ],
  "totara_core": [
    "a11y_achievedrequiredgoal",
    "ally_status_with_value",
    "a11y_achievement_target_with_status"
  ]
}
</lang-strings>

<style lang="scss">
.tui-competencyDetailProgress {
  min-height: 65px;
  margin: var(--gap-7) auto 0;

  &__popover {
    &-header {
      margin-top: 0;
      @include tui-font-heading-label();
    }

    &-body {
      color: var(--color-neutral-6);
    }

    & > * + * {
      margin-top: var(--gap-2);
    }
  }
}
</style>
