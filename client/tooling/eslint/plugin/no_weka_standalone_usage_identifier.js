function getAttr(node, attr) {
  return (
    node && node.attributes && node.attributes.find(x => attrMatches(x, attr))
  );
}

function attrMatches(attr, name) {
  if (attr.type !== 'VAttribute' || !attr.key) {
    return false;
  }

  if (attr.key.type === 'VDirectiveKey') {
    return attr.key.argument === name;
  } else if (attr.key.type === 'VIdentifier') {
    return attr.key.name === name;
  }

  return false;
}

module.exports = {
  create(context) {
    return context.parserServices.defineTemplateBodyVisitor({
      'VElement[name=weka]': node => {
        if (!node.startTag) {
          return;
        }
        // Detect and warn for usage-identifier without variant
        const usageIdentifier = getAttr(node.startTag, 'usage-identifier');
        if (usageIdentifier && !getAttr(node.startTag, 'variant')) {
          context.report({
            node: usageIdentifier,
            message:
              'Passing usage-identifier to Weka without variant (which will ' +
              'construct a variant from usage-identifier) was deprecated in ' +
              'Totara 17. Please update your code to pass a variant ' +
              'explicitly (e.g. variant="standard") as in a future release, ' +
              'variant will default to "standard" even when usage-identifier ' +
              'is passed.',
          });
        }
      },
    });
  },
};
