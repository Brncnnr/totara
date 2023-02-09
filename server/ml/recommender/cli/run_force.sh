#!/usr/bin/env bash
set -ex

SCRIPTPATH="$( cd "$(dirname "$0")" >/dev/null 2>&1 ; pwd -P )"

YELLOW='\033[1;33m'
NC='\033[0m'
printf "${YELLOW}[DEPRECATION WARNING]: ml_recommender has been deprecated in Totara 17 \n ${NC}"

php $SCRIPTPATH/export_data.php --force
eval `php $SCRIPTPATH/recommender_command.php`
php $SCRIPTPATH/import_recommendations.php