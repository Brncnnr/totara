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

  @author Kevin Hottinger <kevin.hottinger@totaralearning.com>
  @package contentmarketplaceactivity_linkedin
-->
<template>
  <Layout
    v-if="activity"
    class="tui-linkedinActivity"
    :banner-image-url="course.image"
    :loading-full-page="$apollo.loading"
    :title="activity.name"
  >
    <template
      v-if="course.course_format.has_course_view_page"
      v-slot:content-nav
    >
      <PageBackLink :link="course.url" :text="course.fullname" />
    </template>

    <!-- Banner image content area -->
    <template v-slot:banner-content="{ stacked }">
      <div class="tui-linkedinActivity__admin">
        <AdminMenu :stacked-layout="stacked" />
      </div>
    </template>

    <!-- Notification banner (Guest/enrolment message) -->
    <template v-if="!interactor.is_enrolled" v-slot:feedback-banner>
      <NotificationBanner type="info">
        <template v-slot:body>
          <ActionCard :no-border="true">
            <template v-slot:card-body>
              {{ enrolBannerText }}
            </template>
            <template v-if="canEnrol" v-slot:card-action>
              <!--
                Using title for button, to allow Selenium finding this button.
                This is happening for admin user, because admin user can see more
                than one enrol button.
              -->
              <Button
                :styleclass="{ primary: 'true' }"
                :title="
                  $str(
                    'enrol_to_course',
                    'mod_contentmarketplace',
                    course.fullname
                  )
                "
                :text="$str('enrol', 'core_enrol')"
                @click="enrol"
              />
            </template>
          </ActionCard>
        </template>
      </NotificationBanner>
    </template>

    <template v-slot:main-content>
      <div class="tui-linkedinActivity__body">
        <Button
          :disabled="!canLaunch"
          :styleclass="{ primary: 'true' }"
          :text="$str('launch', 'mod_contentmarketplace')"
          @click="launch"
        />

        <hr class="tui-linkedinActivity__divider" />

        <!-- Current status and self completion -->
        <div
          v-if="completionEnabled && interactor.is_enrolled"
          class="tui-linkedinActivity__status"
          :class="{
            'tui-linkedinActivity__progressContainer': isProgressBarEnabled,
          }"
        >
          <div class="tui-linkedinActivity__status-completion">
            <Lozenge
              :text="
                isActivityCompleted
                  ? $str('activity_status_completed', 'mod_contentmarketplace')
                  : $str(
                      'activity_status_not_completed',
                      'mod_contentmarketplace'
                    )
              "
            />
          </div>

          <Progress
            v-if="isProgressBarEnabled"
            :value="getProgress"
            class="tui-linkedinActivity__status-progress"
          />

          <!-- Display the completion toggle if there is self-completion enabled and user hasn't completed via RPL. -->
          <ToggleSwitch
            v-if="selfCompletionEnabled && !module.rpl"
            v-model="setCompletion"
            class="tui-linkedinActivity__status-toggle"
            :text="
              $str('activity_set_self_completion', 'mod_contentmarketplace')
            "
            :toggle-first="true"
            @input="setCompletionHandler"
          />
        </div>
      </div>

      <div class="tui-linkedinActivity__details">
        <h3 class="tui-linkedinActivity__details-header">
          {{ $str('course_details', 'mod_contentmarketplace') }}
        </h3>
        <div class="tui-linkedinActivity__details-content">
          <div class="tui-linkedinActivity__details-bar">
            <!-- Course completion time -->
            <div>
              <span class="sr-only">
                {{
                  $str(
                    'a11y_activity_time_to_complete',
                    'mod_contentmarketplace'
                  )
                }}
              </span>
              {{ learningObject.time_to_complete }}
            </div>
            <!-- Course level (Beginner, intermediate, advanced) -->
            <div>
              <span class="sr-only">
                {{ $str('a11y_activity_difficulty', 'mod_contentmarketplace') }}
              </span>
              {{ learningObject.display_level }}
            </div>
            <!-- Last updated  -->
            <div>
              {{
                $str(
                  'updated_at',
                  'mod_contentmarketplace',
                  learningObject.last_updated_at
                )
              }}
            </div>
          </div>

          <div
            class="tui-linkedinActivity__details-desc"
            v-html="activity.intro"
          />
        </div>
      </div>
    </template>
  </Layout>
