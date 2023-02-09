<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2021 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD's customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totaralearning.com] for more information.

  @author Simon Tegg <simon.tegg@totaralearning.com>
  @module mod_approval
-->
<template>
  <div class="tui-mod_approval-applicationDashboard">
    <div
      v-if="!$matches('loading') && $selectors.getIsLearnerEmpty($context)"
      class="tui-mod_approval-applicationDashboard__empty"
    >
      <h1 class="tui-mod_approval-applicationDashboard__emptyHeader">
        {{ $str('applications_empty', 'mod_approval') }}
      </h1>
      <div v-if="displayNewApplicationTrigger">
        <!-- TODO: TL-31227 add a real can-create-on-behalf -->
        <NewApplicationTrigger
          :can-create-on-behalf="pageProps['new-application-on-behalf']"
          :current-user-id="currentUserId"
        />
        <NewApplicationModal
          :is-open="$matches('createNew')"
          :current-user-id="currentUserId"
        />
      </div>
    </div>
    <!-- prevent table rendering in the background with an else-if condition -->
    <div v-else-if="!$matches('loading')">
      <div class="tui-mod_approval-applicationDashboard__titleRow">
        <h1 class="tui-mod_approval-applicationDashboard__title">
          {{ $str('menu_application', 'mod_approval') }}
        </h1>
        <!-- TODO: TL-31227 add a real can-create-on-behalf -->
        <div v-if="displayNewApplicationTrigger">
          <NewApplicationTrigger
            :can-create-on-behalf="pageProps['new-application-on-behalf']"
            :current-user-id="currentUserId"
          />
          <NewApplicationModal
            :is-open="$matches('createNew')"
            :current-user-id="currentUserId"
          />
        </div>
      </div>
      <div v-if="isApprover && awaitingResponse.length > 0">
        <h2 class="tui-mod_approval-applicationDashboard__heading">
          {{ awaitingResponseText }}
        </h2>
        <OverflowContainer
          :total="$selectors.getApplicationsTotal($context)"
          :items="awaitingResponse"
          @show-all="$send($e.SHOW_ALL_PENDING)"
        >
          <template v-slot:default="{ item: { id, title, user, submitted } }">
            <ResponseCard
              :id="id"
              :title="title"
              :user="user"
              :submitted="submitted"
            />
          </template>
        </OverflowContainer>
        <h2 class="tui-mod_approval-applicationDashboard__heading">
          {{ $str('all_applications', 'mod_approval') }}
        </h2>
      </div>
      <ApplicationsTable :page-props="pageProps" />
    </div>
    <!-- Loader rendered last to avoid element reflow on fullPageLoading: true -->
    <Loader v-if="fullPageLoading" :fullpage="true" :loading="true" />
  </div>
</template>

<script>
import dashboardMachine from 'mod_approval/application/index/machine';
import Loader from 'tui/components/loading/Loader';
import OverflowContainer from 'tui/components/overflow_container/OverflowContainer';
import ApplicationsTable from 'mod_approval/components/application/dashboard/ApplicationsTable';
import ResponseCard from 'mod_approval/components/cards/ResponseCard';
import NewApplicationModal from 'mod_approval/components/application/dashboard/NewApplicationModal';
import NewApplicationTrigger from 'mod_approval/components/application/dashboard/NewApplicationTrigger';

export default {
  components: {
    Loader,
    OverflowContainer,
    ApplicationsTable,
    ResponseCard,
    NewApplicationModal,
    NewApplicationTrigger,
  },

  props: {
    currentUserId: Number,
    contextId: Number,
    pageProps: {
      required: true,
      type: Object,
    },
  },

  xState: {
    machine() {
      return dashboardMachine({
        showApplicationsFromOthers: Boolean(
          this.pageProps.tabs['applications-from-others']
        ),
        currentUserId: String(this.currentUserId),
      });
    },
    mapQueryParamsToContext({ notify, notify_type }) {
      return {
        notify: notify ? notify : null,
        notifyType: notify_type ? notify_type : null,
      };
    },
    mapContextToQueryParams(context, prevContext) {
      const notifyAndNotifyType = context.notify && context.notifyType;
      return {
        notify: notifyAndNotifyType ? prevContext.notify : undefined,
        notify_type: notifyAndNotifyType ? prevContext.notifyType : undefined,
      };
    },
  },

  computed: {
    fullPageLoading() {
      return ['loading', 'creatingApplication'].some(this.$matches);
    },

    displayNewApplicationTrigger() {
      return (
        this.$selectors.getDefaultJobAssignment(this.$context) ||
        this.pageProps['new-application-on-behalf']
      );
    },

    showApplicationsFromOthers() {
      return Boolean(this.pageProps.tabs['applications-from-others']);
    },

    awaitingResponse() {
      return this.$selectors.getApplications(this.$context);
    },

    applicationMenuItems() {
      return this.$selectors.getApplicationMenuItems(this.$context);
    },

    titleString() {
      return this.$str('menu_application', 'mod_approval');
    },

    isApprover() {
      return this.pageProps.tabs['applications-from-others'];
    },

    awaitingResponseText() {
      const total = this.$selectors.getApplicationsTotal(this.$context);

      const stringIdentifier =
        total > 1
          ? 'x_applications_awaiting_response'
          : 'one_application_awaiting_response';

      return this.$str(stringIdentifier, 'mod_approval', total);
    },
  },
};
</script>
<lang-strings>
{
  "mod_approval": [
    "all_applications",
    "applications_empty",
    "menu_application",
    "one_application_awaiting_response",
    "x_applications_awaiting_response",
    "error:create_application",
    "error:generic",
    "success:delete_application"
  ]
}
</lang-strings>
<style lang="scss">
.tui-mod_approval-applicationDashboard {
  padding: var(--gap-8);

  &__empty {
    text-align: center;
  }

  &__emptyHeader {
    margin-top: var(--gap-12);
    margin-bottom: var(--gap-12);
    font-size: var(--font-size-30);
  }

  &__titleRow {
    display: flex;
    justify-content: space-between;
    margin-bottom: var(--gap-8);
  }

  &__title {
    margin-top: 0;
    margin-bottom: 0;
  }

  &__action {
    align-self: flex-start;
  }

  &__heading {
    @include tui-font-heading-small();
    margin-top: var(--gap-8);
    margin-bottom: var(--gap-4);
  }

  &__awaiting {
    margin-top: var(--gap-4);
    margin-bottom: var(--gap-8);
  }
}
</style>
