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
import unittest
from os.path import join
from unittest import mock
from service.recommender.recommender_health_check import RecommenderHealthCheck


class TestRecommenderHealthCheck(unittest.TestCase):
    """
    The test object to test the units of RecommenderHealthCheck class
    """

    def setUp(self) -> None:
        """
        Hook method for setting up the fixtures before exercising it
        """
        recommender_model = None
        logs_dir = "/logs/dir/test"
        self.health_checker = RecommenderHealthCheck(recommender_model, logs_dir)
        self.longMessage = False

    @mock.patch(target="service.recommender.recommender_health_check.pd.read_csv")
    def test_read_logs(self, mock_csv_reader) -> None:
        """
        To test if the 'read_logs' method of the 'RecommenderHealthCheck' class calls
        the 'read_csv' method of the pandas library with the correct arguments and if
        the returned response is as expected
        """
        time_1, time_2 = "2021-10-19 (16:18:30)", "2021-10-20 (16:18:30)"
        algorithm = "ALGORITHM"
        tenant_id_1, tenant_id_2 = "0", "2"
        status = "success"
        test_logs = {
            "TIMESTAMP": [time_1, time_1, time_2, time_2],
            "ALGORITHM": [algorithm, algorithm, algorithm, algorithm],
            "TENANT_ID": [tenant_id_1, tenant_id_2, tenant_id_1, tenant_id_2],
            "STATUS": [status, status, status, status],
        }
        mock_csv_reader.return_value = pd.DataFrame(data=test_logs)
        test_logs_file = "test_file"
        returned_logs = self.health_checker.read_logs(filename=test_logs_file)
        expected_logs = {
            "TIMESTAMP": f"{time_2}, {time_2}",
            "ALGORITHM": f"{algorithm}, {algorithm}",
            "TENANT_ID": f"{tenant_id_1}, {tenant_id_2}",
            "STATUS": f"{status}, {status}",
        }
        expected_call = unittest.mock.call(
            filepath_or_buffer=test_logs_file,
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
        self.assertEqual(
            first=mock_csv_reader.call_args,
            second=expected_call,
            msg=(
                "The pandas 'read_csv' has been called with\n"
                f"{mock_csv_reader.call_args}\n"
                "while it was expected to be called with\n"
                f"{expected_call}"
            ),
        )
        self.assertDictEqual(
            d1=returned_logs,
            d2=expected_logs,
            msg=(
                "The method 'RecommenderHealthCheck.read_logs' has returned\n"
                f"{returned_logs}\n"
                "while it was expected to return\n"
                f"{expected_logs}"
            ),
        )

    def test_recommender_health_no_recommender(self) -> None:
        """
        To test if the returned response is as expected when the recommendation model is
        not available
        """
        returned_health_check = self.health_checker.recommender_health()
        expected_health_check = {"recommendation_model": "false"}

        self.assertDictEqual(
            d1=returned_health_check,
            d2=expected_health_check,
            msg=(
                "The response from 'RecommenderHealthCheck.recommender_health' is\n"
                f"{returned_health_check}\n"
                "while it was expected to be\n"
                f"{expected_health_check}"
            ),
        )

    @mock.patch(target="service.recommender.recommender_health_check.path.exists")
    def test_recommender_health_recommender_no_logs(self, mock_exists) -> None:
        """
        To test if the returned response is as expected when the recommendation model is
        present and the logs file does not exist
        """
        mock_exists.return_value = False
        self.health_checker.recommender_model = {"recommender": "exists"}
        returned_health_check = self.health_checker.recommender_health()

        expected_health_check = {
            "recommendation_model": "true",
            "logs_file_exists": "false",
        }

        self.assertDictEqual(
            d1=returned_health_check,
            d2=expected_health_check,
            msg=(
                "The response from 'RecommenderHealthCheck.recommender_health' is\n"
                f"{returned_health_check}\n"
                "while it was expected to be\n"
                f"{expected_health_check}"
            ),
        )

    @mock.patch(target="service.recommender.recommender_health_check.path.exists")
    def test_recommender_health_recommender_logs(self, mock_exists) -> None:
        """
        To test if the 'read_logs' method is called with the correct arguments and the
        returned response is as expected when the recommendation model is present and
        the logs file exists
        """
        mock_exists.return_value = True
        self.health_checker.recommender_model = {"recommender": "exists"}

        time_1 = "2021-10-20 (16:18:30)"
        algorithm = "partial"
        tenant_id_1, tenant_id_2 = "0", "1"
        status = "success"
        test_last_logs = {
            "TIMESTAMP": f"{time_1}, {time_1}",
            "ALGORITHM": f"{algorithm}, {algorithm}",
            "TENANT_ID": f"{tenant_id_1}, {tenant_id_2}",
            "STATUS": f"{status}, {status}",
        }

        with mock.patch.object(
            target=RecommenderHealthCheck, attribute="read_logs"
        ) as mock_logs:
            mock_logs.return_value = test_last_logs
            returned_health_check = self.health_checker.recommender_health()
            expected_call = unittest.mock.call(
                filename=join(self.health_checker.logs_dir, "train_summary.log")
            )
            self.assertEqual(
                first=mock_logs.call_args,
                second=expected_call,
                msg=(
                    "The 'RecommenderHealthCheck.read_logs' is called with\n"
                    f"{mock_logs.call_args}\n"
                    "while it was expected to be called with\n"
                    f"{expected_call}"
                ),
            )

        expected_health_check = {
            "recommendation_model": "true",
            "logs_file_exists": "true",
            "last_recommendation_model_training": str(test_last_logs),
        }

        self.assertDictEqual(
            d1=returned_health_check,
            d2=expected_health_check,
            msg=(
                "The response from 'RecommenderHealthCheck.recommender_health' is\n"
                f"{returned_health_check}\n"
                "while it was expected to be\n"
                f"{expected_health_check}"
            ),
        )
