#!/usr/bin/env bash

# This file is part of Totara Enterprise Extensions.
#
# Copyright (C) 2021 onward Totara Learning Solutions LTD
#
# Totara Enterprise Extensions is provided only to Totara
# Learning Solutions LTD's customers and partners, pursuant to
# the terms and conditions of a separate agreement with Totara
# Learning Solutions LTD or its affiliate.
#
# If you do not have an agreement with Totara Learning Solutions
# LTD, you may not access, use, modify, or distribute this software.
# Please contact [licensing@totaralearning.com] for more information.
#
# @author Cody Finegan <cody.finegan@totaralearning.com>
# @package ml_service


# If we have a param, start a shell instead
if [[ "$1" == "bash" ]]; then
  /bin/sh -c bash
  exit 0
fi

# Create the data  & model dirs if they don't exist already
if [[ ! -d "$ML_LOGS_DIR" ]]; then
  echo "Creating training logs directory..."
  mkdir -p "$ML_LOGS_DIR"
fi
if [[ ! -d "$ML_MODELS_DIR" ]]; then
  echo "Creating models directory..."
  mkdir -p "$ML_MODELS_DIR"
fi

cd /etc/ml

# Run the start script
/etc/ml/start.sh