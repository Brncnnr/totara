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

import unittest
from pathlib import Path

from subroutines.arg_parser import ArgParser


class TestArgParser(unittest.TestCase):
    """
    The test object to tests units of the `ArgParser` class
    """

    def setUp(self):
        """
        Hook method for setting up the test fixture before exercising it
        """
        parser_obj = ArgParser()
        self.parser = parser_obj.set_args()

    def test_query_str(self):
        """
        This method tests if the command line argument `--query` is configured properly
        """
        valid_query = ["mf", "partial", "hybrid"]
        for q in valid_query:
            args = self.parser.parse_args(
                [
                    "--query",
                    q,
                    "--result_count_user",
                    "10",
                    "--result_count_item",
                    "10",
                    "--threads",
                    "2",
                    "--data_path",
                    "/data",
                ]
            )
            self.assertEqual(args.query, q)
        invalid_query = ["10", "other"]
        for q in invalid_query:
            with self.assertRaises(SystemExit):
                self.parser.parse_args(
                    [
                        "--query",
                        q,
                        "--result_count_user",
                        "10",
                        "--result_count_item",
                        "10",
                        "--threads",
                        "2",
                        "--data_path",
                        "/data",
                    ]
                )

    def test_user_counter(self):
        """
        This method tests if the command line argument `--result_count_user` is
        configured properly
        """
        valid_user_counter = ["10", "100"]
        for user in valid_user_counter:
            args = self.parser.parse_args(
                [
                    "--query",
                    "mf",
                    "--result_count_user",
                    user,
                    "--result_count_item",
                    "10",
                    "--threads",
                    "2",
                    "--data_path",
                    "/data",
                ]
            )
            self.assertEqual(args.result_count_user, int(user))
        invalid_user_counter = ["ten", "hundred"]
        for user in invalid_user_counter:
            with self.assertRaises(SystemExit):
                self.parser.parse_args(
                    [
                        "--query",
                        "mf",
                        "--result_count_user",
                        user,
                        "--result_count_item",
                        "10",
                        "--threads",
                        "2",
                        "--data_path",
                        "/data",
                    ]
                )

    def test_item_counter(self):
        """
        This method tests if the command line argument `--result_count_item` is
        configured properly
        """
        valid_item_counter = ["10", "100"]
        for item in valid_item_counter:
            args = self.parser.parse_args(
                [
                    "--query",
                    "mf",
                    "--result_count_user",
                    "10",
                    "--result_count_item",
                    item,
                    "--threads",
                    "2",
                    "--data_path",
                    "/data",
                ]
            )
            self.assertEqual(args.result_count_item, int(item))
        invalid_item_counter = ["ten", "hundred"]
        for item in invalid_item_counter:
            with self.assertRaises(SystemExit):
                self.parser.parse_args(
                    [
                        "--query",
                        "mf",
                        "--result_count_user",
                        "10",
                        "--result_count_item",
                        item,
                        "--threads",
                        "2",
                        "--data_path",
                        "/data",
                    ]
                )

    def test_threads_counter(self):
        """
        This method tests if the command line argument `--thread` is configured properly
        """
        valid_thread_counter = ["2", "10"]
        for thread in valid_thread_counter:
            args = self.parser.parse_args(
                [
                    "--query",
                    "mf",
                    "--result_count_user",
                    "10",
                    "--result_count_item",
                    "10",
                    "--threads",
                    thread,
                    "--data_path",
                    "/data",
                ]
            )
            self.assertEqual(args.threads, int(thread))
        invalid_thread_counter = ["two", "ten"]
        for thread in invalid_thread_counter:
            with self.assertRaises(SystemExit):
                self.parser.parse_args(
                    [
                        "--query",
                        "mf",
                        "--result_count_user",
                        "10",
                        "--result_count_item",
                        "10",
                        "--threads",
                        thread,
                        "--data_path",
                        "/data",
                    ]
                )

    def test_path_str(self):
        """
        This method tests if the command line argument `--data_path` is configured
        properly
        """
        valid_path = ["/data", "/data/data"]
        for path in valid_path:
            args = self.parser.parse_args(
                [
                    "--query",
                    "mf",
                    "--result_count_user",
                    "10",
                    "--result_count_item",
                    "10",
                    "--threads",
                    "2",
                    "--data_path",
                    path,
                ]
            )
            self.assertEqual(args.data_path, Path(path))
