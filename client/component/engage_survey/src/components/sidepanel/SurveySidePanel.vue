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

  @author Qingyang Liu <Qingyang.liu@totaralearning.com>
  @module engage_survey
-->

<template>
  <div class="tui-engageSurveySidePanel">
    <template v-if="!$apollo.loading">
      <ConfirmationModal
        :open="openModalFromAction"
        :title="$str('deletewarningtitle', 'engage_survey')"
        :confirm-button-text="$str('delete', 'core')"
        :loading="deleting"
        @confirm="handleDelete"
        @cancel="openModalFromAction = false"
      >
        <p>{{ $str('delete_survey_confirm_1', 'engage_survey') }}</p>
        <p>{{ $str('delete_survey_confirm_2', 'engage_survey') }}</p>
      </ConfirmationModal>
      <MiniProfileCard
        :no-border="true"
        :no-padding="true"
        :display="survey.resource.user.card_display"
        class="tui-engageSurveySidePanel__profile"
      >
        <template v-slot:drop-down-items>
          <DropdownItem
            v-if="survey.owned || survey.updateable"
            @click="openModalFromAction = true"
          >
            {{ $str('deletesurvey', 'engage_survey') }}
          </DropdownItem>
          <DropdownItem v-if="!survey.owned" @click="reportSurvey">
            {{ $str('reportsurvey', 'engage_survey') }}
          </DropdownItem>
        </template>
      </MiniProfileCard>

      <Tabs :transparent-tabs="true">
        <Tab
          id="overview"
          :name="$str('overview', 'totara_engage')"
          :disabled="true"
        >
          <p class="tui-engageSurveySidePanel__timeDescription">
            {{ survey.timedescription }}
          </p>
          <AccessSetting
            v-if="survey.owned || survey.updateable"
            :item-id="resourceId"
            component="engage_survey"
            :access-value="survey.resource.access"
            :topics="survey.topics"
            :submitting="false"
            :open-access-modal="openModalFromButtonLabel"
            :enable-time-view="false"
            :show-share-button="interactor.show_share_button"
            @close-modal="openModalFromButtonLabel = false"
            @access-update="updateAccess"
          />
          <AccessDisplay
            v-else
            :access-value="survey.resource.access"
            :topics="survey.topics"
            :show-button="false"
          />
          <MediaSetting
            :owned="survey.owned"
            :access-value="survey.resource.access"
            :instance-id="resourceId"
            :share-button-aria-label="shareButtonLabel"
            :shared-by-count="survey.sharedbycount"
            :like-button-aria-label="likeButtonLabel"
            :liked="survey.reacted"
            :show-like-button="interactor.can_react"
            :show-share-button="interactor.can_share"
            component-name="engage_survey"
            @access-update="updateAccess"
            @access-modal="openModalFromButtonLabel = true"
            @update-like-status="updateLikeStatus"
          />
        </Tab>
      </Tabs>
    </template>
  </div>
</template>

<script>
import AccessSetting from 'totara_engage/components/sidepanel/access/AccessSetting';
import AccessDisplay from 'totara_engage/components/sidepanel/access/AccessDisplay';
import ConfirmationModal from 'tui/components/modal/ConfirmationModal';
import MediaSetting from 'totara_engage/components/sidepanel/media/MediaSetting';
import apolloClient from 'tui/apollo_client';
import MiniProfileCard from 'tui/components/profile/MiniProfileCard';
import Tabs from 'tui/components/tabs/Tabs';
import Tab from 'tui/components/tabs/Tab';
import DropdownItem from 'tui/components/dropdown/DropdownItem';
import { notify } from 'tui/notifications';
import { AccessManager } from 'totara_engage/index';

// GraphQL
import getSurvey from 'engage_survey/graphql/get_survey';
import deleteSurvey from 'engage_survey/graphql/delete_survey';
import updateSurvey from 'engage_survey/graphql/update_survey';
import createReview from 'totara_reportedcontent/graphql/create_review';

