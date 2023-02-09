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
  <div class="tui-performUserActivities">
    <PageHeading :title="$str('user_activities_page_title', 'mod_perform')">
      <template v-slot:buttons>
        <Button
          v-if="canPotentiallyManageParticipants"
          :text="$str('manage_participation', 'mod_perform')"
          @click="openParticipationModal = true"
        />
        <ActionLink
          v-if="canPotentiallyReportOnSubjects"
          :href="viewResponseDataUrl"
          :text="$str('view_response_data', 'mod_perform')"
        />
      </template>
    </PageHeading>

    <ManualParticipantsSelectionBanner
      v-if="requireManualParticipantsNotification"
      :item-count="requireManualParticipantsCount"
    />

    <div class="tui-performUserActivities__content">
      <Tabs
        :controlled="true"
        :selected="currentTabId"
        @input="changeCurrentTab"
      >
        <Tab
          v-for="tab in activityRoleTabs.tabs"
          :id="tab.id"
          :key="tab.id"
          :name="tab.name"
        >
          <UserActivityList
            :about-role="tab.id"
            :current-user-id="currentUserId"
            :filter-options="filterOptions"
            :is-about-others="tab.about_others"
            :print-url="printActivityUrl"
            :priority-url="priorityUrl"
            :sort-by-options="sortByOptions"
            :tab-filters="tabFilters"
            :view-url="viewActivityUrl"
            @update-url-params="updateUrlParams"
          />
        </Tab>

        <Tab
          v-if="isHistoricActivitiesEnabled"
          :id="$id('your-historic-activities-tab')"
          :name="$str('user_activities_historic_activities', 'mod_perform')"
        >
          <UserHistoricActivityList :current-user-id="currentUserId" />
        </Tab>
      </Tabs>
    </div>

    <!--
      The v-if is important here because the query performed by the SelectActivityModal can be quite expensive
      so we only want to render/perform the component if we really need to
    -->
    <SelectActivityModal
      v-if="openParticipationModal"
      v-model="openParticipationModal"
    />
  </div>
</template>

<script>
import { url, parseQueryString } from 'tui/util';
import ActionLink from 'tui/components/links/ActionLink';
import Button from 'tui/components/buttons/Button';
import ManualParticipantsSelectionBanner from 'mod_perform/components/user_activities/ManualParticipantsSelectionBanner';
import PageHeading from 'tui/components/layouts/PageHeading';
import SelectActivityModal from 'mod_perform/components/manage_activity/participation/SelectActivityModal';
import Tab from 'tui/components/tabs/Tab';
import Tabs from 'tui/components/tabs/Tabs';
import UserActivityList from 'mod_perform/components/user_activities/list/Activities';
import UserHistoricActivityList from 'mod_perform/components/user_activities/list/HistoricActivities';
import { notify } from 'tui/notifications';

