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
  <div class="tui-competencyAchievements">
    <Loader :loading="loading">
      <h3 class="tui-competencyAchievements__title">
        {{ $str('ways_to_achieve_proficiency', 'totara_competency') }}
      </h3>

      <div
        v-if="!achievements.length"
        class="tui-competencyAchievements__empty"
      >
        {{ $str('no_achievement_criteria', 'totara_competency') }}
      </div>

      <!-- Output tabs showing paths to achieve proficiency (e.g. manual rating, criteria, leraing plan) -->
      <Tabs
        v-else
        v-show="!loading"
        :key="assignmentId"
        :transparent-tabs="true"
        class="tui-competencyAchievements__tabs"
      >
        <Tab
          v-for="(component, id) in achievements"
          :id="id"
          :key="id"
          :always-render="true"
          :name="component.props.name"
        >
          <!-- call either SingleValue or MultiValue component -->
          <component
            :is="component.component"
            v-bind="component.props"
            @loaded="itemLoaded"
          />
        </Tab>
      </Tabs>
    </Loader>
  </div>
</template>

<script>
// Components
import Loader from 'tui/components/loading/Loader';
import Tab from 'tui/components/tabs/Tab';
import Tabs from 'tui/components/tabs/Tabs';
// GraphQL
import AchievementPathsQuery from 'totara_competency/graphql/achievement_paths';

export default {
  components: {
    Loader,
    Tab,
    Tabs,
  },

  props: {
    assignment: {
      required: true,
      type: Object,
    },
    competencyId: {
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
      itemsLoaded: null,
      loading: true,
    };
  },

  apollo: {
    achievements: {
      query: AchievementPathsQuery,
      context: { batch: true },
      variables() {
        return {
          assignment_id: this.assignmentId,
          user_id: this.userId,
        };
      },
      update({ totara_competency_achievement_paths: paths }) {
        let newAchievementComponents = [];
        paths.forEach(path => {
          let componentName = this.capitalizeFirstLetter(path.class);
          let compPath = `totara_competency/components/achievements/${componentName}`;

          newAchievementComponents.push({
            component: tui.asyncComponent(compPath),
            props: {
              userId: this.userId,
              assignmentId: this.assignmentId,
              competencyId: this.competencyId,
              type: path.type,
              name: path.name,
            },
          });
        });

        // Make sure event is fired even if there are no items
        if (newAchievementComponents.length === 0) {
          this.loading = false;
        }

        return newAchievementComponents;
      },
    },
  },

  computed: {
    /**
     * Convert assignmentId to number
     *
     * @return {Int}
     */
    assignmentId() {
      return parseInt(this.assignment.id);
    },

    /**
     * Return number of items
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
        this.loading = false;
      }
    },

    /**
     * Don't display loader on assignment ID change
     *
     */
    assignmentId: function() {
      this.loading = false;
    },
  },

  methods: {
    /**
     * Convert fetched component name string to align with Vue naming convention
     *
     * @param {String} string
     * @return {String}
     */
    capitalizeFirstLetter(string) {
      return string.charAt(0).toUpperCase() + string.toLowerCase().slice(1);
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
      "no_achievement_criteria",
      "ways_to_achieve_proficiency"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-competencyAchievements {
  margin-top: var(--gap-7);

  &__empty {
    font-size: var(--font-size-16);
    @include tui-font-hint();
  }

  &__tabs {
    margin-top: var(--gap-5);
  }

  &__title {
    @include tui-font-heading-small();
  }
}
</style>
