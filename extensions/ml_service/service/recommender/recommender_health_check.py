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

import pandas as pd
from os import path


class RecommenderHealthCheck:
    """
    This class gathers some information on the Recommendations model as response to
    Totara Health Checks
    """

    def __init__(self, recommender_model, logs_dir):
        """
        Class constructor method

        :param recommender_model: The recommendation model object
        :type recommender_model: dict
        :param logs_dir: Path to the logs directory
        :type logs_dir: str
        """
        self.recommender_model = recommender_model
        self.logs_dir = logs_dir

    @staticmethod
    def read_logs(filename: str) -> dict:
        """
        To read the existing logs in the `filename` file

        :param filename: Path of the file that contains the recommendation model
            training logs
        :type filename: str
        :return: The `TIMESTAMP`, `ALGORITHM`, `TENANT_ID`, and `MSG` column values from
            the last training run
        :rtype: dict
        """
        logs = pd.read_csv(
            filepath_or_buffer=filename,
            sep=",",
            dtype={
                "TIMESTAMP": str,
                "ALGORITHM": str,
                "TENANT_ID": str,
                "MSG": str,
            },
            usecols=[
                "TIMESTAMP",
                "ALGORITHM",
                "TENANT_ID",
                "MSG",
            ],
        )
        last_training_time = logs["TIMESTAMP"].iloc[-1]
        logs_info = logs[logs["TIMESTAMP"] == last_training_time].to_dict(orient="list")
        for k in logs_info:
            logs_info[k] = ", ".join(logs_info[k])

        return logs_info

    def recommender_health(self) -> dict:
        """
        This returns the information on the current recommendation model and the last
        training logs

        :return: Status of the current recommendation model and the last logs
        :rtype: dict
        """
        if not self.recommender_model:
            return {"recommendation_model": "false"}

        recommender_info = {"recommendation_model": "true"}
        log_file = path.join(self.logs_dir, "train_summary.log")
        if path.exists(path=log_file):
            recommender_info["logs_file_exists"] = "true"
            logs_info = self.read_logs(filename=log_file)
            recommender_info["last_recommendation_model_training"] = str(logs_info)
        else:
            recommender_info["logs_file_exists"] = "false"

        return recommender_info
