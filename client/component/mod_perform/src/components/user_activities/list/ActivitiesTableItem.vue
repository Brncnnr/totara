<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2022 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD's customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totaralearning.com] for more information.

  @author Kevin Hottinger <kevin.hottinger@totaralearning.com>
  @module mod_perform
-->

<template>
  <Responsive
    :breakpoints="[
      { name: 'small', boundaries: [0, 767] },
      { name: 'medium', boundaries: [765, 995] },
      { name: 'large', boundaries: [993, 1399] },
      { name: 'xLarge', boundaries: [1397, 1681] },
    ]"
    @responsive-resize="resize"
  >
    <Grid
      class="tui-performUserActivityListTableItem"
      :class="{
        'tui-performUserActivityListTableItem--notStacked': !stacked,
      }"
      :max-units="16"
      :stack-at="765"
    >
      <GridItem
        :units="gridUnitsLeft"
        class="tui-performUserActivityListTableItem__left"
      >
        <!-- Desktop avatar -->
        <div
          v-if="isAboutOthers && !stacked"
          class="tui-performUserActivityListTableItem__left-avatar"
        >
          <div
            v-if="loading"
            class="tui-performUserActivityListTableItem__left-avatarLoader"
          >
            <SkeletonContent :char-length="10" :has-overlay="true" />
          </div>

          <Avatar
            v-else
            :alt="row.subject.subject_user.profileimagealt"
            :aria-hidden="true"
            :src="row.subject.subject_user.profileimageurlsmall"
            size="xsmall"
          />
        </div>

        <div class="tui-performUserActivityListTableItem__left-content">
          <!-- Mobile content -->
          <div
            v-if="stacked"
            class="tui-performUserActivityListTableItem__stackedBar"
          >
            <div class="tui-performUserActivityListTableItem__stackedBar-user">
              <SkeletonContent
                v-if="loading && isAboutOthers"
                :char-length="10"
                :has-overlay="true"
              />

              <template v-else-if="isAboutOthers">
                <Avatar
                  :alt="row.subject.subject_user.profileimagealt"
                  :aria-hidden="true"
                  :src="row.subject.subject_user.profileimageurlsmall"
                  size="xsmall"
                />

                <span>
                  {{ row.subject.subject_user.fullname }}
                </span>
              </template>
            </div>

            <!-- Mobile actions menu -->
            <div
              v-if="!loading"
              class="tui-performUserActivityListTableItem__stackedBar-actions"
            >
              <Dropdown position="bottom-right">
                <template v-slot:trigger="{ toggle, isOpen }">
                  <MoreButton
                    :aria-expanded="isOpen.toString()"
                    :aria-label="
                      $str('actions_for_item', 'mod_perform', activityTitle)
                    "
                    @click="toggle"
                  />
                </template>

                <DropdownItem
                  :aria-label="$str('print_activity', 'mod_perform')"
                  :href="getPrintActivityLink(row)"
                >
                  {{ $str('print_activity', 'mod_perform') }}
                </DropdownItem>
              </Dropdown>
            </div>
          </div>

          <div class="tui-performUserActivityListTableItem__overview">
            <SkeletonContent
              v-if="loading"
              :char-length="15"
              :has-overlay="true"
            />

            <template v-else>
              <!-- Subject -->
              <div
                v-if="isAboutOthers && !stacked"
                class="tui-performUserActivityListTableItem__overview-subject"
              >
                {{ row.subject.subject_user.fullname }}
              </div>

              <!-- Activity type -->
              <div class="tui-performUserActivityListTableItem__overview-type">
                <span class="sr-only">
                  {{ $str('a11y_activity_type_label', 'mod_perform') }}
                </span>
                {{ row.subject.activity.type.display_name }}
              </div>

              <!-- Creation date -->
              <div
                v-if="row.subject.created_at"
                class="tui-performUserActivityListTableItem__overview-created"
              >
                <span class="sr-only">
                  {{ $str('a11y_activity_created_at_label', 'mod_perform') }}
                </span>
                {{ row.subject.created_at }}
              </div>
            </template>
          </div>

          <!-- Activity name -->
          <h4 class="tui-performUserActivityListTableItem__title">
            <SkeletonContent
              v-if="loading"
              :char-length="20"
              :has-overlay="true"
            />

            <a v-else :href="activityUrl">
              {{ activityTitle }}
            </a>
          </h4>

          <div class="tui-performUserActivityListTableItem__details">
            <SkeletonContent
              v-if="loading"
              :char-length="15"
              :has-overlay="true"
            />

            <template v-else>
              <!-- Overall progress -->
              <div
                class="tui-performUserActivityListTableItem__details-overallProgress"
              >
                <span
                  :class="{
                    'tui-performUserActivityListTableItem__details-overallProgress--overdue': activityOverdue,
                  }"
                >
                  {{ activityOverallProgress }}
                </span>
              </div>

              <!-- Due date -->
              <div
                v-if="row.subject.due_on"
                class="tui-performUserActivityListTableItem__details-dueDate"
              >
                <span
                  :class="{
                    'tui-performUserActivityListTableItem__details-dueDate--overdue': activityOverdue,
                  }"
                >
                  {{
                    $str(
                      'activity_due_on',
                      'mod_perform',
                      row.subject.due_on.due_date
                    )
                  }}
                </span>
              </div>

              <!-- Job assignment -->
              <div
                v-if="activityJobAssignment"
                class="tui-performUserActivityListTableItem__details-jobAssignment"
              >
                <span class="sr-only">
                  {{
                    $str('a11y_activity_job_assignment_label', 'mod_perform')
                  }}
                </span>

                <span
                  class="tui-performUserActivityListTableItem__details-jobAssignmentText"
                >
                  {{ activityJobAssignment }}
                </span>
              </div>
            </template>
          </div>
        </div>
      </GridItem>

      <!-- Right aligned content -->
      <GridItem
        class="tui-performUserActivityListTableItem__right"
        :units="gridUnitsRight"
      >
        <!-- Actions menu -->
        <div
          v-if="!loading && !stacked"
          class="tui-performUserActivityListTableItem__actions"
        >
          <Dropdown position="bottom-right">
            <template v-slot:trigger="{ toggle, isOpen }">
              <MoreButton
                :aria-expanded="isOpen.toString()"
                :aria-label="
                  $str('actions_for_item', 'mod_perform', activityTitle)
                "
                @click="toggle"
              />
            </template>

            <DropdownItem
              :aria-label="$str('print_activity', 'mod_perform')"
              :href="getPrintActivityLink(row)"
            >
              {{ $str('print_activity', 'mod_perform') }}
            </DropdownItem>
          </Dropdown>
        </div>

        <!-- Your progress -->
        <div class="tui-performUserActivityListTableItem__progress">
          <SkeletonContent
            v-if="loading"
            :char-length="15"
            :has-overlay="true"
          />

          <template v-else>
            <span :class="{ 'sr-only': activityParticipantViewOnly }">
              {{ $str('activity_your_progress_label', 'mod_perform') }}
            </span>

            <OverdueIcon
              v-if="activityParticipantOverdue"
              :aria-hidden="true"
              class="tui-performUserActivityListTableItem__progress-overdue"
              :size="300"
            />

            <SuccessIcon
              v-else-if="activityParticipantComplete"
              :aria-hidden="true"
              class="tui-performUserActivityListTableItem__progress-complete"
              :size="300"
            />

            <ViewOnlyIcon
              v-else-if="activityParticipantViewOnly"
              :aria-hidden="true"
              class="tui-performUserActivityListTableItem__progress-viewOnly"
              :size="300"
            />

            <span
              class="tui-performUserActivityListTableItem__progress-status"
              :class="{
                'tui-performUserActivityListTableItem__progress-status--overdue': activityParticipantOverdue,
              }"
            >
              {{ activityYourProgress }}
            </span>
          </template>
        </div>

        <!-- Section details -->
        <div class="tui-performUserActivityListTableItem__sectionDetails">
          <SkeletonContent
            v-if="loading"
            :char-length="15"
            :has-overlay="true"
          />

          <Button
            v-else
            :styleclass="{ small: true, transparent: true }"
            :text="$str('view_details', 'mod_perform')"
            @click="$emit('show-sections-details', row)"
          />
        </div>
      </GridItem>
    </Grid>
  </Responsive>
