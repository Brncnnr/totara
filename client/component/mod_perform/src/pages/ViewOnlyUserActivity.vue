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

  @author Jaron Steenson <jaron.steenson@totaralearning.com>
  @module mod_perform
-->

<template>
  <div class="tui-performUserActivity">
    <Loader :loading="$apollo.loading">
      <NotificationBanner
        v-if="activityNotFound"
        :dismissable="false"
        :message="
          $str('user_activities_activity_does_not_exist', 'mod_perform')
        "
        type="error"
      />
      <ActivityContent
        v-else-if="activityFound"
        :current-user-id="currentUserId"
        :activity="subjectInstance.activity"
        :created-at="subjectInstance.created_at"
        :subject-instance-id="subjectInstanceId"
        :section-id="sectionId"
        :subject-user="subjectInstance.subject_user"
        :job-assignments="subjectInstance.static_instances"
      />
    </Loader>
  </div>
</template>

<script>
import subjectInstanceQuery from 'mod_perform/graphql/subject_instance_for_participant';
import Loader from 'tui/components/loading/Loader';
import NotificationBanner from 'tui/components/notifications/NotificationBanner';
import ActivityContent from 'mod_perform/components/user_activities/ActivityContent';

export default {
  components: {
    Loader,
    NotificationBanner,
    ActivityContent,
  },
  props: {
    /**
     * The id of the logged in user.
     */
    currentUserId: {
      required: true,
      type: Number,
      default: null,
    },

    /**
     * For gql data look up.
     */
    subjectInstanceId: {
      required: true,
      type: Number,
    },

    /**
     * For gql data look up.
     */
    sectionId: {
      required: false,
      type: Number,
    },
  },

  data() {
    return {
      hasLoaded: false,
      userActivity: null,
      subjectInstance: null,
    };
  },

  apollo: {
    subjectInstance: {
      query() {
        return subjectInstanceQuery;
      },
      skip() {
        return !this.subjectInstanceId;
      },
      variables() {
        return {
          subject_instance_id: this.subjectInstanceId,
        };
      },
      update(data) {
        this.hasLoaded = true;

        return data['mod_perform_subject_instance_for_participant'];
      },
    },
  },

  computed: {
    /**
     * Trying to load the activity from the server resulted in either
     * the activity not being found at all or the user not having permission
     * to view the activity.
     */
    activityNotFound() {
      if (this.subjectInstanceId === null) {
        return true;
      }

      return !this.$apollo.loading && this.subjectInstance === null;
    },
    activityFound() {
      if (this.subjectInstanceId === null) {
        return false;
      }

      return !this.$apollo.loading && this.subjectInstance !== null;
    },
  },
};
</script>

<lang-strings>
  {
    "mod_perform": [
      "user_activities_activity_does_not_exist"
    ]
  }
</lang-strings>
