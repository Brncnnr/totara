"""
This file is part of Totara Enterprise Extensions.

Copyright (C) 2021 onward Totara Learning Solutions LTD

Totara Enterprise Extensions is provided only to Totara
Learning Solutions LTD's customers and partners, pursuant to
the terms and conditions of a separate agreement with Totara
Learning Solutions LTD or its affiliate.

If you do not have an agreement with Totara Learning Solutions
LTD, you may not access, use, modify, or distribute this software.
Please contact [licensing@totaralearning.com] for more information.

@author Amjad Ali <amjad.ali@totaralearning.com>
@package ml_service
"""

import os


class Config(object):
    """Set Flask base config variables"""

    STATIC_FOLDER = ""
    LOGS_DIR = os.environ.get("ML_LOGS_DIR")
    MODELS_DIR = os.environ.get("ML_MODELS_DIR")
    MAX_LEN = os.environ.get("ML_OFFENSIVE_LANGUAGE_LENGTH")
    TOTARA_URL = os.environ.get("ML_TOTARA_URL")
    TOTARA_KEY = os.environ.get("ML_TOTARA_KEY")
    RECOMMENDATION_RETRAIN_FREQ = os.environ.get(
        "ML_RECOMMENDATION_RETRAIN_FREQ", "1440"
    )
    NUM_THREADS = os.environ.get("ML_NUM_THREADS", "4")
    RECOMMENDATION_ALGORITHM = os.environ.get("ML_RECOMMENDATION_ALGORITHM", "hybrid")


class Development(Config):
    """Set Flask development config variables"""

    APP_MODE = "Development"
    TESTING = False
    DEBUG = True


class Production(Config):
    """Set Flask production config variables"""

    APP_MODE = "Production"
    TESTING = False
    DEBUG = False


class Testing(Config):
    """Set Flask testing config values"""

    APP_MODE = "Testing"
    TESTING = True
    TOTARA_URL = "http://totarahost.com"
    TOTARA_KEY = "secretkey"
    MODELS_DIR = "/testing/mock/dir"
    LOGS_DIR = "/testing/mock/dir"
