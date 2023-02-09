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

  @author Riana Rossouw <riana.rossouw@totaralearning.com>
  @module totara_competency
-->

<template>
  <div class="tui-competencySummaryAchievementConfiguration">
    <SectionHeader
      :edit-url="editUrl"
      :title="$str('achievement_paths', 'totara_competency')"
    >
      <template v-slot:custom-actions>
        <ButtonIcon
          :aria-label="$str('copy_to_other_competency', 'totara_competency')"
          :styleclass="{ transparentNoPadding: true }"
          @click="copyPath"
        >
          <CopyIcon />
        </ButtonIcon>
      </template>
    </SectionHeader>

    <template v-if="hasPathways">
      <div class="tui-competencySummaryAchievementConfiguration__aggregation">
        <span
          class="tui-competencySummaryAchievementConfiguration__aggregation-label"
        >
          {{ $str('overall_rating_calc', 'totara_competency') }}
        </span>

        {{ achievementConfiguration.overall_aggregation.title }}
      </div>
      <Card
        v-for="(pathGroup, pathGroupId) in pathGroups"
        :key="'pathGroup' + pathGroupId"
      >
        <div class="tui-competencySummaryAchievementConfiguration__pathGroup">
          <Grid
            v-for="(scaleValue, scaleValueId) in pathGroup.scaleValues"
            :key="'scaleValue' + scaleValueId"
            class="tui-competencySummaryAchievementConfiguration__scaleValue"
            :stack-at="700"
          >
            <GridItem :units="2">
              <h4
                class="tui-competencySummaryAchievementConfiguration__scaleValue-header"
              >
                {{ scaleValue.value }}
              </h4>
            </GridItem>

            <GridItem :units="10">
              <div v-for="(path, pathIdx) in scaleValue.paths" :key="path.id">
                <Separator
                  v-if="pathIdx"
                  class="tui-competencySummaryAchievementConfiguration__scaleValue-or"
                >
                  <OrBox />
                </Separator>
                <Criteria :path="path" />
              </div>
            </GridItem>
          </Grid>
        </div>
      </Card>
    </template>
    <div
      v-else-if="!$apollo.loading"
      class="tui-competencySummaryAchievementConfiguration__noPaths"
    >
      <WarningIcon /> {{ $str('no_paths', 'totara_competency') }}
    </div>

    <!-- Cannot copy path information modal -->
    <InformationModal
      :open="noAchievementPathsModalOpen"
      :title="$str('cannot_copy_achievement_path', 'totara_competency')"
      @close="noAchievementPathsModalOpen = false"
    >
      {{ $str('cannot_copy_achievement_path_details', 'totara_competency') }}
    </InformationModal>
  </div>
</template>

<script>
// Components
import ButtonIcon from 'tui/components/buttons/ButtonIcon';
import Card from 'tui/components/card/Card';
import CopyIcon from 'tui/components/icons/Copy';
import Criteria from 'totara_competency/components/summary/AchievementConfigurationCriteria';
import Grid from 'tui/components/grid/Grid';
import GridItem from 'tui/components/grid/GridItem';
import InformationModal from 'tui/components/modal/InformationModal';
import OrBox from 'tui/components/decor/OrBox';
import SectionHeader from 'totara_competency/components/summary/CompetencySummarySectionHeader';
import Separator from 'tui/components/decor/Separator';
import WarningIcon from 'tui/components/icons/Warning';

// Queries
import achievementConfigurationQuery from 'totara_competency/graphql/achievement_criteria';

