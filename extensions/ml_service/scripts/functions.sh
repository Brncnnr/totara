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


# Functions to help manage/download models
check_env_vars() {
  if [ -z "$ML_MODELS_DIR" ]; then
    export ML_MODELS_DIR=$1/data/models
    echo "Using default path for models: $ML_MODELS_DIR"
    echo "If you want to use different path, please define ML_MODELS_DIR environment variable:"
    echo "For example: ML_MODELS_DIR=/path/to/models $0"
    echo
  fi
  
  if [ -z "$ML_LOGS_DIR" ]; then
    export ML_LOGS_DIR=$1/data/logs
    echo "Using default path for logs: $ML_LOGS_DIR"
    echo "If you want to use different path, please define ML_LOGS_DIR environment variable:"
    echo "For example: ML_LOGS_DIR=/path/to/logs $0"
    echo ""
  fi

  if [[ "$ML_MODELS_DIR" == "$ML_LOGS_DIR" ]]; then
    echo "The logs & models directories must not be the same."
    echo "Got: '$ML_MODELS_DIR' and '$ML_LOGS_DIR'"
    exit 1
  fi

  if [[ ! -d "$ML_MODELS_DIR" ]]; then
    echo "Models directory '$ML_MODELS_DIR' does not exist. Creating..."
    if mkdir -p "$ML_MODELS_DIR" ; then
      echo "Created."
    else
      echo "Could not create '$ML_MODELS_DIR'. Exiting."
      exit 1
    fi
  fi

  if [[ ! -d "$ML_LOGS_DIR" ]]; then
    echo "Logs directory '$ML_LOGS_DIR' does not exist. Creating..."  
    if mkdir -p "$ML_LOGS_DIR" ; then
      echo "Created."
    else
      echo "Could not create '$ML_LOGS_DIR'. Exiting."
      exit 1
    fi 
  fi
}

check_start_env_vars() {
  if [[ -z "$ML_TOTARA_URL" ]]; then
    echo "ML_TOTARA_URL has not been defined. Please define the ML_TOTARA_URL environment variable:"
    echo "For example: ML_TOTARA_URL=https://example.com $0"
    exit 1
  fi

  if [[ -z "$ML_TOTARA_KEY" ]]; then
    echo "ML_TOTARA_KEY has not been defined. Please define the ML_TOTARA_KEY environment variable:"
    echo "For example: ML_TOTARA_KEY=auniquetoken $0"
    exit 1
  fi
}

check_python_version() {
  local python_min_ver=37000
  local python_max_ver=39000
  
  echo "Checking python version."
  if [ $# -eq 0 ]; then
    python_command="python3"
    if [[ ! "$($python_command -V 2>&1)" =~ "Python 3" ]]; then
      python_command="python"
    fi
    if [[ ! "$($python_command -V 2>&1)" =~ "Python 3" ]]; then
      echo "Unable to find the correct python version. Please check python3 is installed & available on the path".
      exit 1
    fi
  else
    python_command=$1
  fi

  python_version=$($python_command -c "import sys; print(int(float(f\"{sys.version_info.major}.{sys.version_info.minor}\") * 10000))")
  if [[ "$python_version" -lt $python_min_ver ]] || [[ "$python_version" -gt $python_max_ver ]]; then
    echo "Could not find a valid python version - please check the readme file for installation instructions."
    exit 1
  fi
  echo "Python found."
}

check_pip() {
  echo "Check pip."
    pip_command="pip3"
  if [[ ! "$($pip_command -V 2>&1)" =~ "ython 3" ]]; then
    pip_command="pip"
  fi
  if [[ ! "$($pip_command -V 2>&1)" =~ "ython 3" ]]; then
    echo "Unable to find the correct pip version."  
    return 1
  fi
  return 0
}

ensure_pip() {
  if check_pip ; then
    echo "pip found"
    return 0
  fi

  echo "Installing pip"
  if $python_command -m ensurepip --upgrade ; then
    echo "pip installed."
  else 
    echo "Failed to install pip"
    exit 1
  fi
 
  if check_pip ; then
    echo "pip found"
    return 0
  else
    echo "Still cannot find pip. Please install it manually. Exiting."
    exit 1
  fi
}

models_exist() {
  local models_dir=$1
  [[ -d "${models_dir}/offensive-language-classifier" ]]
}

download_models() {
  if ! command -v git --version &>/dev/null; then
    echo "To download the models you need to have git installed. Otherwise check the documentation about manually providing the models to the service."
    return
  fi

  local models_dir=$1
  local remote_repo="ssh://git@git.totaralearning.com:7999/general/ml-models.git"
  local remote_repo_https="https://git.totaralearning.com/scm/general/ml-models.git"

  if models_exist "$models_dir"; then
    echo "There are already models in your models directory (${models_dir})"
    echo "Please clean-up models directory if you want reinstall them."
    return
  fi

  cd "$models_dir"
  
  # Have we cloned already?
  local download_result
  if [[ -d ".git" ]]; then
    echo "Updating models..."
    echo
    git pull --depth=1 --progress origin master
    download_result=$?
  else
    echo "Downloading the models..."
    echo
    if [[ "$ML_MODELS_GIT_HTTPS" == "1" ]]; then
      remote_repo="${remote_repo_https}"
    fi

    git clone --depth=1 "$remote_repo" --branch master --single-branch --progress ./
    download_result=$?
  fi

  echo

  if [[ "$download_result" == "0" ]]; then
    echo "Model downloading is complete."
  else
    echo "There was a problem trying to download the models."
  fi

  cd "$SCRIPT_DIR"
}