</template>

<script>
import Avatar from 'tui/components/avatar/Avatar';
import Button from 'tui/components/buttons/Button';
import Dropdown from 'tui/components/dropdown/Dropdown';
import DropdownItem from 'tui/components/dropdown/DropdownItem';
import Grid from 'tui/components/grid/Grid';
import GridItem from 'tui/components/grid/GridItem';
import MoreButton from 'tui/components/buttons/MoreIcon';
import OverdueIcon from 'tui/components/icons/Overdue';
import Responsive from 'tui/components/responsive/Responsive';
import SkeletonContent from 'tui/components/loading/SkeletonContent';
import SuccessIcon from 'tui/components/icons/SuccessSolid';
import ViewOnlyIcon from 'tui/components/icons/ViewOnlyAccess';

//Util
import {
  getFirstSectionToParticipate,
  getYourProgressStatusValue,
  isRoleInstanceOverdue,
} from 'mod_perform/activities_util';

export default {
  components: {
    Avatar,
    Button,
    Dropdown,
    DropdownItem,
    Grid,
    GridItem,
    MoreButton,
    OverdueIcon,
    Responsive,
    SkeletonContent,
    SuccessIcon,
    ViewOnlyIcon,
  },

  props: {
    aboutRole: {
      required: true,
      type: Number,
    },
    isAboutOthers: {
      type: Boolean,
    },
    loading: {
      type: Boolean,
    },
    printUrl: {
      required: true,
      type: String,
    },
    row: {
      required: true,
      type: Object,
    },
    viewUrl: {
      required: true,
      type: String,
    },
  },

  data() {
    return {
      boundaryDefaults: {
        small: {
          gridUnitsLeft: 16,
          gridUnitsRight: 16,
        },
        medium: {
          gridUnitsLeft: 10,
          gridUnitsRight: 6,
        },
        large: {
          gridUnitsLeft: 11,
          gridUnitsRight: 5,
        },
        xLarge: {
          gridUnitsLeft: 12,
          gridUnitsRight: 4,
        },
      },
      currentBoundaryName: null,
    };
  },

  computed: {
    /**
     * Get job assignment for this activity, if multiple job assignments it is null
     *
     * @return {string|null}
     */
    activityJobAssignment() {
      const jobAssignment = this.row.subject.job_assignment;

      if (!jobAssignment) {
        return;
      }

      let fullname = jobAssignment.fullname
        ? jobAssignment.fullname.trim()
        : null;

      // Fullname isn't a required field when creating a job assignment
      return fullname && fullname.length > 0
        ? fullname
        : this.$str(
            'unnamed_job_assignment',
            'mod_perform',
            jobAssignment.idnumber
          );
    },

    /**
     * Is the overall activity progress overdue
     *
     * @return {boolean}
     */
    activityOverdue() {
      return this.row.subject.due_on && this.row.subject.due_on.is_overdue;
    },

    /**
     * Is the participant instance for current role complete.
     *
     * @return {Boolean}
     */
    activityParticipantComplete() {
      const status = getYourProgressStatusValue(
        this.row.subject.participant_instances,
        this.aboutRole
      );

      return status === 'COMPLETE';
    },

    /**
     * Is the participant instance for current role overdue.
     *
     * @return {Boolean}
     */
    activityParticipantOverdue() {
      return isRoleInstanceOverdue(
        this.row.subject.participant_instances,
        this.aboutRole
      );
    },

    /**
     * Is the participant instance for current role overdue.
     *
     * @return {Boolean}
     */
    activityParticipantViewOnly() {
      const status = getYourProgressStatusValue(
        this.row.subject.participant_instances,
        this.aboutRole
      );

      return status === 'PROGRESS_NOT_APPLICABLE';
    },

    /**
     * Returns the activity title generated from the row subject instance.
     *
     * @return {string}
     */
    activityTitle() {
      return this.row.subject.activity.name.trim();
    },

    /**
     * Get "view" url for user activity.
     *
     * @return {string}
     */
    activityUrl() {
      const participantSection = getFirstSectionToParticipate(
        this.row.sections,
        this.aboutRole
      );

      if (participantSection) {
        return this.$url(this.viewUrl, {
          participant_section_id: participantSection.id,
        });
      }
      return '';
    },

    /**
     * Get the overall progress of activity string
     *
     * @return {string}
     */
    activityOverallProgress() {
      if (this.activityOverdue) {
        return this.$str(
          'activity_overall_progress_status_overdue',
          'mod_perform'
        );
      }

      const status = this.row.subject.progress_status;

      switch (status) {
        case 'NOT_STARTED':
          return this.$str(
            'activity_overall_progress_status_not_started',
            'mod_perform'
          );
        case 'IN_PROGRESS':
          return this.$str(
            'activity_overall_progress_status_in_progress',
            'mod_perform'
          );
        case 'COMPLETE':
          return this.$str(
            'activity_overall_progress_status_complete',
            'mod_perform'
          );
        case 'PROGRESS_NOT_APPLICABLE':
          return this.$str(
            'activity_overall_progress_status_not_applicable',
            'mod_perform'
          );
        case 'NOT_SUBMITTED':
          return this.$str(
            'activity_overall_progress_status_not_submitted',
            'mod_perform'
          );
        default:
          return '';
      }
    },

    /**
     * Get the viewing participants progress for activity
     *
     * @return {string}
     */
    activityYourProgress() {
      if (this.activityParticipantOverdue) {
        return this.$str('overdue', 'mod_perform');
      }

      let status = getYourProgressStatusValue(
        this.row.subject.participant_instances,
        this.aboutRole
      );

      switch (status) {
        case 'NOT_STARTED':
          return this.$str(
            'participant_instance_status_not_started',
            'mod_perform'
          );
        case 'IN_PROGRESS':
          return this.$str(
            'participant_instance_status_in_progress',
            'mod_perform'
          );
        case 'COMPLETE':
          return this.$str(
            'participant_instance_status_complete',
            'mod_perform'
          );
        case 'PROGRESS_NOT_APPLICABLE':
          return this.$str(
            'participant_instance_status_progress_view_only',
            'mod_perform'
          );
        case 'NOT_SUBMITTED':
          return this.$str(
            'participant_instance_status_not_submitted',
            'mod_perform'
          );
        default:
          return '';
      }
    },

    /**
     * Return the number of grid units for left content
     *
     * @return {Number}
     */
    gridUnitsLeft() {
      if (!this.currentBoundaryName) {
        return;
      }

      return this.boundaryDefaults[this.currentBoundaryName].gridUnitsLeft;
    },

    /**
     * Return the number of grid units for right content
     *
     * @return {Number}
     */
    gridUnitsRight() {
      if (!this.currentBoundaryName) {
        return;
      }

      return this.boundaryDefaults[this.currentBoundaryName].gridUnitsRight;
    },

    /**
     * Return if the grid is stacked
     *
     * @return {Bool}
     */
    stacked() {
      return this.currentBoundaryName === 'small';
    },
  },

  methods: {
    /**
     * Get print-friendly page URL for activity.
     *
     * @param subjectInstance
     */
    getPrintActivityLink(subjectInstance) {
      const participantSection = getFirstSectionToParticipate(
        subjectInstance.sections,
        this.aboutRole
      );

      if (participantSection) {
        return this.$url(this.printUrl, {
          participant_section_id: participantSection.id,
        });
      }
      return '';
    },

    /**
     * Handles responsive resizing which wraps the grid layout for this table
     *
     * @param {String} boundaryName
     */
    resize(boundaryName) {
      this.currentBoundaryName = boundaryName;
    },
  },
};
</script>