</template>

<script>
import ActionCard from 'tui/components/card/ActionCard';
import AdminMenu from 'tui/components/settings_navigation/SettingsNavigation';
import Button from 'tui/components/buttons/Button';
import Layout from 'mod_contentmarketplace/components/layouts/LayoutBannerTwoColumn';
import Lozenge from 'tui/components/lozenge/Lozenge';
import NotificationBanner from 'tui/components/notifications/NotificationBanner';
import PageBackLink from 'tui/components/layouts/PageBackLink';
import Progress from 'tui/components/progress/Progress';
import ToggleSwitch from 'tui/components/toggle/ToggleSwitch';
// Utils
import { notify } from 'tui/notifications';
import {
  COMPLETION_STATUS_UNKNOWN,
  COMPLETION_STATUS_INCOMPLETE,
  COMPLETION_TRACKING_NONE,
  COMPLETION_TRACKING_MANUAL,
  COMPLETION_CONDITION_CONTENT_MARKETPLACE,
} from 'mod_contentmarketplace/constants';

// GraphQL
import LinkedinActivityQuery from 'contentmarketplaceactivity_linkedin/graphql/linkedin_activity';
import setSelfCompletionMutation from 'mod_contentmarketplace/graphql/set_self_completion';
import requestNonInteractiveEnrol from 'mod_contentmarketplace/graphql/request_non_interactive_enrol';

export default {
  components: {
    ActionCard,
    AdminMenu,
    Button,
    Layout,
    Lozenge,
    NotificationBanner,
    PageBackLink,
    Progress,
    ToggleSwitch,
  },

  props: {
    /**
     * The course's module id, not the content marketplace id.
     */
    cmId: {
      type: Number,
      required: true,
    },

    /**
     * Check it has notification or not.
     */
    hasNotification: {
      type: Boolean,
      required: true,
    },
  },

  data() {
    return {
      setCompletion: false,

      // We need to store the initial states of the query data state here to ensure
      // Vue watches them and updates the DOM accordingly when the query gets updated.
      interactor: {
        can_enrol: false,
        can_launch: false,
        has_view_capability: false,
        is_enrolled: false,
        is_site_guest: false,
        non_interactive_enrol_instance_enabled: false,
        supports_non_interactive_enrol: false,
      },
      module: {
        completionstatus: COMPLETION_STATUS_UNKNOWN,
        rpl: false,
      },
    };
  },

  computed: {
    isProgressBarEnabled() {
      return this.completionMarketplace && !this.selfCompletionEnabled;
    },
    canEnrol() {
      return (
        this.interactor.can_enrol &&
        !this.interactor.is_site_guest &&
        this.interactor.non_interactive_enrol_instance_enabled
      );
    },

    canLaunch() {
      if (this.interactor.can_enrol) {
        return false;
      }
      return this.interactor.can_launch || this.interactor.is_site_guest;
    },

    enrolBannerText() {
      if (this.interactor.has_view_capability) {
        return this.interactor.non_interactive_enrol_instance_enabled
          ? this.$str('viewing_as_enrollable_admin', 'mod_contentmarketplace')
          : this.$str(
              'viewing_as_enrollable_admin_self_enrol_disabled',
              'mod_contentmarketplace'
            );
      }

      return this.canEnrol
        ? this.$str('viewing_as_enrollable_guest', 'mod_contentmarketplace')
        : this.$str('viewing_as_guest', 'mod_contentmarketplace');
    },

    isActivityCompleted() {
      return (
        this.module.completionstatus !== COMPLETION_STATUS_UNKNOWN &&
        this.module.completionstatus !== COMPLETION_STATUS_INCOMPLETE
      );
    },

    completionEnabled() {
      return this.module.completion !== COMPLETION_TRACKING_NONE;
    },

    selfCompletionEnabled() {
      return this.module.completion === COMPLETION_TRACKING_MANUAL;
    },

    completionMarketplace() {
      return (
        this.activity.completion_condition ===
        COMPLETION_CONDITION_CONTENT_MARKETPLACE
      );
    },

    getProgress() {
      if (this.module.progress !== 100 && this.isActivityCompleted) {
        return 100;
      }
      return this.module.progress;
    },
  },

  mounted() {
    if (this.hasNotification) {
      notify({
        message: this.$str('enrol_success_message', 'mod_contentmarketplace'),
        type: 'success',
      });
    }
  },

  apollo: {
    activity: {
      query: LinkedinActivityQuery,
      variables() {
        return {
          cm_id: this.cmId,
        };
      },
      update({ instance: data }) {
        const activity = data.module;
        this.course = activity.course;
        this.interactor = activity.interactor;
        this.learningObject = data.learning_object;
        this.module = activity.course_module;
        this.setCompletion = this.isActivityCompleted;
        return activity;
      },
    },
  },

  methods: {
    async launch() {
      const url = this.learningObject.sso_launch_url
        ? this.learningObject.sso_launch_url
        : this.learningObject.web_launch_url;
      window.open(url, 'linkedIn_course_window');
    },

    async setCompletionHandler() {
      await this.$apollo.mutate({
        mutation: setSelfCompletionMutation,
        refetchAll: false,
        variables: {
          cm_id: this.cmId,
          status: this.setCompletion,
        },
      });
      this.$apollo.queries.activity.refetch();
    },

    async enrol() {
      if (this.interactor.supports_non_interactive_enrol) {
        await this.nonInteractiveEnrol();
      } else {
        window.location.href = this.$url('/enrol/index.php', {
          id: this.course.id,
        });
      }
    },

    async nonInteractiveEnrol() {
      let {
        data: { result },
      } = await this.$apollo.mutate({
        mutation: requestNonInteractiveEnrol,
        variables: { cm_id: this.cmId },
        refetchAll: true,
      });

      if (result) {
        notify({
          message: this.$str('enrol_success_message', 'mod_contentmarketplace'),
          type: 'success',
        });
      }
    },
  },
};
</script>

