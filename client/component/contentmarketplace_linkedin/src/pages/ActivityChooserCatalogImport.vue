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

  @author Arshad Anwer <arshad.anwer@totaralearning.com>
  @package contentmarketplace_linkedin
-->

<template>
  <div>
    <Layout
      :loading="isLoading"
      :selection-title="
        $str('activity_chooser_catalog_title', 'contentmarketplace_linkedin')
      "
      :selection-sub-title="
        $str(
          'activity_chooser_catalog_sub_title',
          'contentmarketplace_linkedin'
        )
      "
    >
      <!-- Primary filter for selecting language -->
      <template v-slot:primary-filter>
        <LinkedInPrimaryFilter
          v-if="languageFilterOptions.length > 1"
          :options="languageFilterOptions"
          :selected="selectedLanguage"
          @filter-change="setPrimaryFilter"
        />
      </template>

      <!-- Filter panel -->
      <template v-slot:filters="{ contentId }">
        <LinkedInFilters
          v-model="selectedFilters"
          :content-id="contentId"
          :filters="filters"
          :open-nodes="openNodes"
        />
      </template>

      <!-- Table count and active filters used -->
      <template v-slot:summary-count>
        <CountAndFilters
          :filters="learningObjects.selectedFilters"
          :count="learningObjects.total"
        />
      </template>

      <!-- Sort order filter for table -->
      <template v-slot:summary-sort>
        <SortFilter
          :options="sortFilterOptions"
          :sort-by="selectedSortOrderFilter"
          @filter-change="setSortOrderFilter"
        />
      </template>

      <!-- Table of available learning items -->
      <template v-slot:select-table>
        <ImportTable
          ref="import-table"
          :items="isLoading ? placeholderItems : learningObjects.items"
          @select="setSelectedItem"
        >
          <template v-slot:row="{ row }">
            <!-- Learning item -->
            <LinkedInLearningItem
              :data="row"
              :loading="isLoading"
              :logo="logo"
            />
          </template>
        </ImportTable>

        <SelectionPaging
          :current-page="paginationPage"
          :items-per-page="paginationLimit"
          :total-items="learningObjects.total"
          @items-per-page-change="setItemsPerPage"
          @page-change="setPaginationPage"
        />
      </template>
    </Layout>
    <ConfirmationModal
      :open="modalOpen"
      :title="$str('add_activity_title', 'contentmarketplace_linkedin')"
      :confirm-button-text="$str('add_activity', 'contentmarketplace_linkedin')"
      :loading="creatingContentLoading"
      :close-button="true"
      @confirm="createActivity"
      @cancel="cancelActivity"
    >
      <div v-html="getModalContent" />
    </ConfirmationModal>
  </div>
</template>

<script>
import CountAndFilters from 'totara_contentmarketplace/components/count/ImportCountAndFilters';
import ConfirmationModal from 'tui/components/modal/ConfirmationModal';
import Layout from 'totara_contentmarketplace/pages/CatalogImportLayout';
import LinkedInFilters from 'contentmarketplace_linkedin/components/filters/ImportSideFilters';
import LinkedInLearningItem from 'contentmarketplace_linkedin/components/learning_item/ImportLearningItem';
import LinkedInPrimaryFilter from 'contentmarketplace_linkedin/components/filters/ImportPrimaryFilter';
import SelectionPaging from 'totara_contentmarketplace/components/paging/ImportSelectionPaging';
import ImportTable from 'totara_contentmarketplace/components/tables/ImportTable';
import SortFilter from 'totara_contentmarketplace/components/filters/ImportSortFilter';
import { notify } from 'tui/notifications';
import { parseQueryString, url } from 'tui/util';

// GraphQL
import courseCategoriesQuery from 'contentmarketplace_linkedin/graphql/catalog_import_course_categories';
import createCourseMutation from 'contentmarketplace_linkedin/graphql/catalog_import_add_activity';
import filterOptionsQuery from 'contentmarketplace_linkedin/graphql/catalog_import_learning_objects_filter_options';
import learningObjectsQuery from 'contentmarketplace_linkedin/graphql/catalog_import_learning_objects';
import localesQuery from 'contentmarketplace_linkedin/graphql/catalog_import_available_locales';

const LANGUAGE_ENGLISH = 'en';

