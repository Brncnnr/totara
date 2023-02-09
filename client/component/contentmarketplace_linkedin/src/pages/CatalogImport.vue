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
  @package contentmarketplace_linkedin
-->

<template>
  <Layout
    :loading="isLoading"
    :reviewing-selection="reviewingSelectedItems"
    :review-title="$str('catalog_review_title', 'contentmarketplace_linkedin')"
    :selection-title="$str('catalog_title', 'contentmarketplace_linkedin')"
  >
    <template v-if="canManageContent" v-slot:content-nav>
      <PageBackLink
        :link="$url('/totara/contentmarketplace/marketplaces.php')"
        :text="$str('manage_content_marketplaces', 'totara_contentmarketplace')"
      />
    </template>

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

    <!-- Basket for tracking selection, reviewing and creating courses -->
    <template v-slot:basket>
      <Basket
        :is-loading="isLoading"
        :current-category-id="categoryId"
        :category-options="categoryOptions"
        :selected-category="selectedCategory.id"
        :selected-items="selectedItems"
        :viewing-selected="reviewingSelectedItems"
        :creating-content="creatingContentLoading"
        @category-change="setDefaultSelectedCategory"
        @clear-selection="clearSelectedItems"
        @create-courses="createCourses"
        @reviewing-selection="switchContentView"
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
      <SelectionTable
        ref="selection-table"
        :items="isLoading ? placeholderItems : learningObjects.items"
        row-label-key="name"
        :selected-items="selectedItems"
        @update="setSelectedItems"
      >
        <template v-slot:row="{ row }">
          <!-- Learning item -->
          <LinkedInLearningItem :data="row" :loading="isLoading" :logo="logo" />
        </template>
      </SelectionTable>

      <SelectionPaging
        :current-page="paginationPage"
        :items-per-page="paginationLimit"
        :total-items="learningObjects.total"
        @items-per-page-change="setItemsPerPage"
        @page-change="setPaginationPage"
      />
    </template>

    <!-- Table of selected courses for reviewing -->
    <template v-if="reviewingLearningObjects" v-slot:review-table>
      <ReviewTable
        :items="reviewingLearningObjects.items"
        row-label-key="name"
        :selected-items="selectedItems"
        @update="setSelectedItems"
      >
        <template v-slot:row="{ checked, row }">
          <!-- Learning item -->
          <LinkedInLearningItem
            :data="row"
            :small="true"
            :unselected="!checked"
          >
            <template
              v-if="reviewingItemCategories[row.id] && checked"
              v-slot:side-content
            >
              <LinkedInLearningItemCategory
                :category-options="categoryOptions"
                :course-id="row.id"
                :current-category="reviewingItemCategories[row.id]"
                @change-course-category="setSingleCourseCategory"
              />
            </template>
          </LinkedInLearningItem>
        </template>
      </ReviewTable>

      <ReviewPaging
        :last-page="!reviewingLearningObjects.next_cursor.length"
        @next-page="updateReviewPage"
      />
    </template>
  </Layout>
</template>

<script>
import Basket from 'totara_contentmarketplace/components/basket/ImportBasket';
import CountAndFilters from 'totara_contentmarketplace/components/count/ImportCountAndFilters';
import Layout from 'totara_contentmarketplace/pages/CatalogImportLayout';
import LinkedInFilters from 'contentmarketplace_linkedin/components/filters/ImportSideFilters';
import LinkedInLearningItem from 'contentmarketplace_linkedin/components/learning_item/ImportLearningItem';
import LinkedInLearningItemCategory from 'contentmarketplace_linkedin/components/learning_item/ImportLearningItemCategory';
import LinkedInPrimaryFilter from 'contentmarketplace_linkedin/components/filters/ImportPrimaryFilter';
import PageBackLink from 'tui/components/layouts/PageBackLink';
import ReviewPaging from 'totara_contentmarketplace/components/paging/ImportReviewLoadMore';
import ReviewTable from 'totara_contentmarketplace/components/tables/ImportReviewTable';
import SelectionPaging from 'totara_contentmarketplace/components/paging/ImportSelectionPaging';
import SelectionTable from 'totara_contentmarketplace/components/tables/ImportSelectionTable';
import SortFilter from 'totara_contentmarketplace/components/filters/ImportSortFilter';
import { notify } from 'tui/notifications';
import { parseQueryString, url } from 'tui/util';

// GraphQL
import courseCategoriesQuery from 'contentmarketplace_linkedin/graphql/catalog_import_course_categories';
import createCourseMutation from 'contentmarketplace_linkedin/graphql/catalog_import_create_course';
import filterOptionsQuery from 'contentmarketplace_linkedin/graphql/catalog_import_learning_objects_filter_options';
import learningObjectsQuery from 'contentmarketplace_linkedin/graphql/catalog_import_learning_objects';
import localesQuery from 'contentmarketplace_linkedin/graphql/catalog_import_available_locales';

const LANGUAGE_ENGLISH = 'en';

