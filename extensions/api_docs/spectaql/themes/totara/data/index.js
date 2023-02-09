/**
 * This file is part of the Totara API docs.
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
 *
 * MIT License
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

const { Microfiber: IntrospectionManipulator } = require('microfiber');
// Navigation structure passed in as environment variable from build.js script.
let totaraNav = JSON.parse(process.env.NAV);

function sortByName(a, b) {
  if (a.name > b.name) {
    return 1;
  }
  if (a.name < b.name) {
    return -1;
  }

  return 0;
}

function sortByReverseId(a, b) {
  if (a.id < b.id) {
    return 1;
  }
  if (a.id > b.id) {
    return -1;
  }

  return 0;
}

function createNode(name) {
  return {
    name: name,
    hideInNav: false,
    makeNavSection: true,
    hideInContent: false,
    makeContentSection: true,
    items: [],
  };
}

function findOrCreateNode(parent, name) {
  let node = parent.find(item => item.name === name);
  if (!node) {
    node = createNode(name);
    parent.push(node);
  }
  return node;
}

function mapItemToType(item, type) {
  return {
    ...item,
    isQuery: type === 'query',
    isMutation: type === 'mutation',
    isSubscription: false,
    isType: type === 'type',
  };
}

module.exports = ({
  // The Introspection Query Response after all the augmentation and metadata directives
  // have been applied to it
  introspectionResponse,
}) => {
  const introspectionManipulator = new IntrospectionManipulator(
    introspectionResponse
  );

  let queryType = introspectionManipulator.getQueryType();
  let mutationType = introspectionManipulator.getMutationType();
  let normalTypes = introspectionManipulator.getAllTypes({
    includeQuery: false,
    includeMutation: false,
    includeSubscription: false,
  });

  let reference = [];

  // Sort totaraNav by id in reverse alphabetical order to sort items into component with most specificity
  // e.g. items are sorted into core_user before core
  totaraNav.sort(sortByReverseId);

  totaraNav.forEach(({ id, name }) => {
    let componentNode = createNode(name);

    let matchingQueries = queryType.fields
      .filter(query => query.name.startsWith(id))
      .map(query => mapItemToType(query, 'query'))
      .sort(sortByName);

    queryType.fields = queryType.fields.filter(
      query => !query.name.startsWith(id)
    );

    let matchingMutations = mutationType.fields
      .filter(mutation => mutation.name.startsWith(id))
      .map(mutation => mapItemToType(mutation, 'mutation'))
      .sort(sortByName);

    mutationType.fields = mutationType.fields.filter(
      mutation => !mutation.name.startsWith(id)
    );

    let matchingTypes = normalTypes
      .filter(type => type.name.startsWith(id))
      .map(type => mapItemToType(type, 'type'))
      .sort(sortByName);

    normalTypes = normalTypes.filter(type => !type.name.startsWith(id));

    // Only add queries node if there are types for this component.
    if (matchingQueries.length > 0) {
      let queriesNode = createNode('Queries');
      queriesNode.items = matchingQueries;
      componentNode.items.push(queriesNode);
    }

    // Only add mutations node if there are types for this component.
    if (matchingMutations.length > 0) {
      let mutationsNode = createNode('Mutations');
      mutationsNode.items = matchingMutations;
      componentNode.items.push(mutationsNode);
    }

    // Only add types node if there are types for this component.
    if (matchingTypes.length > 0) {
      let typesNode = createNode('Types');
      typesNode.items = matchingTypes;
      componentNode.items.push(typesNode);
    }

    // Only add component if there's a node for at least one type.
    if (componentNode.items && componentNode.items.length > 0) {
      reference.push(componentNode);
    }
  });

  // Add items without matching prefix to core node
  // If there are none return reference
  if (
    queryType.length === 0 &&
    mutationType.length === 0 &&
    normalTypes.length === 0
  ) {
    return reference.sort(sortByName);
  }

  let coreNav = totaraNav.find(item => item.id === 'core');

  let coreNode = findOrCreateNode(reference, coreNav.name);

  if (queryType.fields.length !== 0) {
    let queriesNode = findOrCreateNode(coreNode.items, 'Queries');

    let matchingQueries = queryType.fields.map(query =>
      mapItemToType(query, 'query')
    );

    queriesNode.items = [...matchingQueries, ...queriesNode.items];

    queriesNode.items.sort(sortByName);
  }

  if (mutationType.fields.length !== 0) {
    let mutationsNode = findOrCreateNode(coreNode.items, 'Mutations');

    let matchingMutations = mutationType.fields.map(mutation =>
      mapItemToType(mutation, 'mutation')
    );

    mutationsNode.items = [...matchingMutations, ...mutationsNode.items];

    mutationsNode.items.sort(sortByName);
  }

  if (normalTypes.length !== 0) {
    let typesNode = findOrCreateNode(coreNode.items, 'Types');

    let matchingTypes = normalTypes.map(type => mapItemToType(type, 'type'));

    // Move basic (capitalised) types to top

    let basicTypes = [];

    for (let i = 0; i < matchingTypes.length; i++) {
      if (
        matchingTypes[i].name.charAt(0) ===
        matchingTypes[i].name.charAt(0).toUpperCase()
      ) {
        basicTypes.push(matchingTypes[i]);
      } else {
        typesNode.items.push(matchingTypes[i]);
      }
    }

    basicTypes.sort(sortByName);
    typesNode.items.sort(sortByName);

    typesNode.items = [...basicTypes, ...typesNode.items];

    // Move types node to the bottom of core node
    if (coreNode.items.length > 1) {
      let typesNodeIndex = coreNode.items.findIndex(
        node => node.name === 'Types'
      );
      if (typesNodeIndex < coreNode.items.length - 1) {
        coreNode.items.splice(typesNodeIndex, 1);
        coreNode.items.push(typesNode);
      }
    }
  }

  reference.sort(sortByName);

  // Move core node to the start of reference
  let coreNodeIndex = reference.findIndex(node => node.name === coreNav.name);
  reference.splice(coreNodeIndex, 1);
  reference.unshift(coreNode);

  return reference;
};
