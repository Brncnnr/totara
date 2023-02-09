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
  @module totara_webapi
-->

<template>
  <div>
    <p>
      This is a test page to test queries using sessions and queries using
      nosession.
    </p>
    <div>
      <button type="button" @click="sendSessionQuery()">
        {{ sessionButtonText }}
      </button>
      <h4 v-if="session">Result:</h4>
      <div v-if="session">
        <pre>{{ session }}</pre>
      </div>
      <h4 v-if="sessionError">Error:</h4>
      <div v-if="sessionError">
        <pre>{{ sessionError }}</pre>
      </div>
    </div>
    <div><hr /></div>
    <div>
      <button type="button" @click="sendNosessionQuery()">
        {{ nosessionButtonText }}
      </button>
      <h3 v-if="nosession">Result:</h3>
      <div v-if="nosession">
        <pre>{{ nosession }}</pre>
      </div>
      <h4 v-if="nosessionError">Error:</h4>
      <div v-if="nosessionError">
        <pre>{{ nosessionError }}</pre>
      </div>
    </div>
  </div>
</template>

<script>
import statusNosessionQuery from 'totara_webapi/graphql/status_nosession';
import statusSessionQuery from 'totara_webapi/graphql/status';

export default {
  components: {},
  props: {},
  data() {
    return {
      sessionButtonText: 'Send session query',
      nosessionButtonText: 'Send nosession query',
      nosession: '',
      session: '',
      sessionError: '',
      nosessionError: '',
    };
  },
  methods: {
    sendSessionQuery() {
      this.$apollo.queries.session.skip = false;
      this.$apollo.queries.session.refetch();
    },

    sendNosessionQuery() {
      this.$apollo.queries.nosession.skip = false;
      this.$apollo.queries.nosession.refetch();
    },
  },
  apollo: {
    nosession: {
      query: statusNosessionQuery,
      update: data => data.totara_webapi_status,
      error(error) {
        this.nosessionError =
          error.graphQLErrors.length > 0
            ? error.graphQLErrors
            : error.networkError;
        return false;
      },
      skip: true,
      fetchPolicy: 'no-cache',
    },
    session: {
      query: statusSessionQuery,
      update: data => data.totara_webapi_status,
      error: function(error) {
        this.sessionError =
          error.graphQLErrors.length > 0
            ? error.graphQLErrors
            : error.networkError;
        return false;
      },
      skip: true,
      fetchPolicy: 'no-cache',
    },
  },
};
</script>