export default {
  components: {
    CountAndFilters,
    ConfirmationModal,
    Layout,
    LinkedInLearningItem,
    LinkedInFilters,
    LinkedInPrimaryFilter,
    SelectionPaging,
    ImportTable,
    SortFilter,
  },

  props: {
    canManageContent: {
      type: Boolean,
      required: true,
    },
    logo: {
      type: Object,
      required: true,
    },
    course: {
      type: Object,
      required: true,
    },
  },

  data() {
    return {
      modalOpen: false,
      categoryOptions: [],
      filters: {
        subjects: [],
        time_to_complete: [],
        in_catalog: [],
      },
      // Available language options for primary filter.
      // This will be populated via the graphql call.
      languageFilterOptions: [],
      // Available learning content populated by learningObjectsQuery
      learningObjects: {
        items: [],
        selectedFilters: [],
        total: 0,
      },
      // URL key of marketplace
      marketplace: 'linkedin',
      // Open Filter tree nodes
      openNodes: {
        subjects: ['subjects'],
        time_to_complete: [],
        in_catalog: [],
      },
      // items per page limit
      paginationLimit: 20,
      // Selection view pagination page
      paginationPage: 1,
      // Selected category value
      selectedCategory: {
        id: null,
        label: null,
      },
      categoryId: null,
      sectionId: null,
      selectedFilters: {
        search: '',
        subjects: [],
        time_to_complete: [],
        in_catalog: [],
      },
      selectedItem: null,
      // Selected language value from primary filter
      selectedLanguage: LANGUAGE_ENGLISH,
      // Selected sort filter value
      selectedSortOrderFilter: 'LATEST',
      // Setting initial filters
      settingInitFilters: true,
      // Available Sort filter options
      sortFilterOptions: [
        {
          label: this.$str('sort_filter_latest', 'contentmarketplace_linkedin'),
          id: 'LATEST',
        },
        {
          label: this.$str(
            'sort_filter_alphabetical',
            'contentmarketplace_linkedin'
          ),
          id: 'ALPHABETICAL',
        },
      ],
      creatingContentLoading: false,
    };
  },

  apollo: {
    categoryOptions: {
      query: courseCategoriesQuery,
      update({ categoryOptions }) {
        if (this.categoryId) {
          const category = categoryOptions.find(
            categoryOption => categoryOption.id == this.categoryId
          );
          this.selectedCategory = { id: category.id, label: category.label };
        }

        return categoryOptions;
      },
    },

    learningObjects: {
      query: learningObjectsQuery,
      skip() {
        return this.settingInitFilters;
      },
      variables() {
        return {
          input: {
            filters: {
              ids: [],
              language: this.selectedLanguage,
              search: this.trimmedSearch,
              subjects: this.selectedFilters.subjects,
              time_to_complete: this.selectedFilters.time_to_complete,
              in_catalog: this.selectedFilters.in_catalog,
            },
            pagination: {
              limit: this.paginationLimit,
              page: this.paginationPage,
            },
            sort_by: this.selectedSortOrderFilter,
          },
        };
      },
      update({ result: data }) {
        let selectedFilters = data.selected_filters.slice();
        if (this.trimmedSearch.length > 0) {
          selectedFilters.unshift(this.trimmedSearch);
        }

        return {
          items: data.items,
          next_cursor: data.next_cursor,
          total: data.total,
          selectedFilters,
        };
      },
    },

    filters: {
      query: filterOptionsQuery,
      fetchPolicy: 'network-only',
      variables() {
        return {
          input: {
            language: this.selectedLanguage,
          },
        };
      },
      skip() {
        // Skip this query, when the language filter options is not populated yet.
        return this.languageFilterOptions.length === 0;
      },
      update({ result: data }) {
        return data;
      },
    },

    languageFilterOptions: {
      query: localesQuery,
      update({ locales }) {
        return locales;
      },
    },
  },

  computed: {
    /**
     * Are we currently mutating or querying data via graphQL?
     *
     * @return {Boolean}
     */
    isLoading() {
      return this.$apollo.loading;
    },

    /**
     * Number of placeholder items for loading display
     *
     * @return {Array}
     */
    placeholderItems() {
      return Array.from({ length: this.paginationLimit }, () => ({}));
    },

    /**
     * Get the search string with whitespace removed.
     *
     * @return {String}
     */
    trimmedSearch() {
      return this.selectedFilters.search.trim();
    },

    /**
     * Get confirmation modal content
     *
     * @return {String}
     */
    getModalContent() {
      if (!this.selectedItem) {
        return;
      }

      return this.$str(
        'activity_modal_content',
        'contentmarketplace_linkedin',
        {
          activityname: this.selectedItem.name,
          coursename: this.course.fullname,
        }
      );
    },
  },

  watch: {
    selectedFilters: {
      deep: true,
      handler() {
        this.setPageFilterParams();
        this.setPaginationPage(1);
      },
    },
  },

  mounted() {
    // Populate active filters based on URL params
    let urlParams = parseQueryString(window.location.search);

    Object.keys(urlParams).forEach(key => {
      // Only populate filters with default values
      if (typeof this.selectedFilters[key] !== 'undefined') {
        this.selectedFilters[key] = urlParams[key];
      }

      if (key === 'sortby') {
        this.selectedSortOrderFilter = urlParams[key];
      }

      if (key === 'language') {
        // The validation of language is done at the back-end, prior to the point
        // where this page is rendered.
        this.selectedLanguage = urlParams[key];
      }

      if (key === 'category') {
        this.categoryId = urlParams[key];
      }

      if (key === 'section_id') {
        this.sectionId = urlParams[key];
      }
    });

    this.settingInitFilters = false;
  },

  methods: {
    /**
     * Create activity
     */
    async createActivity() {
      try {
        this.creatingContentLoading = true;
        const {
          data: { payload },
        } = await this.$apollo.mutate({
          mutation: createCourseMutation,
          variables: {
            learning_object_id: this.selectedItem.id,
            section_id: this.sectionId,
          },
        });
        if (payload.redirect_url) {
          window.location.href = payload.redirect_url;
          return;
        }

        if (payload.message.length > 0) {
          await notify({
            message: payload.message,
            type: payload.success ? 'success' : 'error',
          });
        }
        this.creatingContentLoading = false;
      } catch (e) {
        await notify({
          message: this.$str(
            'activity_chooser_content_creation_error',
            'contentmarketplace_linkedin'
          ),
          type: 'error',
        });
        this.creatingContentLoading = false;
      }
    },

    cancelActivity() {
      this.modalOpen = false;
    },

    /**
     * Reset active side panel filters
     *
     */
    resetPanelFilters() {
      this.selectedFilters = {
        search: '',
        subjects: [],
        time_to_complete: [],
        in_catalog: [],
      };
    },

    /**
     * Update number of items displayed in paginated selection results
     *
     * @param {Number} limit
     */
    setItemsPerPage(limit) {
      this.paginationLimit = limit;
    },

    /**
     * Set the page filters params in the URL
     *
     */
    setPageFilterParams() {
      let urlData = {
        marketplace: this.marketplace,
      };

      let values = this.selectedFilters;

      // Iterate through all filter types
      Object.keys(values).forEach(key => {
        let filter = values[key];

        if (key === 'search' && filter) {
          urlData.search = filter;
        }

        // Only include filter types with an active filter
        if (filter instanceof Array && filter.length) {
          urlData[key] = filter;
        }
      });

      urlData.section_id = this.sectionId;

      if (this.selectedSortOrderFilter) {
        urlData.sortby = this.selectedSortOrderFilter;
      }

      if (this.selectedLanguage !== LANGUAGE_ENGLISH) {
        urlData.language = this.selectedLanguage;
      }

      if (this.categoryId && this.selectedCategory.id) {
        urlData.categoryid = this.selectedCategory.id;
      }

      const pageUrl = url(window.location.pathname, urlData);
      window.history.pushState(null, null, pageUrl);
    },

    /**
     * Update current paginated page of selection results
     *
     * @param {Number} page
     */
    setPaginationPage(page) {
      if (this.$refs['import-table']) {
        this.$refs['import-table'].$el.scrollIntoView();
      }

      this.paginationPage = page;
    },

    /**
     * Set the language primary filter value
     *
     * @param {String} value
     */
    setPrimaryFilter(value) {
      this.resetPanelFilters();
      this.selectedLanguage = value;
    },

    /**
     * Set selected course item (chosen from the table)
     * and display confirmation modal
     *
     * @param {Object} items
     */
    setSelectedItem(item) {
      this.selectedItem = item;
      this.modalOpen = true;
    },

    /**
     * Set the sort order filter value
     *
     * @param {String} value
     */
    setSortOrderFilter(value) {
      this.selectedSortOrderFilter = value;
      this.setPaginationPage(1);
      this.setPageFilterParams();
    },
  },
};
</script>

<lang-strings>
  {
    "contentmarketplace_linkedin": [
      "add_activity_title",
      "add_activity",
      "activity_chooser_content_creation_error",
      "activity_chooser_catalog_title",
      "activity_chooser_catalog_sub_title",
      "activity_modal_content",
      "sort_filter_alphabetical",
      "sort_filter_latest"
    ],
    "totara_contentmarketplace": [
      "explore_content_marketplace"
    ]
  }
</lang-strings>
