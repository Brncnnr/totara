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
  @module totara_competency
-->

<template>
  <SelectTable
    :data="competencies"
    :disabled-ids="disabledIds"
    :hover-off="true"
    :loading-overlay-active="true"
    :loading-preview="loading"
    :loading-preview-rows="10"
    :no-items-text="$str('no_competencies_found', 'totara_competency')"
    :no-label-offset="true"
    :select-all-enabled="true"
    :stack-at="0"
    :value="selectedCompetencies"
    @input="$emit('update', $event)"
  >
    <template v-slot:header-row>
      <HeaderCell size="12" valign="center" />
    </template>

    <template v-slot:row="{ row }">
      <Cell size="11">
        <template v-slot:custom-loader>
          <div class="tui-competencyCopyPathwaySelectionTable__contentLoader">
            <div
              class="tui-competencyCopyPathwaySelectionTable__content-parents"
            >
              <SkeletonContent :char-length="25" :has-overlay="true" />
            </div>
            <div class="tui-competencyCopyPathwaySelectionTable__content-title">
              <SkeletonContent :char-length="30" :has-overlay="true" />
            </div>
            <div class="tui-competencyCopyPathwaySelectionTable__content-paths">
              <SkeletonContent :char-length="25" :has-overlay="true" />
            </div>
          </div>
        </template>
        <template v-slot:default>
          <div class="tui-competencyCopyPathwaySelectionTable__content">
            <!-- Competency parents -->
            <div
              v-if="row.parents && row.parents.length"
              class="tui-competencyCopyPathwaySelectionTable__content-parents"
            >
              <span class="sr-only">
                {{ $str('a11y_competency_parents', 'totara_competency') }}
              </span>
              {{ concatParentsString(row.parents) }}
            </div>

            <!-- Competency title -->
            <h6 class="tui-competencyCopyPathwaySelectionTable__content-title">
              {{ row.name }}
            </h6>

            <!-- Competency existing achievement paths -->
            <div
              v-if="row.achievement_path && row.achievement_path.length"
              class="tui-competencyCopyPathwaySelectionTable__content-paths"
            >
              <span
                class="tui-competencyCopyPathwaySelectionTable__content-pathsLabel"
              >
                {{ $str('achievement_paths_label', 'totara_competency') }}
              </span>

              {{ concatAchievementPathsString(row.achievement_path) }}
            </div>
          </div>
        </template>
      </Cell>
      <Cell align="end" size="1" valign="center">
        <template v-slot:custom-loader />
        <template v-slot:default>
          <div
            v-if="!reviewingSelection && row.has_children"
            class="tui-competencyCopyPathwaySelectionTable__actions"
          >
            <ButtonIcon
              :aria-label="
                $str(
                  'view_child_competencies_of_x',
                  'totara_competency',
                  row.name
                )
              "
              :styleclass="{ transparent: true }"
              :title="$str('view_child_competencies', 'totara_competency')"
              @click="$emit('change-competency-level', row.id)"
            >
              <ForwardArrow />
            </ButtonIcon>
          </div>
        </template>
      </Cell>
    </template>
  </SelectTable>
</template>

<script>
import ButtonIcon from 'tui/components/buttons/ButtonIcon';
import Cell from 'tui/components/datatable/Cell';
import ForwardArrow from 'tui/components/icons/ForwardArrow';
import HeaderCell from 'tui/components/datatable/HeaderCell';
import SelectTable from 'tui/components/datatable/SelectTable';
import SkeletonContent from 'tui/components/loading/SkeletonContent';

export default {
  components: {
    ButtonIcon,
    Cell,
    ForwardArrow,
    HeaderCell,
    SelectTable,
    SkeletonContent,
  },

  props: {
    // List of available competencies
    competencies: {
      type: Array,
    },
    loading: {
      type: Boolean,
    },
    // On the basket review UI
    reviewingSelection: {
      type: Boolean,
    },
    // List of selected competency ID's
    selectedCompetencies: {
      type: Array,
      required: true,
    },
    // The id of the competency we're copying from
    sourceCompetencyId: {
      type: Number,
    },
  },

  computed: {
    // Currently we only need to disable the source competency in the SelectTable
    disabledIds() {
      return this.sourceCompetencyId
        ? [this.sourceCompetencyId.toString()]
        : [];
    },
  },

  methods: {
    /**
     * Return a concatenated string of the competencies achievement paths
     *
     * @param {Array} paths [{ name: 'a' }, { name: 'b' }, { name: 'c' }]
     * @return {String}
     */
    concatAchievementPathsString(paths) {
      let keys = [];

      return paths
        .filter(path => {
          const type = path.type;
          const duplicate = keys.includes(type);
          keys.push(type);
          return !duplicate;
        })
        .map(path => path.name)
        .join(
          this.$str(
            'competency_achievement_path_list_separator',
            'totara_competency'
          )
        );
    },

    /**
     * Return a concatenated string of the competencies parents
     *
     * @param {Array} parents [{ name: 'a' }, { name: 'b' }, { name: 'c' }]
     * @return {String}
     */
    concatParentsString(parents) {
      return parents
        .map(parent => parent.name)
        .join(
          this.$str('competency_parent_list_separator', 'totara_competency')
        );
    },
  },
};
</script>

<lang-strings>
  {
    "totara_competency": [
      "a11y_competency_parents",
      "achievement_paths_label",
      "competency_achievement_path_list_separator",
      "competency_parent_list_separator",
      "no_competencies_found",
      "view_child_competencies",
      "view_child_competencies_of_x"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-competencyCopyPathwaySelectionTable {
  &__content {
    padding-right: var(--gap-2);

    &-parents {
      @include tui-font-body-x-small();
      color: var(--color-neutral-6);
    }

    &-paths {
      padding-top: var(--gap-1);
      @include tui-font-body-small();
    }

    &-pathsLabel {
      color: var(--color-neutral-6);
    }

    &-title {
      @include tui-font-heading-label();
      margin: 0;
    }

    & > * + * {
      margin-top: var(--gap-1);
    }
  }

  &__contentLoader {
    display: flex;
    flex-direction: column;
    & > * + * {
      margin-top: var(--gap-1);
    }
  }
}
</style>
