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

  @author Oleg Demeshev <oleg.demeshevn@totaralearning.com>
  @module mod_perform
-->
<template>
  <Loader :loading="$apollo.loading">
    <h4 class="tui-performUserHistoricActivities__heading">
      {{ $str('user_historic_activities_title', 'mod_perform') }}
    </h4>
    <MyHistoricActivities
      v-if="!$apollo.loading"
      :my-historic-activities="historicActivities"
    />
    <h4 class="tui-performUserHistoricActivities__heading">
      {{ $str('user_historic_activities_about_others_title', 'mod_perform') }}
    </h4>
    <OtherHistoricActivities
      v-if="!$apollo.loading"
      :other-historic-activities="otherHistoricActivities"
    />
  </Loader>
</template>
<script>
import Loader from 'tui/components/loading/Loader';
import MyHistoricActivities from 'mod_perform/components/user_activities/list/MyHistoricActivities';
import OtherHistoricActivities from 'mod_perform/components/user_activities/list/OtherHistoricActivities';
// Query
import historicActivitiesQuery from 'mod_perform/graphql/historic_activities';
import otherHistoricActivitiesQuery from 'mod_perform/graphql/other_historic_activities';

export default {
  components: {
    MyHistoricActivities,
    OtherHistoricActivities,
    Loader,
  },

  props: {
    /**
     * The id of the logged in user.
     */
    currentUserId: {
      required: true,
      type: Number,
    },
  },

  data() {
    return {
      historicActivities: [],
      otherHistoricActivities: [],
    };
  },

  apollo: {
    historicActivities: {
      query: historicActivitiesQuery,
      fetchPolicy: 'network-only', // Always refetch data on tab change
      update: data => data['mod_perform_historic_activities'],
    },
    otherHistoricActivities: {
      query: otherHistoricActivitiesQuery,
      fetchPolicy: 'network-only', // Always refetch data on tab change
      update: data => data['mod_perform_other_historic_activities'],
    },
  },
};
</script>
<lang-strings>
  {
    "mod_perform": [
      "user_historic_activities_title",
      "user_historic_activities_about_others_title"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-performUserHistoricActivities {
  &__heading {
    margin: var(--gap-8) 0 var(--gap-6) 0;
    padding-right: var(--gap-8);
  }

  @include tui-font-body();
}
</style>
