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

@author Cody Finegan <cody.finegan@totaralearning.com>
@package ml_service
"""

import datetime
import typing
from requests import post


class TotaraGraphql:
    """
    Communicator class with the Totara Graphql API
    """

    def __init__(self, totara_url):
        """
        Class constructor method

        :param totara_url: URL of the Totara instance
        :type totara_url: str
        """
        self.totara_url = totara_url

    def send(
        self, operation_name: str, variables: dict = None
    ) -> typing.Tuple[any, datetime.timedelta]:
        """
        Make a request to the GraphQL method provided

        :param operation_name: The name of the persistent query to call
        :type operation_name: str
        :param variables: A collection of variables for the payload
        :type variables: dict
        :return: The dictionary data response, else raises an exception
        """
        payload = {
            "operationName": operation_name,
            "variables": variables or {},
            "extensions": {},
        }

        r = post(
            url=self.totara_url + "/totara/webapi/ajax.php",
            json=payload,
            verify=False,
            timeout=1.5,
        )
        r.raise_for_status()

        json = r.json()
        return json["data"] if "data" in json else json, r.elapsed

    def check(self) -> typing.Tuple[bool, datetime.timedelta]:
        """
        Does a call to totara_webapi_status_nosession and returns a True/False based on
        the results

        :return: A Boolean for whether value of `status` key in the value of
            `totara_webapi_status` key of the returned dictionary is 'ok' and the time
            after which the response is received
        :type: tuple
        """
        data, elapsed = self.send("totara_webapi_status_nosession")

        return (
            "totara_webapi_status" in data
            and data["totara_webapi_status"]["status"] == "ok",
            elapsed,
        )
