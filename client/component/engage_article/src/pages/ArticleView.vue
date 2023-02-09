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

  @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
  @module engage_article
-->

<template>
  <Layout class="tui-engageArticleView">
    <template v-if="backButton || navigationButtons" v-slot:header>
      <ResourceNavigationBar
        :back-button="backButton"
        :navigation-buttons="navigationButtons"
        class="tui-engageArticleView__backButton"
      />
    </template>
    <template v-slot:column>
      <Loader :loading="$apollo.loading" :fullpage="true" />
      <div v-if="!$apollo.loading" class="tui-engageArticleView__layout">
        <ArticleTitle
          :title="articleName"
          :resource-id="resourceId"
          :owned="article.owned"
          :bookmarked="bookmarked"
          :update-able="article.updateable"
          :show-bookmark-button="interactor.can_bookmark"
          @bookmark="updateBookmark"
        />
        <div class="tui-engageArticleView__actions">
          <ArticleSeparator />
          <ButtonIcon
            v-show="article.updateable"
            :disabled="editing"
            :aria-label="
              $str('editarticlecontent', 'engage_article', articleName)
            "
            :styleclass="{
              circle: true,
            }"
            @click="enableEditing"
          >
            <EditIcon />
          </ButtonIcon>
        </div>
        <ArticleContent
          :title="articleName"
          :update-able="article.updateable"
          :content="article.content"
          :editing="editing"
          :resource-id="resourceId"
          @disable-editing="disableEditing"
        />
      </div>
    </template>
    <template v-slot:sidepanel>
      <ArticleSidePanel :resource-id="resourceId" :interactor="interactor" />
    </template>
  </Layout>
</template>

<script>
import ArticleContent from 'engage_article/components/content/ArticleContent';
import ArticleSeparator from 'engage_article/components/separator/ArticleSeparator';
import ArticleSidePanel from 'engage_article/components/sidepanel/ArticleSidePanel';
import ArticleTitle from 'engage_article/components/content/ArticleTitle';
import ButtonIcon from 'tui/components/buttons/ButtonIcon';
import EditIcon from 'tui/components/icons/Edit';
import Layout from 'totara_engage/components/page/LayoutOneColumnContentWithSidePanel';
import Loader from 'tui/components/loading/Loader';
import ResourceNavigationBar from 'totara_engage/components/header/ResourceNavigationBar';

// GraphQL
import getArticle from 'engage_article/graphql/get_article';
import updateBookmark from 'totara_engage/graphql/update_bookmark';

export default {
  components: {
    ArticleContent,
    ArticleSeparator,
    ArticleSidePanel,
    ArticleTitle,
    ButtonIcon,
    EditIcon,
    Layout,
    Loader,
    ResourceNavigationBar,
  },

  props: {
    resourceId: {
      type: Number,
      required: true,
    },

    backButton: {
      type: Object,
      required: false,
    },

    navigationButtons: {
      type: Object,
      required: false,
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

  data() {
    return {
      article: {},
      bookmarked: false,
      editing: false,
    };
  },

  computed: {
    articleName() {
      if (!this.article.resource || !this.article.resource.name) {
        return '';
      }

      return this.article.resource.name;
    },
  },

  apollo: {
    article: {
      query: getArticle,
      variables() {
        return {
          id: this.resourceId,
        };
      },
      result({ data: { article } }) {
        this.bookmarked = article.bookmarked;
      },
    },
  },

  methods: {
    disableEditing() {
      this.editing = false;
    },

    enableEditing() {
      this.editing = true;
    },

    updateBookmark() {
      this.bookmarked = !this.bookmarked;
      this.$apollo.mutate({
        mutation: updateBookmark,
        refetchAll: false,
        variables: {
          itemid: this.resourceId,
          component: 'engage_article',
          bookmarked: this.bookmarked,
        },
        update: proxy => {
          let { article } = proxy.readQuery({
            query: getArticle,
            variables: {
              id: this.resourceId,
            },
          });

          article = Object.assign({}, article);
          article.bookmarked = this.bookmarked;

          proxy.writeQuery({
            query: getArticle,
            variables: { id: this.resourceId },
            data: { article: article },
          });
        },
      });
    },
  },
};
</script>

<lang-strings>
  {
    "engage_article": [
      "editarticlecontent",
      "entercontent",
      "entertitle"
    ]
  }
</lang-strings>

<style lang="scss">
:root {
  --engageArticle-min-height: 100vh;
}

.tui-engageArticleView {
  .tui-grid-item {
    min-height: var(--engageArticle-min-height);
  }
  &__backButton {
    margin-bottom: var(--gap-12);
    padding: var(--gap-4) var(--gap-8);
  }

  &__layout {
    padding-right: var(--gap-8);
    padding-left: var(--gap-8);
  }

  &__actions {
    display: flex;
    flex-direction: row;
    justify-content: space-between;
    margin-top: var(--gap-4);
  }
}

@media (min-width: $tui-screen-xs) {
  .tui-engageArticleView {
    &__layout {
      padding-right: 0;
      padding-left: 0;
    }
  }
}
</style>
