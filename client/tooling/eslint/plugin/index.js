/**
 * This file is part of Totara Enterprise Extensions.
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
 *
 * Totara Enterprise Extensions is provided only to Totara
 * Learning Solutions LTD's customers and partners, pursuant to
 * the terms and conditions of a separate agreement with Totara
 * Learning Solutions LTD or its affiliate.
 *
 * If you do not have an agreement with Totara Learning Solutions
 * LTD, you may not access, use, modify, or distribute this software.
 * Please contact [licensing@totaralearning.com] for more information.
 *
 * @author Simon Chester <simon.chester@totaralearning.com>
 * @module totara_core
 */

const generatorError =
  'Generators are not allowed due to performance considerations in IE 11.';

function checkGenerator(context, node) {
  if (node.generator) {
    context.report(node, generatorError);
  }
}

module.exports = {
  rules: {
    'no-generators': {
      create: context => ({
        FunctionExpression(node) {
          checkGenerator(context, node);
        },
        FunctionDeclaration(node) {
          checkGenerator(context, node);
        },
      }),
    },

    'no-export-vue-extend': {
      create: context => ({
        ExportDefaultDeclaration(node) {
          if (node.declaration.type != 'CallExpression') {
            return;
          }
          const callee = node.declaration.callee;
          const isMatch =
            callee.type == 'MemberExpression' &&
            callee.object.type == 'Identifier' &&
            callee.object.name == 'Vue' &&
            callee.property.type == 'Identifier' &&
            callee.property.name == 'extend';
          if (isMatch) {
            context.report(
              callee,
              'Avoid using Vue.extend to define exported components as it ' +
                'will not work with theme inheritance.'
            );
          }
        },
      }),
    },

    'no-for-of': {
      create: context => ({
        ForOfStatement(node) {
          context.report(
            node,
            'for..of is not supported by IE 11 and cannot be polyfilled ' +
              'perfomantly. Try using .forEach or a plain for loop instead.'
          );
        },
      }),
    },

    'no-tui-internal': {
      create: context => ({
        MemberExpression(node) {
          if (
            node.object.type == 'Identifier' &&
            node.object.name == 'tui' &&
            node.property.type == 'Identifier' &&
            node.property.name[0] == '_'
          ) {
            context.report(
              node.property,
              'Do not access internal TUI properties, as they may change without warning.'
            );
          }
        },
      }),
    },

    'no-weka-standalone-usage-identifier': require('./no_weka_standalone_usage_identifier'),
  },
};
