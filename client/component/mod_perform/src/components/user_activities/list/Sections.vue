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

  @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
  @module mod_perform
-->
<template>
  <div class="tui-performUserActivityListSections">
    <div
      v-for="subjectSection in subjectSectionsSubset"
      :key="subjectSection.section.id"
      class="tui-performUserActivityListSection"
    >
      <h3
        v-if="isMultiSectionActive"
        class="tui-performUserActivityListSection__header"
      >
        <template
          v-if="
            subjectSection.canParticipate &&
              getViewActivitySectionUrl(subjectSection)
          "
        >
          <a :href="getViewActivitySectionUrl(subjectSection)">
            {{ subjectSection.section.display_title }}
          </a>

          <span v-if="!subjectSection.canCurrentUserAnswer">
            {{ $str('user_activities_section_view_only', 'mod_perform') }}
          </span>
        </template>
        <template v-else>
          {{ subjectSection.section.display_title }}
        </template>
      </h3>

      <Table
        v-if="
          !anonymousResponses ||
            subjectSection.participationToDisplay.length > 0
        "
        :data="subjectSection.participationToDisplay"
        class="tui-performUserActivityListSection__data"
        :border-bottom-hidden="true"
        :border-separator-hidden="true"
        :border-top-hidden="!isMultiSectionActive"
        :hover-off="true"
        :stack-at="512"
      >
        <template v-slot:row="{ row }">
          <Cell
            size="4"
            :column-header="
              $str('user_activities_status_header_relationship', 'mod_perform')
            "
            :heavy="forCurrentUserInRole(row)"
            valign="center"
          >
            {{ row.relationship }}
          </Cell>

          <Cell
            size="4"
            :column-header="
              $str('user_activities_subject_header', 'mod_perform')
            "
            valign="center"
          >
            <ParticipantUserHeader
              :user-name="
                row.isForCurrentUser
                  ? $str('user_activities_you', 'mod_perform')
                  : row.participant.fullname
              "
              :profile-picture="row.participant.profileimageurlsmall"
              :profile-picture-alt="row.participant.profileimagealt"
              :regular-weight="!forCurrentUserInRole(row)"
              size="xxsmall"
            />
          </Cell>
          <Cell
            size="4"
            :column-header="
              $str(
                'user_activities_status_header_section_progress',
                'mod_perform'
              )
            "
            :heavy="forCurrentUserInRole(row)"
            valign="center"
          >
            {{ getStatusText(row.progressStatus) }}
            <Lock
              v-if="row.availabilityStatus === 'CLOSED'"
              :alt="$str('user_activities_closed', 'mod_perform')"
            />
            <OverdueLozenge v-if="row.isOverdue" />
          </Cell>
        </template>
      </Table>

      <template v-if="anonymousResponses">
        <hr />

        <div class="tui-performUserActivityListSection__summary">
          <p>
            {{
              $str(
                'user_activities_total_respondents',
                'mod_perform',
                subjectSection.summary.totalRespondents
              )
            }}
          </p>
          <p>
            {{
              $str(
                'user_activities_total_completed',
                'mod_perform',
                subjectSection.summary.totalCompleted
              )
            }}
          </p>
        </div>
      </template>
    </div>
  </div>
</template>

<script>
import Cell from 'tui/components/datatable/Cell';
import Lock from 'tui/components/icons/Lock';
import OverdueLozenge from 'mod_perform/components/user_activities/list/ActivityOverdue';
import ParticipantUserHeader from 'mod_perform/components/user_activities/participant/ParticipantUserHeader';
import Table from 'tui/components/datatable/Table';

