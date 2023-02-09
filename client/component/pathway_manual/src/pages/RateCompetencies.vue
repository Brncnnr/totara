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

  @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
  @module pathway_manual
-->

<template>
  <Loader class="tui-rateCompetencies" :loading="$apollo.loading">
    <MiniProfileCard
      v-if="isForAnotherUser && user"
      :display="user.card_display"
    />
    <PageHeading :title="$str('rate_competencies', 'pathway_manual')" />
    <RoleSelector
      v-if="isForAnotherUser"
      :user-id="userId"
      :specified-role="specifiedRole"
      :show-warning="hasUnsavedRatings"
      @role-selected="roleSelected"
    />
    <RateUserCompetencies
      v-if="role && user"
      :user="user"
      :role="role"
      :current-user-id="currentUserId"
      :assignment-id="assignmentId"
      @has-unsaved-ratings="has => (hasUnsavedRatings = has)"
      @go-back="goBack(0)"
      @saved="goBack($event)"
    />
  </Loader>
</template>

<script>
import Loader from 'tui/components/loading/Loader';
import MiniProfileCard from 'tui/components/profile/MiniProfileCard';
import PageHeading from 'tui/components/layouts/PageHeading';
import RateUserCompetencies from 'pathway_manual/components/RateUserCompetencies';
import RoleSelector from 'pathway_manual/components/RoleSelector';
import UserQuery from 'totara_competency/graphql/user';

export default {
  components: {
    Loader,
    MiniProfileCard,
    PageHeading,
    RateUserCompetencies,
    RoleSelector,
  },

  props: {
    userId: {
      required: true,
      type: Number,
    },
    specifiedRole: {
      type: String,
    },
    currentUserId: {
      required: true,
      type: Number,
    },
    returnUrl: {
      required: true,
      type: String,
    },
    assignment: {
      type: Object,
    },
  },

  data() {
    return {
      role: this.specifiedRole,
      user: null,
      hasUnsavedRatings: false,
    };
  },

  apollo: {
    user: {
      query: UserQuery,
      variables() {
        return { user_id: this.userId };
      },
      update: data => data.totara_competency_user,
    },
  },

  computed: {
    hasRole() {
      return this.role != null;
    },

    isForAnotherUser() {
      return this.userId !== this.currentUserId;
    },

    assignmentId() {
      if (this.assignment) {
        return parseInt(this.assignment.assignment_id);
      }
      return null;
    },
  },

  methods: {
    roleSelected(selectedRole) {
      this.hasUnsavedRatings = false;
      this.role = selectedRole;
    },

    goBack(ratingsSaved) {
      if (ratingsSaved === 0) {
        window.location = this.returnUrl;
      } else {
        window.location = this.$url(this.returnUrl, {
          rating_success: ratingsSaved,
        });
      }
    },
  },
};
</script>

<lang-strings>
  {
    "pathway_manual": [
      "rate_competencies"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-rateCompetencies {
  & > * + * {
    margin-top: var(--gap-4);
  }
}
</style>