export default {
  components: {
    AccessDisplay,
    ConfirmationModal,
    AccessSetting,
    MediaSetting,
    MiniProfileCard,
    Tabs,
    Tab,
    DropdownItem,
  },

  props: {
    resourceId: {
      type: [Number, String],
      required: true,
    },
    interactor: {
      type: Object,
      default: () => ({
        user_id: 0,
        can_bookmark: false,
        can_comment: false,
        can_react: false,
        can_share: false,
        show_share_button: false,
      }),
    },
  },

  apollo: {
    survey: {
      query: getSurvey,
      fetchPolicy: 'network-only',
      variables() {
        return {
          resourceid: this.resourceId,
        };
      },
    },
  },

  data() {
    return {
      survey: {},
      deleting: false,
      submitting: false,
      openModalFromButtonLabel: false,
      openModalFromAction: false,
    };
  },

  computed: {
    userEmail() {
      return this.survey.resource.user.email || '';
    },

    shareButtonLabel() {
      if (this.survey.owned) {
        return this.$str(
          'sharesurvey',
          'engage_survey',
          this.survey.resource.name
        );
      }

      return this.$str(
        'resharesurvey',
        'engage_survey',
        this.survey.resource.name
      );
    },

    likeButtonLabel() {
      if (this.survey.reacted) {
        return this.$str(
          'removelikesurvey',
          'engage_survey',
          this.survey.resource.name
        );
      }

      return this.$str(
        'likesurvey',
        'engage_survey',
        this.survey.resource.name
      );
    },

    isPrivateResource() {
      return AccessManager.isPrivate(this.survey.resource.access);
    },
  },

  methods: {
    handleDelete() {
      this.deleting = true;
      this.$apollo
        .mutate({
          mutation: deleteSurvey,
          variables: {
            resourceid: this.resourceId,
          },
          refetchAll: false,
        })
        .then(({ data }) => {
          if (data.result) {
            this.openModalFromAction = false;
            window.location.href = this.$url(
              '/totara/engage/your_resources.php'
            );
          }
        });
    },

    /**
     * Updates Access for this survey
     *
     * @param {String} access The access level of the survey
     * @param {Array} topics Topics that this survey should be shared with
     * @param {Array} shares An array of group id's that this survey is shared with
     */
    updateAccess({ access, topics, shares }) {
      this.submitting = true;
      this.$apollo
        .mutate({
          mutation: updateSurvey,
          refetchAll: false,
          variables: {
            resourceid: this.resourceId,
            access: access,
            topics: topics.map(({ id }) => id),
            shares: shares,
          },

          update: (proxy, { data }) => {
            proxy.writeQuery({
              query: getSurvey,
              variables: { resourceid: this.resourceId },
              data,
            });
          },
        })
        .finally(() => {
          this.submitting = false;
        });
    },

    /**
     *
     * @param {Boolean} status
     */
    updateLikeStatus(status) {
      let { survey } = apolloClient.readQuery({
        query: getSurvey,
        variables: {
          resourceid: this.resourceId,
        },
      });

      survey = Object.assign({}, survey);
      survey.reacted = status;

      apolloClient.writeQuery({
        query: getSurvey,
        variables: { resourceid: this.resourceId },
        data: { survey: survey },
      });
    },

    /**
     * Report the attached survey
     * @returns {Promise<void>}
     */
    async reportSurvey() {
      if (this.submitting) {
        return;
      }
      this.submitting = true;
      try {
        let response = await this.$apollo
          .mutate({
            mutation: createReview,
            refetchAll: false,
            variables: {
              component: 'engage_survey',
              area: '',
              item_id: this.resourceId,
              url: window.location.href,
            },
          })
          .then(response => response.data.review);

        if (response.success) {
          await notify({
            message: this.$str('reported', 'totara_reportedcontent'),
            type: 'success',
          });
        } else {
          await notify({
            message: this.$str('reported_failed', 'totara_reportedcontent'),
            type: 'error',
          });
        }
      } catch (e) {
        await notify({
          message: this.$str('error:reportsurvey', 'engage_survey'),
          type: 'error',
        });
      } finally {
        this.submitting = false;
      }
    },
  },
};
</script>
<lang-strings>
  {
    "core": [
      "delete"
    ],
    "engage_survey": [
      "deletewarningtitle",
      "delete_survey_confirm_1",
      "delete_survey_confirm_2",
      "likesurvey",
      "resharesurvey",
      "sharesurvey",
      "removelikesurvey",
      "deletesurvey",
      "reportsurvey",
      "error:reportsurvey"
    ],
    "totara_engage": [
      "overview"
    ],
    "totara_reportedcontent": [
      "reported",
      "reported_failed"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-engageSurveySidePanel {
  padding: var(--gap-8);

  &__profile {
    margin-bottom: var(--gap-8);
  }

  &__timeDescription {
    @include tui-font-body-small();
  }
}
</style>
