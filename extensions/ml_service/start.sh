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


SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )"
cd "$SCRIPT_DIR" || exit 1

source scripts/functions.sh

# Check that the environmental variables have been provided & our directories exist
check_env_vars "$SCRIPT_DIR"
check_start_env_vars

# We need python, don't continue if we don't have the correct version of it 
if [ -z ${PYTHON_EXEC+x} ]; then
  check_python_version
else
  check_python_version "$PYTHON_EXEC"
fi

echo "Starting Totara Machine Learning service..."

# Make sure we have stopwords
$python_command -m nltk.downloader stopwords -d $ML_MODELS_DIR

if [[ "$ML_DEV" == "1" ]]; then
  cd service
  FLASK_ENV="Development" $python_command -m flask run --host=0.0.0.0
else
  $python_command -m waitress --listen="${ML_BIND:-*:5000}" --call "service.app:create_app"
fi
