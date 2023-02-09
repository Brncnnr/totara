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
  @module totara_competency
-->

<template>
  <div class="tui-competencyAchievementsScale">
    <!-- Iterate through proficiency levels -->
    <div v-for="(achievement, id) in achievements" :key="id">
      <!-- Collapsible bar for proficiency level -->
      <Collapsible
        :always-render="true"
        :initial-state="!achievement.achieved"
        :label="$str('work_towards_level', 'totara_competency')"
      >
        <!-- Collapsible bar proficiency level string -->
        <template v-slot:label-extra>
          <span class="tui-competencyAchievementsScale__title">
            {{ achievement.scale_value.name }}
          </span>
        </template>

        <!-- Collapsible bar criteria fulfilled UI -->
        <template v-if="achievement.achieved" v-slot:collapsible-side-content>
          <span>
            <Lozenge
              :text="$str('criteria_fulfilled', 'totara_competency')"
              type="success"
            />
          </span>
        </template>

        <!-- Proficiency criteria content -->
        <template v-slot="{ expanded }">
          <template v-for="(item, itemid) in achievement.items">
            <div :key="itemid" :class="'tui-competencyAchievementsScale__item'">
              <component
                :is="item.component"
                v-bind="item.props"
                :displayed="expanded"
                @loaded="itemLoaded"
              />
            </div>

            <!-- Or separator if multiple paths to fulfilling criteria-->
            <div
              v-if="!isLastItem(itemid, achievement.items)"
              :key="itemid + 'orseparator'"
              class="tui-competencyAchievementsScale__separator"
            >
              <AchievementLayout :no-borders="true">
                <template v-slot:left>
                  <OrBox />
                </template>
              </AchievementLayout>
            </div>
          </template>
        </template>
      </Collapsible>
    </div>
  </div>
</template>

<script>
// Components
import AchievementLayout from 'totara_competency/components/achievements/AchievementLayout';
import Collapsible from 'tui/components/collapsible/Collapsible';
import Lozenge from 'tui/components/lozenge/Lozenge';
import OrBox from 'tui/components/decor/OrBox';

// GraphQL
import ScaleAchievementsQuery from 'totara_competency/graphql/scale_achievements';

export default {
  components: {
    AchievementLayout,
    Collapsible,
    Lozenge,
    OrBox,
  },

  inheritAttrs: false,
  props: {
    userId: {
      required: true,
      type: Number,
    },
    assignmentId: {
      required: true,
      type: Number,
    },
    type: String,
  },

  data: function() {
    return {
      achievements: [],
      achievementComponents: [],
      itemsLoaded: 0,
    };
  },

  apollo: {
    achievements: {
      query: ScaleAchievementsQuery,
      context: { batch: true },
      variables() {
        return {
          assignment_id: this.assignmentId,
          user_id: this.userId,
        };
      },
      update({ totara_competency_scale_achievements: achievements }) {
        let newAchievements = [];
        let numberOfItems = 0;

        achievements.forEach(achievement => {
          let newAchievement = {
            achieved: false,
            items: [],
            scale_value: achievement.scale_value,
          };
          achievement.items.forEach(item => {
            let compPath = `pathway_${item.pathway.pathway_type}/components/achievements/AchievementDisplay`;
            numberOfItems += 1;

            newAchievement.items.push({
              component: tui.asyncComponent(compPath),
              props: {
                assignmentId: this.assignmentId,
                instanceId: parseInt(item.pathway.instance_id),
                userId: this.userId,
                dateAchieved: item.date_achieved,
              },
            });

            // If any item is achieved in the set the whole set is achieved
            if (item.achieved) {
              newAchievement.achieved = true;
            }
          });

          // Make sure event is fired even if there are no items
          if (numberOfItems === 0) {
            this.$emit('loaded');
          }

          newAchievements.push(newAchievement);
        });

        return newAchievements;
      },
    },
  },

  computed: {
    /**
     * Return int for number of items
     *
     * @return {Integer}
     */
    numberOfItems() {
      if (!this.achievements) {
        return 0;
      }

      return this.achievements.reduce(function(count, i) {
        return count + i.items.length;
      }, 0);
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

<lang-strings>
  {
    "totara_competency" : [
      "criteria_fulfilled",
      "work_towards_level"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-competencyAchievementsScale {
  & > * + * {
    margin-top: var(--gap-3);
  }

  &__title {
    margin-left: var(--gap-2);
    @include tui-font-heading-small();
  }

  &__item {
    margin-top: var(--gap-4);
    &:first-of-type {
      margin-top: var(--gap-2);
    }
  }

  &__separator {
    margin: var(--gap-4) var(--gap-4) 0;
  }
}
</style>
