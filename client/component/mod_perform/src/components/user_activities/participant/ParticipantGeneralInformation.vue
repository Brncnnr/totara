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
  <Grid
    class="tui-participantGeneralInformation"
    :use-horizontal-gap="false"
    :use-vertical-gap="false"
    :max-units="12"
    :stack-at="600"
  >
    <GridItem :units="4">
      <div class="tui-participantGeneralInformation__userDetails">
        <MiniProfileCard
          :display="subjectUser.card_display"
          :label-id="$id('user-info')"
          no-border
          no-padding
          read-only
        />
      </div>
    </GridItem>
    <GridItem :units="8" class="tui-participantGeneralInformation__rhs">
      <div class="tui-participantGeneralInformation__relationship">
        <p class="tui-participantGeneralInformation__relationship-heading">
          {{
            $str(
              !currentUserIsSubject
                ? 'user_activities_your_relationship_to_user_internal'
                : 'relation_to_subject_self_internal',
              'mod_perform'
            )
          }}
          <span v-if="!currentUserIsSubject">{{ relationship }}</span>
        </p>

        <div class="tui-participantGeneralInformation__relationship-toSubject">
          <template
            v-if="
              currentUserIsSubject &&
                jobAssignments &&
                jobAssignments.length > 0
            "
          >
            {{ $str('user_activities_your_capacity', 'mod_perform') }}
          </template>
          <template v-if="!currentUserIsSubject && jobAssignments.length > 0">
            {{
              $str(
                'user_activities_their_capacity',
                'mod_perform',
                subjectUser.fullname
              )
            }}
          </template>
        </div>

        <JobAssignmentInformation :job-assignments="jobAssignments" />
      </div>
    </GridItem>
  </Grid>
</template>

<script>
import MiniProfileCard from 'tui/components/profile/MiniProfileCard';
import JobAssignmentInformation from 'mod_perform/components/user_activities/participant/JobAssignmentInformation';
import Grid from 'tui/components/grid/Grid';
import GridItem from 'tui/components/grid/GridItem';

export default {
  components: {
    MiniProfileCard,
    JobAssignmentInformation,
    Grid,
    GridItem,
  },
  props: {
    subjectUser: {
      type: Object,
      required: true,
    },
    jobAssignments: Array,
    currentUserIsSubject: Boolean,
    relationship: String,
  },
};
</script>
<lang-strings>
  {
    "mod_perform": [
      "relation_to_subject_self_internal",
      "user_activities_your_relationship_to_user_internal",
      "user_activities_their_capacity",
      "user_activities_your_capacity"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-participantGeneralInformation {
  @include tui-font-body();

  display: flex;
  flex-direction: column;
  justify-content: space-between;
  border: var(--border-width-thin) solid var(--color-border);
  border-radius: var(--border-radius-normal);

  &__userDetails {
    min-width: 30%;
    height: 100%;
    padding: var(--gap-2) var(--gap-4);
    border-bottom: var(--border-width-thin) solid var(--color-neutral-5);
  }

  &__rhs {
    background: var(--color-neutral-2);
    border-radius: var(--border-radius-normal);
  }

  &__relationship {
    display: flex;
    flex-direction: column;
    flex-grow: 1;
    padding: var(--gap-2) var(--gap-4) var(--gap-1) var(--gap-4);
    border-radius: var(--border-radius-normal);
  }

  &__relationship-heading {
    @include tui-font-heading-small-regular();

    display: flex;
    flex-wrap: wrap;
    margin-bottom: var(--gap-1);

    span {
      @include tui-font-heading-small();
      // margin-left: 4px;
    }
  }

  &__relationshipHeadingDetails {
    @include tui-font-heading-small();
  }

  &__relationship-toSubject {
    @include tui-font-body-small();
    margin-bottom: var(--gap-1);
  }
}

@media (min-width: $tui-screen-xs) {
  .tui-participantGeneralInformation {
    flex-direction: row;

    &__userDetails {
      border-right: var(--border-width-thin) solid var(--color-neutral-5);
      border-bottom: none;
    }

    &__relationship-heading {
      span {
        margin-left: var(--gap-1);
      }
    }
  }
}
</style>