<lang-strings>
  {
    "mod_contentmarketplace": [
      "a11y_activity_difficulty",
      "a11y_activity_time_to_complete",
      "activity_contents",
      "activity_set_self_completion",
      "activity_status_completed",
      "activity_status_not_completed",
      "course_details",
      "enrol_to_course",
      "enrol_success_message",
      "internal_error",
      "launch",
      "toggle_off_error",
      "toggle_on_error",
      "updated_at",
      "viewing_as_enrollable_admin",
      "viewing_as_enrollable_admin_self_enrol_disabled",
      "viewing_as_enrollable_guest",
      "viewing_as_guest"
    ],
    "core_enrol": [
      "enrol"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-linkedinActivity {
  &__admin {
    margin-left: auto;
  }

  &__body {
    & > * + * {
      margin-top: var(--gap-5);
    }
  }

  &__collapsibleContent {
    padding: 0 var(--gap-10);
  }

  &__divider {
    margin-top: var(--gap-9);
    margin-bottom: var(--gap-9);
  }

  &__progressContainer {
    @media (min-width: $tui-screen-xs) {
      width: 50%;
    }
  }

  &__status {
    display: flex;
    flex-wrap: wrap;
    align-items: center;

    & > * {
      margin-bottom: var(--gap-2);
    }

    &-completion {
      margin-right: var(--gap-4);
    }

    &-toggle {
      flex-wrap: wrap;
    }

    &-progress {
      flex-grow: 1;
      flex-shrink: 0;

      @media (max-width: $tui-screen-xs) {
        width: 100%;
        margin-top: var(--gap-2);
      }
    }
  }

  &__details {
    margin-top: var(--gap-9);

    & > * + * {
      margin-top: var(--gap-2);
    }

    &-header {
      margin: 0;
      @include tui-font-heading-small();
    }

    &-bar {
      @include tui-separator-dot();
      color: var(--color-neutral-6);
      @include tui-font-heading-label();
    }

    &-content {
      & > * + * {
        margin-top: var(--gap-4);
      }
    }

    &-desc {
      img {
        max-width: 100%;
      }
    }
  }
}
</style>
