"""
This file is part of Totara Enterprise Extensions.

Copyright (C) 2020 onwards Totara Learning Solutions LTD

Totara Enterprise Extensions is provided only to Totara
Learning Solutions LTD's customers and partners, pursuant to
the terms and conditions of a separate agreement with Totara
Learning Solutions LTD or its affiliate.

If you do not have an agreement with Totara Learning Solutions
LTD, you may not access, use, modify, or distribute this software.
Please contact [licensing@totaralearning.com] for more information.

@author Amjad Ali <amjad.ali@totaralearning.com>
@package ml_recommender
@deprecated since Totara 17.0 ml_recommender has been deprecated.
"""

import pandas as pd
import os


class DataReader:
    """
    This is a conceptual representation of the process to read the data that was
    exported by the Totara instance
    """

    def __init__(self, data_home=None):
        """
        Class constructor method
        :param data_home: Full path of the directory that contains the data exported by
            the Totara instance
        :type data_home: str
        """
        self.data_home = data_home

    def read_tenants(self):
        """
        This method reads the `tenants.csv` file and reruns a pandas DataFrame
        :return: A pandas DataFrame
        """
        file_path = os.path.join(self.data_home, "tenants.csv")
        tenants = pd.read_csv(filepath_or_buffer=file_path, sep=",", encoding="utf-8")
        return tenants

    def read_users_file(self, tenant=0):
        """
        This method reads the `user_data` file of the given tenant and returns a pandas
        DataFrame
        :param tenant: The tenant id whose data needs to be processed, defaults to '0'
        :type tenant: str, optional
        :return: A pandas DataFrame
        """
        file_path = os.path.join(self.data_home, f"user_data_{tenant}.csv")
        users_data = pd.read_csv(
            filepath_or_buffer=file_path, sep=",", encoding="utf-8", index_col="user_id"
        )
        return users_data

    def read_items_file(self, tenant=0):
        """
        This method reads the `item_data` file of the given tenant and returns a pandas
        DataFrame
        :param tenant: The tenant id whose data needs to be processed, defaults to '0'
        :type tenant: str, optional
        :return: A pandas DataFrame
        """
        file_path = os.path.join(self.data_home, f"item_data_{tenant}.csv")
        items_data = pd.read_csv(
            filepath_or_buffer=file_path, sep=",", encoding="utf-8", index_col="item_id"
        )
        return items_data

    def read_interactions_file(self, tenant=0):
        """
        This method reads the `user_interactions` data file of the given tenant and
        returns a pandas DataFrame
        :param tenant: The tenant id whose data needs to be processed, defaults to '0'
        :type tenant: str, optional
        :return: A pandas DataFrame
        """
        file_path = os.path.join(self.data_home, f"user_interactions_{tenant}.csv")
        interactions = pd.read_csv(
            filepath_or_buffer=file_path, sep=",", encoding="utf-8"
        )
        return interactions