export default {
  components: {
    ButtonIcon,
    Card,
    CopyIcon,
    Criteria,
    Grid,
    GridItem,
    InformationModal,
    OrBox,
    SectionHeader,
    Separator,
    WarningIcon,
  },

  props: {
    competencyId: {
      type: Number,
    },
    copyUrl: {
      type: String,
    },
  },

  data() {
    return {
      editUrl: '',
      noAchievementPathsModalOpen: false,
      paths: [],
    };
  },

  computed: {
    hasPathways() {
      return (
        !this.$apollo.loading &&
        this.achievementConfiguration.paths &&
        this.achievementConfiguration.paths.length > 0
      );
    },

    // Order paths by sort order
    // Group paths - multi value paths will always be in their own group
    //             - all single value paths are placed in a single group
    pathGroups() {
      let bottomGroups = [];
      let singleValueGroup = null;
      let paths = this.paths;
      let topGroups = [];

      for (let idx in paths) {
        let path = Object.assign({}, paths[idx]);
        path.multiCriteria = path.criteria_summary.length > 1;

        // multi-value paths are always placed in their own group with scale value 'Any value'
        // All single-value paths are grouped in a single group
        if (path.classification === 'MULTIVALUE') {
          if (!singleValueGroup) {
            topGroups.push({
              key: 'group-' + idx,
              scaleValues: [
                {
                  value: this.$str('any_scale_value', 'totara_competency'),
                  paths: [path],
                },
              ],
            });
          } else {
            bottomGroups.push({
              key: 'group-' + idx,
              scaleValues: [
                {
                  value: this.$str('any_scale_value', 'totara_competency'),
                  paths: [path],
                },
              ],
            });
          }
        } else {
          if (!singleValueGroup) {
            singleValueGroup = {
              key: 'group-' + idx,
              scaleValues: [],
            };
          }

          let svIdx = singleValueGroup.scaleValues.findIndex(
            item => item.value === path.scale_value
          );
          if (svIdx === -1) {
            svIdx = singleValueGroup.scaleValues.length;
            singleValueGroup.scaleValues.push({
              value: path.scale_value,
              paths: [],
            });
          }

          singleValueGroup.scaleValues[svIdx].paths.push(path);
        }
      }

      // Now merge all 3 together and return

      if (!singleValueGroup) {
        return topGroups;
      } else {
        return [].concat(topGroups, singleValueGroup, bottomGroups);
      }
    },
  },

  apollo: {
    achievementConfiguration: {
      query: achievementConfigurationQuery,
      variables() {
        return {
          competency_id: this.competencyId,
          summarized: true,
        };
      },
      update({ totara_competency_achievement_criteria: data }) {
        this.editUrl = this.$url('/totara/competency/competency_edit.php', {
          s: 'achievement_paths',
          id: this.competencyId,
        });
        this.paths = data.paths;

        return data;
      },
    },
  },

  methods: {
    /**
     * Copy achievement path button has clicked
     *
     */
    copyPath() {
      if (this.hasPathways) {
        this.redirectToCopyPath();
      } else {
        // show information modal
        this.noAchievementPathsModalOpen = true;
      }
    },

    /**
     * Redirect to the copy achievements path page for the current competency
     *
     */
    redirectToCopyPath() {
      const copyCompetencyUrl = this.$url(this.copyUrl, {
        id: this.competencyId,
      });

      window.location = copyCompetencyUrl;
    },
  },
};
</script>

<lang-strings>
{
  "totara_competency": [
    "achievement_paths",
    "cannot_copy_achievement_path",
    "cannot_copy_achievement_path_details",
    "copy_to_other_competency",
    "overall_rating_calc",
    "any_scale_value",
    "no_paths"
  ]
}
</lang-strings>

<style lang="scss">
.tui-competencySummaryAchievementConfiguration {
  & > * + * {
    margin-top: var(--gap-2);
  }

  &__aggregation {
    margin-top: var(--gap-4);
    &-label {
      @include tui-font-heavy();
      margin-right: var(--gap-2);
    }
  }

  &__noPaths {
    color: var(--color-prompt-warning);
  }

  &__pathGroup {
    flex-grow: 1;
    padding: var(--gap-4);

    & > * + * {
      &.tui-grid {
        margin-top: var(--gap-4);
        padding-top: var(--gap-4);
        border-top: var(--border-width-thin) solid var(--card-border-color);
      }
    }
  }

  &__scaleValue {
    &-header {
      margin: 0;
      @include tui-font-heading-x-small();
    }

    &-or {
      width: 50%;
      margin: 0;
    }
  }
}
</style>
