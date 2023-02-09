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

# We need python, don't continue if we don't have it
check_python_version

ensure_pip

echo "Upgrading pip..."
$pip_command install --upgrade pip

echo "Installing packages..."
if $pip_command install --no-cache-dir -r requirements.txt ; then
  echo "Packages installed."
else
  echo "Failed to install packages. Exiting."
  exit 1
fi

echo
echo "Installation complete. Please start the service with ./start.sh"
