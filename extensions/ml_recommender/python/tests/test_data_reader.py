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

import os
import unittest
from unittest.mock import patch

from subroutines.data_reader import DataReader


class TestDataReader(unittest.TestCase):
    """
    This class is the test object to tests the units of the `DataReader` class
    """

    def setUp(self):
        """
        Hook method for setting up the fixture before exercising it
        """
        self.data_home = "/test_directory"
        self.reader = DataReader(data_home=self.data_home)

    @patch("subroutines.data_reader.pd.read_csv")
    def test_read_tenants(self, mock_read_csv):
        """
        This method tests if the `read_tenants` method of the `DataReader` class calls
        the pandas' read_csv method of the DataFrame class is called correctly with the
        correct arguments
        """
        test_return = "tested"
        mock_read_csv.return_value = test_return
        tenants_read = self.reader.read_tenants()
        test_file = os.path.join(self.data_home, "tenants.csv")
        mock_read_csv.assert_called_once_with(
            filepath_or_buffer=test_file, sep=",", encoding="utf-8"
        )
        self.assertEqual(tenants_read, test_return)

    @patch("subroutines.data_reader.pd.read_csv")
    def test_read_users_file(self, mock_read_csv):
        """
        This method tests if the `read_users_file` method of the `DataReader` class
        calls the pandas' read_csv method of the DataFrame class is called correctly
        with the correct arguments
        """
        test_return = "tested"
        dummy_tenant = 0
        mock_read_csv.return_value = test_return
        users_read = self.reader.read_users_file(tenant=dummy_tenant)
        test_file = os.path.join(self.data_home, f"user_data_{dummy_tenant}.csv")
        mock_read_csv.assert_called_once_with(
            filepath_or_buffer=test_file, sep=",", encoding="utf-8", index_col="user_id"
        )
        self.assertEqual(users_read, test_return)

    @patch("subroutines.data_reader.pd.read_csv")
    def test_read_items_file(self, mock_read_csv):
        """
        This method tests if the `read_items_file` method of the `DataReader` class
        calls the pandas' read_csv method of the DataFrame class is called correctly
        with the correct arguments
        """
        test_return = "tested"
        dummy_tenant = 0
        mock_read_csv.return_value = test_return
        items_read = self.reader.read_items_file(tenant=dummy_tenant)
        test_file = os.path.join(self.data_home, f"item_data_{dummy_tenant}.csv")
        mock_read_csv.assert_called_once_with(
            filepath_or_buffer=test_file, sep=",", encoding="utf-8", index_col="item_id"
        )
        self.assertEqual(items_read, test_return)

    @patch("subroutines.data_reader.pd.read_csv")
    def test_read_interactions_file(self, mock_read_csv):
        """
        This method tests if the `read_interactions_file` method of the `DataReader`
        class calls the pandas' read_csv method of the DataFrame class is called
        correctly with the correct arguments
        """
        test_return = "tested"
        dummy_tenant = 0
        mock_read_csv.return_value = test_return
        interactions_read = self.reader.read_interactions_file(tenant=dummy_tenant)
        test_file = os.path.join(
            self.data_home, f"user_interactions_{dummy_tenant}.csv"
        )
        mock_read_csv.assert_called_once_with(
            filepath_or_buffer=test_file, sep=",", encoding="utf-8"
        )
        self.assertEqual(interactions_read, test_return)
