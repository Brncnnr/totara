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

  @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
  @module pathway_criteria_group
-->

<template>
  <div class="tui-pathwayCriteriaGroupAchievement">
    <template v-for="(component, id) in achievements">
      <!-- Criteria group item -->
      <div :key="id" class="tui-pathwayCriteriaGroupAchievement__item">
        <component
          :is="component.component"
          v-bind="component.props"
          :displayed="displayed"
          @loaded="itemLoaded"
        />
      </div>

      <!-- And separator -->
      <div
        v-if="!isLastItem(id, achievements)"
        :key="id + 'andseparator'"
        class="tui-pathwayCriteriaGroupAchievement__separator"
      >
        <AchievementLayout :no-borders="true">
          <template v-slot:left>
            <AndBox />
          </template>
        </AchievementLayout>
      </div>
    </template>
  </div>
</template>

<script>
// Components
import AchievementLayout from 'totara_competency/components/achievements/AchievementLayout';
import AndBox from 'tui/components/decor/AndBox';

// GraphQL
import CriteriaGroupAchievementsQuery from 'pathway_criteria_group/graphql/achievements';

export default {
  components: {
    AchievementLayout,
    AndBox,
  },

  inheritAttrs: false,

  props: {
    assignmentId: {
      required: true,
      type: Number,
    },
    dateAchieved: {
      type: String,
    },
    displayed: Boolean,
    instanceId: {
      required: true,
      type: Number,
    },
    userId: {
      required: true,
      type: Number,
    },
  },

  data: function() {
    return {
      achievements: [],
      itemsLoaded: 0,
    };
  },

  apollo: {
    achievements: {
      query: CriteriaGroupAchievementsQuery,
      context: { batch: true },
      variables() {
        return {
          instance_id: this.instanceId,
        };
      },
      update({ pathway_criteria_group_achievements: achievements }) {
        let newAchievementComponents = [];
        achievements.forEach(achievement => {
          let compPath = `criteria_${achievement.type}/components/achievements/AchievementDisplay`;

          newAchievementComponents.push({
            component: tui.asyncComponent(compPath),
            props: {
              assignmentId: this.assignmentId,
              dateAchieved: this.dateAchieved,
              instanceId: parseInt(achievement.instance_id),
              userId: this.userId,
            },
          });
        });

        // Make sure event is fired even if there are no items
        if (newAchievementComponents.length === 0) {
          this.$emit('loaded');
        }

        return newAchievementComponents;
      },
    },
  },

  computed: {
    /**
     * Calculates the number of items and returns the value
     *
     * @return {Int}
     */
    numberOfItems() {
      return this.achievements.length;
    },
  },

  watch: {
    /**
     * Check if all items are loaded, emit a 'loaded' event if they are
     *
     * @param {Object} loadedItems
     */
    itemsLoaded: function(loadedItems) {
      if (loadedItems === this.numberOfItems) {
        this.$emit('loaded');
      }
    },
  },

  methods: {
    /**
     * Checks if current item is last and returns a bool
     *
     * @param {Int} id
     * @param {Array} items
     * @return {Boolean}
     */
    isLastItem(id, items) {
      return id === items.length - 1;
    },

    /**
     * Increments number of items loaded
     *
     */
    itemLoaded() {
      this.itemsLoaded += 1;
    },
  },
};
</script>

<style lang="scss">
.tui-pathwayCriteriaGroupAchievement {
  padding: var(--gap-4);
  border: var(--border-width-thin) solid var(--color-neutral-5);
  border-radius: 6px;

  & > * + * {
    margin-top: var(--gap-8);
  }
}
</style>
