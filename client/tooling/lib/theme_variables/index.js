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
 * @module tui
 */

const { parseCss } = require('./css_parser');
const { parseDerive } = require('./derive_expression_parser');

const varValueRegex = /^var\(\s*([^),]+)(?:\s*,\s*([^)\s]+)\s*)?\)$/s;

/**
 * Normalise property value.
 *
 * Converts #123 to #112233.
 *
 * @param {string} val
 * @returns {string}
 */
function normalizeValue(val) {
  if (typeof val === 'string' && val[0] == '#' && val.length === 4) {
    return '#' + val[1] + val[1] + val[2] + val[2] + val[3] + val[3];
  }
  return val;
}

/**
 * Get vars exported by CSS source.
 *
 * @param {string} css
 * @returns {{ vars: object }}
 */
function getThemeExportedVars(css) {
  const rules = parseCss(css);

  const vars = {};
  const cssVarMap = {};

  for (const rule of rules) {
    if (rule.selectorText != ':root') {
      continue;
    }

    let nextPropertyFlags = {};
    for (const node of rule.children) {
      switch (node.type) {
        case 'control_comment':
          nextPropertyFlags[node.commentType] = node;
          break;

        case 'property': {
          if (
            node.property.startsWith('--') &&
            nextPropertyFlags['theme:var']
          ) {
            // calculate name to use for theme variable
            const key = node.property.slice(2);

            // mapping of css variables that correspond to this theme variable (normally 1 but can be multiple)
            if (!cssVarMap[key]) {
              cssVarMap[key] = [];
            }
            cssVarMap[key].push(node.property);

            // calculate value
            let value;
            const varMatch = varValueRegex.exec(node.value);
            if (varMatch && varMatch[1].slice(0, 2) == '--') {
              value = {
                type: 'var',
                value: varMatch[1].slice(2),
              };
              if (varMatch[2]) {
                value.default = normalizeValue(varMatch[2]);
              }
            } else {
              value = { type: 'value', value: normalizeValue(node.value) };
            }

            // add derive data if provided
            const themeDerive = nextPropertyFlags['theme:derive'];
            if (themeDerive) {
              const deriveAst = parseDerive(themeDerive.args);
              const usedVars = [];
              getUsedThemeVars(usedVars, deriveAst);
              // value.derivedValue = deriveAst;
              // value.derivedFrom = usedVars;
              value.transform = generateTransform(deriveAst, themeDerive.args);
            }

            vars[key] = value;
            nextPropertyFlags = {};
          }
          break;
        }
      }
    }
  }
  return { vars };
}

/**
 * Get theme variables used by derive expression.
 *
 * @param {*} arr
 * @param {*} node
 */
function getUsedThemeVars(arr, node) {
  if (node.type == 'var-ref') {
    arr.push(node.name);
  } else if (node.type == 'call') {
    if (node.name == 'var') {
      const arg = node.args[0];
      if (arg.type == 'atom') {
        arr.push(arg.name);
      } else {
        throw new Error(
          'Unknown node in call to theme-var: ' + JSON.stringify(node)
        );
      }
    } else {
      node.args.forEach(node => getUsedThemeVars(arr, node));
      if (node.namedArgs) {
        Object.values(node.namedArgs).forEach(node =>
          getUsedThemeVars(arr, node)
        );
      }
    }
  }
}

function generateTransform(deriveAst, src) {
  if (deriveAst.type != 'call') {
    throw new Error('unsupported derive syntax: ' + src);
  }
  const name = deriveAst.name;
  let args = deriveAst.args;
  if (args[0].type != 'var-ref') {
    throw new Error(
      'unsupported derive argument, first argument must be a var(): ' + src
    );
  }
  const varArg = args.shift();
  args = args.map(x => {
    if (x.type != 'number' && x.type != 'var-ref') {
      throw new Error(
        'unsupported derive argument, must be a number or a var(): ' + src
      );
    }
    return x.value || x.name;
  });

  return {
    source: varArg.name,
    type: 'var',
    call: name,
    args,
  };
}

module.exports = {
  getThemeExportedVars,
  parseDerive,
};
