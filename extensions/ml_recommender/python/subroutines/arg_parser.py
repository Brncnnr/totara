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

import argparse
from pathlib import Path


class ArgParser:
    """
    This is a conceptual representation of setting up the commandline arguments required
    to run the recommender engine
    """

    def __init__(self):
        """
        The constructor method
        """
        self.parser = argparse.ArgumentParser(
            description="Totara Engage recommendations"
        )

    def set_args(self):
        """
        This method sets up the required commandline arguments with the correct type and
        defaults, etc.
        :return: An object of the `argparse.ArgumentParser` class with defined
            command-line named arguments
        :rtype: An object of the `argparse.ArgumentParser` class
        """
        the_parser = self.parser
        the_parser.add_argument(
            "--query",
            help="""
                The type of query to run (mf = matrix factorisation, partial = matrix
                factorisation & item metadata, hybrid = matrix factorisation &
                content-filtering
                """,
            required=True,
            choices=["mf", "partial", "hybrid"],
        )
        the_parser.add_argument(
            "--result_count_user",
            help="Number of items-to-user recommendations to return",
            required=True,
            type=int,
        )
        the_parser.add_argument(
            "--result_count_item",
            help="Number of items-to-item recommendations to return",
            required=True,
            type=int,
        )
        the_parser.add_argument(
            "--threads",
            help="Number of parallel threads to use (should be <= physical cores)",
            required=True,
            type=int,
        )
        the_parser.add_argument(
            "--data_path", help="Path to data directory", required=True, type=Path
        )
        return the_parser
