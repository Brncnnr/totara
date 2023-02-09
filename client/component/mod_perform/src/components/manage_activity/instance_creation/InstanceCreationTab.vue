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

  @author Rodney Cruden-Powell <rodney.cruden-powell@totaralearning.com>
  @module mod_perform
-->

<template>
  <div class="tui-performManageActivityInstanceCreationForm">
    <Schedule
      v-if="track"
      :track="track"
      :dynamic-date-sources="dynamicDateSources"
      :default-fixed-date="defaultFixedDateSetting"
      :activity="activity"
      :activity-state="activityState"
      @unsaved-changes="hasUnsavedChanges"
      @refetch-core-query="$emit('refetch-core-query')"
    />
  </div>
</template>

<script>
import Schedule from 'mod_perform/components/manage_activity/assignment/Schedule';

//GraphQL
import TrackSettingsQuery from 'mod_perform/graphql/default_track_settings';

export default {
  components: {
    Schedule,
  },

  props: {
    activity: {
      type: Object,
      required: true,
    },
    activityState: {
      type: String,
      required: true,
    },
    activityHasUnsavedChanges: Boolean,
  },

  data() {
    return {
      defaultFixedDateSetting: null,
      dynamicDateSources: [],
      track: null,
      trackSettings: null,
    };
  },

  watch: {
    /**
     * Used so we can get track, date resolver options, and default fixed date setting in one query.
     */
    trackSettings(newValue) {
      this.track = newValue.track;
      this.defaultFixedDateSetting = newValue.defaultFixedDateSetting;

      if (
        this.track.schedule_dynamic_source &&
        this.track.schedule_dynamic_source.is_available === false
      ) {
        this.dynamicDateSources = this.addedDeletedDateSourceToList(
          newValue.dynamicDateSources
        );
      } else {
        this.dynamicDateSources = newValue.dynamicDateSources;
      }
    },
  },

  mounted() {
    // Confirm navigation away if user is currently editing.
    window.addEventListener('beforeunload', this.unloadHandler);
  },

  beforeDestroy() {
    // Modal will no longer exist so remove the navigation warning.
    window.removeEventListener('beforeunload', this.unloadHandler);
  },

  apollo: {
    trackSettings: {
      query: TrackSettingsQuery,
      fetchPolicy: 'network-only', // Always refetch data on tab change
      variables() {
        return {
          activity_id: this.activity.id,
        };
      },
      update: data => {
        return {
          track: data.mod_perform_default_track,
          dynamicDateSources: data.mod_perform_available_dynamic_date_sources,
          defaultFixedDateSetting: data.mod_perform_default_fixed_date_setting,
        };
      },
    },
  },

  methods: {
    /**
     * Add the currently selected date resolver to the front of the selections,
     * with a modified "deleted" label.
     * @param {Object} dynamicDateSources
     */
    addedDeletedDateSourceToList(dynamicDateSources) {
      const deletedDisplayName = this.$str(
        'deleted_dynamic_source_label',
        'mod_perform',
        this.track.schedule_dynamic_source.display_name
      );

      const deletedOption = Object.assign(
        {},
        this.track.schedule_dynamic_source,
        { display_name: deletedDisplayName }
      );

      const options = [deletedOption];
      options.push.apply(options, dynamicDateSources);

      return options;
    },

    /**
     *  @param {Boolean} value
     */
    hasUnsavedChanges(value) {
      this.$emit('unsaved-changes', value);
    },

    /**
     * Displays a warning message if the user tries to navigate away without saving.
     * @param {Event} e
     * @returns {String|void}
     */
    unloadHandler(e) {
      if (!this.activityHasUnsavedChanges) {
        return;
      }

      // For older browsers that still show custom message.
      const discardUnsavedChanges = this.$str(
        'unsaved_changes_warning',
        'mod_perform'
      );
      e.preventDefault();
      e.returnValue = discardUnsavedChanges;
      return discardUnsavedChanges;
    },
  },
};
</script>

<lang-strings>
  {
    "mod_perform" : [
      "deleted_dynamic_source_label",
      "unsaved_changes_warning"
    ]
  }
</lang-strings>
