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

import hashlib
import re
import time

from requests import get
from requests.exceptions import ConnectionError


class TotaraFiles:
    """
    This is a conceptual representation to download specific files from Totara
    """

    def __init__(self, totara_url, totara_key):
        """
        Class constructor method

        :param totara_url: The URL at which Totara can be reached
        :type totara_url: str
        :param totara_key: The secret key for making connection with Totara
        :type totara_key: str
        """
        self.totara_url = totara_url
        self.totara_key = totara_key

    def make_headers(self):
        """
        This method creates authentication headers for requesting the files from Totara

        :return: Headers based on current timestamp
        :rtype: dict
        """
        now = int(time.time())
        key = hashlib.sha256(f"{now}{self.totara_key}".encode("utf8")).hexdigest()

        headers = {"x-totara-ml-key": key, "x-totara-time": str(now)}

        return headers

    def validate_schema(self) -> bool:
        """
        To check if the URL provided for Totara has the valid http or https schema

        :return: Whether the Totaral URL has valid schema
        :rtype bool:
        """
        return bool(re.match(pattern=r"https?://", string=self.totara_url.lower()))

    def download(self, filename: str) -> dict:
        """
        This method takes file name as argument and returns the content of the file
        after fetching that from Totara

        :param filename: Name of the file to be downloaded
        :type filename: str
        :return: Contents of the file and status after fetching from Totara
        :rtype: dict
        """
        if not self.validate_schema():
            return {"status": "fail", "content": ""}

        try:
            r = get(
                url=(
                    f"{self.totara_url}/pluginfile.php/1/ml_recommender/export"
                    f"/{filename}"
                ),
                headers=self.make_headers(),
                verify=False,
                timeout=30,
            )

            # Retry at least three times if the status code is 202, i.e., the content
            # is not ready
            retry_counter = 1
            while retry_counter <= 3 and r.status_code == 202:
                time.sleep(60)
                r = get(
                    url=(
                        f"{self.totara_url}/pluginfile.php/1/ml_recommender/export"
                        f"/{filename}"
                    ),
                    headers=self.make_headers(),
                    verify=False,
                    timeout=30,
                )
                retry_counter += 1

            if r.status_code == 200:
                content = r.content
                status = "success"
            elif r.status_code == 204 and filename == "tenants.csv":
                # When multi-tenancy is disabled prepare a 'tenants.csv' with
                # default tenant
                content = b"tenants\n0\n"
                status = "success"
            else:
                content = ""
                status = "fail"
        # Return a fail status if there is a connection issue
        except ConnectionError as e:
            content = e
            status = "fail"

        return {"status": status, "content": content}