export default {
  components: {
    Basket,
    CountAndFilters,
    Layout,
    LinkedInLearningItem,
    LinkedInLearningItemCategory,
    LinkedInFilters,
    LinkedInPrimaryFilter,
    PageBackLink,
    ReviewPaging,
    ReviewTable,
    SelectionPaging,
    SelectionTable,
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
  },

  data() {
    return {
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
      // Categories assigned to reviewing items
      reviewingItemCategories: {},
      // List of selected items provided to review step
      reviewingItemList: [],
      // Number of items to display per page
      reviewingItemPageLimit: 50,
      // Selected learning content populated by learningObjectsQuery
      reviewingLearningObjects: {
        items: [],
        next_cursor: '',
        total: 0,
      },
      // Current load more page on review display
      reviewingLoadMorePage: 1,
      // Showing display for reviewing selected items
      reviewingSelectedItems: false,
      // Selected category value
      selectedCategory: {
        id: null,
        label: null,
      },
      categoryId: null,
      selectedFilters: {
        search: '',
        subjects: [],
        time_to_complete: [],
        in_catalog: [],
      },
      // Selected course ID's
      selectedItems: [],
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

    reviewingLearningObjects: {
      query: learningObjectsQuery,
      skip() {
        return !this.reviewingSelectedItems;
      },
      variables() {
        return {
          input: {
            filters: {
              ids: this.reviewingItemList,
              language: this.selectedLanguage,
              search: '',
              subjects: [],
              time_to_complete: [],
              in_catalog: [],
            },
            pagination: {
              limit: this.reviewingItemPageLimit,
              page: 1,
            },
            sort_by: 'LATEST',
          },
        };
      },
      update({ result: data }) {
        return data;
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
    });

    this.settingInitFilters = false;
  },

  methods: {
    /**
     * Remove all selected items from basket
     *
     */
    clearSelectedItems() {
      this.selectedItems = [];
    },

    /**
     * Creating courses
     */
    async createCourses() {
      try {
        this.creatingContentLoading = true;
        const {
          data: { payload },
        } = await this.$apollo.mutate({
          mutation: createCourseMutation,
          variables: {
            input: this.selectedItems.map(item => {
              return {
                learning_object_id: item,
                category_id: this.reviewingItemCategories[item].id,
              };
            }),
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
            'content_creation_unknown_failure',
            'contentmarketplace_linkedin'
          ),
          type: 'error',
        });
        this.creatingContentLoading = false;
      }
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
     * Set all selected item categories to the default
     *
     */
    setAllCategoriesToDefault() {
      let selectedCategories = {};

      // Add an entry for each selected course and set it to the default
      this.selectedItems.forEach(key => {
        selectedCategories[key] = this.selectedCategory;
      });

      // Reset individual item categories to the default
      this.reviewingItemCategories = selectedCategories;
    },

    /**
     * Set the default selected category for all courses
     *
     * @param {String} value
     */
    setDefaultSelectedCategory(value) {
      // Set to default if no value
      if (value === null) {
        this.selectedCategory = {
          id: null,
          label: null,
        };

        return;
      }

      // Store key and string for selected value
      this.selectedCategory = this.categoryOptions.find(key => {
        return key.id === value;
      });
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
     * Set the next page for the reviewing load more button
     *
     * @param {Number} page
     */
    setLoadMorePage(page) {
      this.reviewingLoadMorePage = page;
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
      if (this.$refs['selection-table']) {
        this.$refs['selection-table'].$el.scrollIntoView();
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
     * Set selected course items (chosen from the table)
     *
     * @param {Array} items
     */
    setSelectedItems(items) {
      this.selectedItems = items;
    },

    /**
     * Update selected category for a single course
     *
     * @param {Object} data
     */
    setSingleCourseCategory(data) {
      // Get string & ID for selected value
      let selectedCategory = this.categoryOptions.find(key => {
        return key.id === data.value;
      });

      this.reviewingItemCategories[data.courseId] = selectedCategory;
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

    /**
     * Update displayed results on review page (load more)
     *
     */
    updateReviewPage() {
      // Increase page number
      this.setLoadMorePage(this.reviewingLoadMorePage + 1);

      // Fetch additional data
      this.$apollo.queries.reviewingLearningObjects.fetchMore({
        variables: {
          input: {
            filters: {
              ids: this.reviewingItemList,
              language: this.selectedLanguage,
              search: '',
              subjects: [],
              time_to_complete: [],
              in_catalog: [],
            },
            pagination: {
              limit: this.reviewingItemPageLimit,
              page: this.reviewingLoadMorePage,
            },
            sort_by: 'LATEST',
          },
        },
        updateQuery: (previousResult, { fetchMoreResult }) => {
          fetchMoreResult.result.items.unshift(...previousResult.result.items);
          return fetchMoreResult;
        },
      });
    },

    /**
     * Update the view (either viewing catalogue or reviewing selected items)
     *
     * @param {Boolean} reviewing
     */
    switchContentView(reviewing) {
      // If switching to review display, update default categories of items
      if (reviewing) {
        // Reset load more button
        this.setLoadMorePage(1);

        // Set all item categories to the default value
        this.setAllCategoriesToDefault();

        // Provide selected item list as a unique array
        this.reviewingItemList = this.selectedItems;
      } else {
        // Reset filters
        this.resetPanelFilters();

        // Reset pagination settings
        this.setItemsPerPage(20);
        this.setPaginationPage(1);
      }

      // Switch view
      this.reviewingSelectedItems = reviewing;
    },
  },
};
</script>

<lang-strings>
  {
    "contentmarketplace_linkedin": [
      "catalog_title",
      "catalog_review_title",
      "content_creation_unknown_failure",
      "sort_filter_alphabetical",
      "sort_filter_latest"
    ],
    "totara_contentmarketplace": [
      "manage_content_marketplaces"
    ]
  }
</lang-strings>
