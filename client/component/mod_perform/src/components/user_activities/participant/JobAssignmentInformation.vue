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

  @author Arshad Anwer <arshad.anwer@totaralearning.com>
  @module mod_perform
-->

<template>
  <div v-if="jobAssignments" class="tui-jobAssignment">
    <p
      v-for="(assignment, index) in jobAssignments"
      :key="index"
      class="tui-jobAssignment__jobAssignmentDetails"
    >
      <template>
        <strong
          :class="{
            'tui-jobAssignment__spacing': assignment.position,
          }"
          >{{ assignment.fullname }}</strong
        >
        <span
          v-if="
            !assignment.position &&
              (assignment.managerja || assignment.organisation)
          "
          >,</span
        >
      </template>
      <template v-if="assignment.position">
        ({{ assignment.position.fullname }})
        <span v-if="assignment.managerja || assignment.organisation">,</span>
      </template>
      <template v-if="assignment.managerja">
        {{ $str('in_team', 'mod_perform', assignment.managerja.user.fullname) }}
        <span v-if="assignment.organisation">,</span>
      </template>
      <template v-if="assignment.organisation">
        {{ assignment.organisation.fullname }}
      </template>
    </p>
  </div>
</template>

<script>
export default {
  props: {
    jobAssignments: Array,
  },
};
</script>
<lang-strings>
  {
    "mod_perform": [
      "in_team"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-jobAssignment {
  @include tui-font-body();

  &__jobAssignmentDetails {
    @include tui-font-body-small();
    display: flex;
    flex-wrap: wrap;
    margin-bottom: var(--gap-1);

    span {
      margin-right: var(--gap-1);
    }
  }

  &__spacing {
    margin-right: var(--gap-1);
  }
}
</style>
