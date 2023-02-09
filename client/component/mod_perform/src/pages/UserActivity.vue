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
  @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
  @module mod_perform
-->

<template>
  <div class="tui-performUserActivity">
    <Loader :loading="$apollo.loading" />
    <NotificationBanner
      v-if="activityNotFound"
      :dismissable="false"
      :message="$str('user_activities_activity_does_not_exist', 'mod_perform')"
      type="error"
    />
    <ActivityContentPrint
      v-else-if="print && subjectInstance"
      :activity="subjectInstance.activity"
      :participant-instance-id="participantInstanceId"
      :subject-instance-id="subjectInstanceId"
      :subject-user="subjectInstance.subject_user"
      :created-at="subjectInstance.created_at"
      :due-date="dueDate"
      :job-assignments="jobAssignments"
      :printed-on-date="printedOnDate"
    />
    <ActivityContent
      v-else-if="!print && subjectInstance"
      :current-user-id="currentUserId"
      :user-activities-url="userActivitiesUrl"
      :activity="subjectInstance.activity"
      :created-at="subjectInstance.created_at"
      :participant-instance-id="participantInstanceId"
      :subject-instance-id="subjectInstanceId"
      :participant-section-id="participantSectionId"
      :subject-user="subjectInstance.subject_user"
      :token="token"
      :job-assignments="jobAssignments"
    />
  </div>
</template>

<script>
import Loader from 'tui/components/loading/Loader';
import NotificationBanner from 'tui/components/notifications/NotificationBanner';
import ActivityContent from 'mod_perform/components/user_activities/ActivityContent';
import ActivityContentPrint from 'mod_perform/components/user_activities/ActivityContentPrint';
// Query
import externalSubjectInstanceQuery from 'mod_perform/graphql/subject_instance_for_external_participant_nosession';
import subjectInstanceQuery from 'mod_perform/graphql/subject_instance_for_participant';

export default {
  components: {
    Loader,
    NotificationBanner,
    ActivityContent,
    ActivityContentPrint,
  },
  props: {
    /**
     * The id of the logged in user.
     */
    currentUserId: {
      required: false,
      type: Number,
      default: null,
    },
    userActivitiesUrl: {
      required: false,
      type: String,
    },
    participantInstanceId: {
      required: false,
      type: Number,
    },
    participantSectionId: {
      required: false,
      type: Number,
    },
    subjectInstanceId: {
      required: false,
      type: Number,
    },
    token: {
      required: false,
      type: String,
      default: '',
    },
    print: Boolean,
    printedOnDate: String,
  },

  data() {
    return {
      jobAssignments: [],
      userActivity: null,
      subjectInstance: null,
    };
  },

  apollo: {
    subjectInstance: {
      query() {
        return this.isExternalParticipant
          ? externalSubjectInstanceQuery
          : subjectInstanceQuery;
      },
      skip() {
        return !this.subjectInstanceId;
      },
      variables() {
        return {
          subject_instance_id: this.subjectInstanceId,
          token: this.token,
        };
      },
      update(data) {
        return this.isExternalParticipant
          ? data['mod_perform_subject_instance_for_external_participant']
          : data['mod_perform_subject_instance_for_participant'];
      },
    },
  },

  computed: {
    /**
     * Returns true if the current user is an external participant,
     * means the token is set
     * @return {Boolean}
     */
    isExternalParticipant() {
      return this.token.length > 0;
    },

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

    /**
     * Returns the activity due date if there is one.
     *
     * @return {string} or null
     */
    dueDate() {
      return this.subjectInstance && this.subjectInstance.due_on
        ? this.subjectInstance.due_on.due_date
        : null;
    },
  },

  watch: {
    /**
     * Extracts the job assignments from the associated subject instance.
     */
    subjectInstance() {
      this.jobAssignments =
        this.subjectInstance && !this.isExternalParticipant
          ? this.subjectInstance.static_instances
          : [];
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
