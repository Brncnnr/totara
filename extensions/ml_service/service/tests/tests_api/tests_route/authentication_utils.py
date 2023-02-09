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

import hashlib


class AuthenticationUtils:
    """
    To create authentication headers for testing the endpoints
    """

    def __init__(self, timestamp=0, secret_key=""):
        """
        Class constructor method
        :param timestamp: Timestamp
        :type timestamp: float
        :param secret_key: The secret key
        :type secret_key: str
        """
        self.timestamp = str(int(timestamp))
        self.secret_key = secret_key

    def create_headers(self):
        """
        To create headers
        :return: Headers dictionary
        :rtype: dict
        """
        hashed_key = hashlib.sha256(
            (self.timestamp + self.secret_key).encode("utf8")
        ).hexdigest()
        return {
            "User-Agent": "TotaraBot/1.0",
            "X-Totara-Ml-Key": hashed_key,
            "X-Totara-Time": self.timestamp,
        }
