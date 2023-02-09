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

import unittest
from unittest.mock import patch
from service.communicator.totara_graphql import TotaraGraphql


class TestTotaraGraphql(unittest.TestCase):
    """
    The test object to test the units of the TotaraGraphql class in
    `service.communicator.totara_graphql`
    """

    def setUp(self) -> None:
        """
        Hook method for setting up the fixtures before exercising it
        """
        self.test_totara_url = "https://totara.server"
        self.totara_graphql = TotaraGraphql(totara_url=self.test_totara_url)
        self.longMessage = False

    @patch(target="service.communicator.totara_graphql.post")
    def test_send(self, mock_sender) -> None:
        """
        This method tests if the `requests.post` method is called with the arguments
        as expected
        """
        test_variables = {"variable1": "a", "variable2": 0}
        _ = self.totara_graphql.send(
            operation_name="test_operation", variables=test_variables
        )

        self.assertEqual(
            first=mock_sender.call_args,
            second=unittest.mock.call(
                url=self.test_totara_url + "/totara/webapi/ajax.php",
                json={
                    "operationName": "test_operation",
                    "variables": test_variables,
                    "extensions": {},
                },
                verify=False,
                timeout=1.5,
            ),
        )

    @patch(target="service.communicator.totara_graphql.post")
    def test_check(self, mock_poster) -> None:
        """
        This method tests if the `check` method of the `TotaraGraphql` behaves as
        expected
        """
        mock_poster.return_value.json.return_value = {
            "totara_webapi_status": {"status": "ok"}
        }
        test_response = self.totara_graphql.check()

        self.assertIsInstance(
            obj=test_response,
            cls=tuple,
            msg=(
                "The returned value from the 'check' method of the 'TotaraGraphql' "
                f"class is an instance of {type(test_response)} while it was expected "
                f"to be an instance of <class 'tuple'>"
            ),
        )

        self.assertTrue(
            expr=test_response[0],
            msg="The first value of the returned tuple is not 'True'",
        )
