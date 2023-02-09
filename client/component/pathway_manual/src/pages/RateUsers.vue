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
  <div class="tui-rateUserCompetencies">
    <PageHeading :title="$str('rate_competencies', 'pathway_manual')" />

    <RoleSelector
      :specified-role="specifiedRole"
      :has-default-selected="true"
      @role-selected="roleSelected"
    />
    <RateUsersList v-if="role" :role="role" :current-user-id="currentUserId" />
  </div>
</template>

<script>
import PageHeading from 'tui/components/layouts/PageHeading';
import RateUsersList from 'pathway_manual/components/RateUsersList';
import RoleSelector from 'pathway_manual/components/RoleSelector';
import { notify } from 'tui/notifications';

export default {
  components: {
    PageHeading,
    RateUsersList,
    RoleSelector,
  },

  props: {
    specifiedRole: {
      type: String,
    },
    currentUserId: {
      required: true,
      type: Number,
    },
    toastMessage: {
      type: String,
    },
  },

  data() {
    return {
      role: this.specifiedRole,
    };
  },

  mounted() {
    if (this.toastMessage) {
      notify({ message: this.toastMessage });
    }
  },

  methods: {
    roleSelected(role) {
      this.role = role;
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
.tui-rateUserCompetencies {
  & > * + * {
    margin-top: var(--gap-4);
  }
}
</style>