export default {
  components: {
    ActionLink,
    Button,
    ManualParticipantsSelectionBanner,
    PageHeading,
    SelectActivityModal,
    Tab,
    Tabs,
    UserActivityList,
    UserHistoricActivityList,
  },
  props: {
    // Tabs for all roles with user visible activities
    activityRoleTabs: {
      required: true,
      type: Object,
    },
    canPotentiallyManageParticipants: {
      required: true,
      type: Boolean,
    },
    canPotentiallyReportOnSubjects: {
      required: true,
      type: Boolean,
    },
    closedOnCompletion: {
      type: Boolean,
      default: false,
    },
    completionSaveSuccess: {
      required: true,
      type: Boolean,
    },
    // The id of the logged in user.
    currentUserId: {
      required: true,
      type: Number,
    },
    filterOptions: Object,
    initialFilters: Object,
    // The id of the tab to be initially open
    initiallyOpenTab: Number,
    isHistoricActivitiesEnabled: {
      required: true,
      type: Boolean,
    },
    printActivityUrl: {
      required: true,
      type: String,
    },
    priorityUrl: {
      required: true,
      type: String,
    },
    viewResponseDataUrl: {
      required: true,
      type: String,
    },
    requireManualParticipantsCount: Number,
    requireManualParticipantsNotification: {
      type: Boolean,
      default: false,
    },
    sortOptions: Object,
    viewActivityUrl: {
      required: true,
      type: String,
    },
  },

  data() {
    return {
      openParticipationModal: false,
      // Existing filters for the current tab
      tabFilters: this.initialFilters,
      urlTabChange: false,
    };
  },

  computed: {
    /**
     * Check the initially open tab ID is available
     *
     * @return {Number}
     */
    currentTabId() {
      let targetTab = this.urlTabChange || this.initiallyOpenTab;
      let tabExists = this.activityRoleTabs.tabs.find(tab => {
        return tab.id === targetTab;
      });

      if (
        typeof targetTab !== 'number' &&
        targetTab.includes('your-historic-activities-tab')
      ) {
        return targetTab;
      }

      // If tab doesn't exist fallback to the users own tab
      if (!tabExists) {
        return this.activityRoleTabs.tabs.find(tab => {
          return !tab.about_others;
        }).id;
      }

      return targetTab;
    },

    sortByOptions() {
      if (!this.sortOptions) {
        return null;
      }
      return this.sortOptions.options;
    },
  },

  mounted() {
    window.addEventListener('popstate', this.pageChange);

    // Show the save notification if we have been redirected back here after saving.
    if (this.completionSaveSuccess) {
      let message = this.closedOnCompletion
        ? 'toast_success_save_close_on_completion_response'
        : 'toast_success_save_response';
      this.showSuccessNotification(message, 'mod_perform');
    }
  },

  destroyed() {
    window.removeEventListener('popstate', this.pageChange);
  },

  methods: {
    /**
     * A different tab has been selected
     *
     * @param {Number} tab
     */
    changeCurrentTab(tab) {
      // Reset the filters and switch to new tab
      this.tabFilters = null;
      this.urlTabChange = tab;
    },

    /**
     * Shows a success toast.
     * @param {String} message
     * @param {String} component
     */
    showSuccessNotification(message, component) {
      notify({
        message: this.$str(message, component),
        type: 'success',
      });
    },

    /**
     * Browser back/forward button has been pressed
     * Update current page filters to match URL variables
     *
     */
    pageChange() {
      const urlParams = parseQueryString(window.location.search);
      const tabID = parseInt(urlParams.about_role);

      // If back/forward browser buttons are changing the current tab
      if (tabID !== this.currentTabId) {
        this.urlTabChange = tabID;
      }

      this.tabFilters = urlParams;
    },

    /**
     * Update the url params based on the current tab and it's filters
     *
     * @param {Object} filterParams
     */
    updateUrlParams(filterParams) {
      this.tabFilters = filterParams;

      let currentURLData = parseQueryString(window.location.search);
      let urlData = Object.assign({}, currentURLData);

      Object.keys(filterParams).forEach(key => {
        const value = filterParams[key];
        if (value !== null && value !== '' && value !== false) {
          urlData[key] = key === 'about_role' ? value.toString() : value;
        } else {
          delete urlData[key];
        }
      });

      // If the URL params haven't changed don't updated
      if (JSON.stringify(currentURLData) === JSON.stringify(urlData)) {
        return;
      }

      window.history.pushState(
        null,
        null,
        url(window.location.pathname, urlData)
      );
    },
  },
};
</script>

<lang-strings>
  {
    "mod_perform": [
      "manage_participation",
      "toast_success_save_close_on_completion_response",
      "toast_success_save_response",
      "user_activities_historic_activities",
      "user_activities_page_title",
      "view_response_data"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-performUserActivities {
  @include tui-font-body();

  & > * + * {
    margin-top: var(--gap-8);
  }
}
</style>