<lang-strings>
  {
    "mod_perform": [
      "a11y_activity_created_at_label",
      "a11y_activity_job_assignment_label",
      "a11y_activity_type_label",
      "actions_for_item",
      "activity_due_on",
      "activity_overall_progress_status_complete",
      "activity_overall_progress_status_in_progress",
      "activity_overall_progress_status_not_applicable",
      "activity_overall_progress_status_not_started",
      "activity_overall_progress_status_not_submitted",
      "activity_overall_progress_status_overdue",
      "activity_your_progress_label",
      "overdue",
      "participant_instance_status_complete",
      "participant_instance_status_in_progress",
      "participant_instance_status_not_started",
      "participant_instance_status_not_submitted",
      "participant_instance_status_progress_view_only",
      "print_activity",
      "view_details"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-performUserActivityListTableItem {
  $block: #{&};

  &__left {
    display: flex;
    width: 100%;

    & > * + * {
      margin-left: var(--gap-2);
    }

    &-avatarLoader {
      width: var(--avatar-xsmall-width);
      height: var(--avatar-xsmall-height);
      overflow: hidden;
      border-radius: var(--avatar-border-radius);
    }

    &-content {
      width: 100%;
      overflow: hidden;

      & > * + * {
        margin-top: var(--gap-2);
      }
    }
  }

  &__right {
    & > * + * {
      margin-top: var(--gap-1);
    }
  }

  &__actions {
    display: flex;
    justify-content: flex-end;
  }

  &__details {
    margin-top: var(--gap-4);
    @include tui-font-body-small();
    color: var(--color-neutral-6);

    &-dueDate {
      &--overdue {
        color: var(--color-prompt-alert);
      }
    }

    &-overallProgress {
      &--overdue {
        color: var(--color-prompt-alert);
      }
    }

    &-jobAssignment {
      display: block;
      overflow: hidden;
    }

    &-jobAssignmentText {
      overflow: hidden;
      text-overflow: ellipsis;
    }

    & > * + * {
      margin-top: var(--gap-2);
    }
  }

  &__overview {
    display: flex;
    flex-wrap: wrap;
    @include tui-font-body-small();
    color: var(--color-neutral-6);

    & > * {
      margin-right: var(--gap-2);
    }

    &-subject {
      color: var(--color-neutral-7);
      font-weight: bold;
    }
  }

  &__progress {
    margin-top: 0;

    &-complete {
      margin: 0 1px;
      color: var(--color-prompt-success);
    }

    &-overdue {
      margin: 0 1px;
      color: var(--color-prompt-alert);
    }

    &-status {
      &--overdue {
        color: var(--color-prompt-alert);
      }
    }

    &-viewOnly {
      margin-right: 1px;
    }
  }

  &__stackedBar {
    display: flex;

    &-user {
      display: flex;
      @include tui-font-body-small();
      flex-grow: 1;
      align-items: center;
      font-weight: bold;

      & > * + * {
        margin-left: var(--gap-2);
      }
    }
  }

  &__title {
    margin: var(--gap-1) 0 0;
    @include tui-font-heading-label();
  }

  &--notStacked {
    #{$block} {
      &__details {
        display: flex;
        @include tui-separator-pipe();
        margin-top: var(--gap-2);

        & > * + * {
          margin-top: 0;
        }

        &-jobAssignmentText {
          white-space: nowrap;
        }
      }
    }
  }
}
</style>