export default {
  components: {
    Cell,
    Lock,
    OverdueLozenge,
    ParticipantUserHeader,
    Table,
  },

  props: {
    aboutRole: {
      required: true,
      type: Number,
    },
    activityId: {
      required: true,
      type: String,
    },
    anonymousResponses: {
      required: true,
      type: Boolean,
    },
    isMultiSectionActive: {
      required: true,
      type: Boolean,
    },
    subjectSections: {
      required: true,
      type: Array,
    },
    viewUrl: {
      required: true,
      type: String,
    },
  },

  computed: {
    /**
     * Reduce the data set to the required values for this template
     *
     */
    subjectSectionsSubset() {
      return this.subjectSections.map(item => {
        const participation = item.participant_sections.map(
          participantSection => {
            let relationship,
              relationship_id,
              participant = {};

            const isForCurrentUser =
              participantSection.participant_instance.is_for_current_user;

            // These fields are not included if answers are anonymous.
            if (isForCurrentUser || !this.anonymousResponses) {
              relationship =
                participantSection.participant_instance.core_relationship.name;
              relationship_id =
                participantSection.participant_instance.core_relationship.id;
              participant = participantSection.participant_instance.participant;
            }

            return {
              id: participantSection.id,
              isForCurrentUser,
              progressStatus: participantSection.progress_status,
              availabilityStatus: participantSection.availability_status,
              isOverdue: participantSection.is_overdue,
              canAnswer: participantSection.can_answer,
              participant,
              relationship,
              relationship_id,
            };
          }
        );

        const filteredParticipation = participation.filter(
          participantSection => {
            if (!this.anonymousResponses) {
              return true;
            }

            return participantSection.isForCurrentUser;
          }
        );

        const participationToDisplay = this.filterToCanAnswer(
          filteredParticipation
        );

        const canCurrentUserAnswer =
          this.filterToCurrentUser(participationToDisplay).length > 0;

        return {
          canParticipate: item.can_participate,
          canCurrentUserAnswer,
          participation: filteredParticipation,
          participationToDisplay,
          summary: this.getParticipantSummary(participation),
          participant_sections: item.participant_sections,
          section: item.section,
        };
      });
    },
  },

  mounted() {
    this.checkSingleSectionViewOnly();
  },

  methods: {
    /**
     * Check if row is for current user and the role matched the tab role
     *
     * @param {Object} row
     * @return {Boolean}
     */
    forCurrentUserInRole(row) {
      return (
        row.isForCurrentUser &&
        row.relationship_id === this.aboutRole.toString()
      );
    },

    /**
     * Get the localized status text for a particular user activity.
     *
     * @param status {String}
     * @returns {string}
     */
    getStatusText(status) {
      switch (status) {
        case 'NOT_STARTED':
          return this.$str('user_activities_status_not_started', 'mod_perform');
        case 'IN_PROGRESS':
          return this.$str('user_activities_status_in_progress', 'mod_perform');
        case 'COMPLETE':
          return this.$str('user_activities_status_complete', 'mod_perform');
        case 'NOT_SUBMITTED':
          return this.$str(
            'user_activities_status_not_submitted',
            'mod_perform'
          );
        default:
          return '';
      }
    },

    /**
     * Get "view" url for a specific user activity section based on current role.
     *
     * @param subjectInstance {{Object}}
     * @returns {string}
     */
    getViewActivitySectionUrl(subjectSection) {
      const participantSection = subjectSection.participation.find(
        item =>
          item.isForCurrentUser &&
          item.relationship_id === this.aboutRole.toString()
      );

      if (participantSection) {
        return this.$url(this.viewUrl, {
          participant_section_id: participantSection.id,
        });
      }
      return null;
    },

    /**
     * Filter section participation to only ones that belong to the logged in user.
     *
     * @param {Object[]} participation
     * @return {Object[]}
     */
    filterToCurrentUser(participation) {
      return participation.filter(ps => ps.isForCurrentUser);
    },

    /**
     * Filter section participation to only ones that can answer.
     *
     * @param {Object[]} participation
     * @return {Object[]}
     */
    filterToCanAnswer(participation) {
      return participation.filter(ps => ps.canAnswer);
    },

    getParticipantSummary(participation) {
      const respondents = this.filterToCanAnswer(participation);
      const totalRespondents = respondents.length;
      const totalCompleted = respondents.filter(
        item => item.progressStatus === 'COMPLETE'
      ).length;

      return {
        totalRespondents,
        totalCompleted,
      };
    },

    /**
     * Let parent component know when we find out that we only have one section and it is view-only for the
     * current user.
     */
    checkSingleSectionViewOnly() {
      if (
        this.subjectSectionsSubset.length === 1 &&
        !this.subjectSectionsSubset[0].canCurrentUserAnswer
      ) {
        this.$emit('single-section-view-only', this.activityId);
      }
    },
  },
};
</script>

<lang-strings>
  {
    "mod_perform": [
      "user_activities_closed",
      "user_activities_section_view_only",
      "user_activities_status_complete",
      "user_activities_status_header_relationship",
      "user_activities_status_header_section_progress",
      "user_activities_status_in_progress",
      "user_activities_status_not_started",
      "user_activities_status_not_submitted",
      "user_activities_subject_header",
      "user_activities_total_completed",
      "user_activities_total_respondents",
      "user_activities_you"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-performUserActivityListSections {
  & > * + * {
    margin-top: var(--gap-12);
  }
}

.tui-performUserActivityListSection {
  max-width: 800px;

  & > * + * {
    margin-top: var(--gap-2);
  }

  &__header {
    margin: 0;
    @include tui-font-body();
  }

  &__selectRelationshipLink {
    text-align: left;
    @include tui-font-link();
  }
}
</style>
