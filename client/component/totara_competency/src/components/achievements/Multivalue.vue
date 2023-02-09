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
  <div>
    <component
      :is="component"
      :assignment-id="assignmentId"
      :competency-id="competencyId"
      :user-id="userId"
      @loaded="isLoaded"
    />
  </div>
</template>

<script>
export default {
  inheritAttrs: false,
  props: {
    assignmentId: {
      required: true,
      type: Number,
    },
    competencyId: {
      type: Number,
    },
    type: {
      required: true,
      type: String,
    },
    userId: {
      required: true,
      type: Number,
    },
  },

  data: function() {
    return {
      component: null,
    };
  },

  mounted: function() {
    let compPath = `pathway_${this.type}/components/achievements/AchievementDisplay`;
    this.component = tui.asyncComponent(compPath);
  },

  methods: {
    /**
     * Emit a 'loaded' event once the component emits an event saying it's ready
     *
     */
    isLoaded() {
      this.$emit('loaded');
    },
  },
};
</script>
