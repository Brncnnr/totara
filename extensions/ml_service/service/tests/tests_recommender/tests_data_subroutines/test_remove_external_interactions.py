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

from service.recommender.data_subroutines.remove_external_interactions import (
    RemoveExternalInteractions,
)
from service.tests.tests_recommender.generate_data import GenerateData


class TestRemoveExternalInteractions(unittest.TestCase):
    """
    This class is the test object to test units of the class
    `RemoveExternalInteractions`
    """

    def setUp(self):
        """
        Hook method for setting up the fixture before exercising it
        """
        data_generator1 = GenerateData(n_users=10, n_items=20)
        data_generator2 = GenerateData(n_users=15, n_items=25)
        self.users_df = data_generator1.get_users()
        self.items_df = data_generator1.get_items()
        interactions_df = data_generator2.get_interactions()
        self.rem_ext_int = RemoveExternalInteractions(
            users_df=self.users_df,
            items_df=self.items_df,
            interactions_df=interactions_df,
        )

    def test_clean_interactions(self):
        """
        This method tests if the users and items from the `interactions_df` have been
        removed that were not found in the datasets `users_df` and `items_df`,
        respectively
        """
        computed_cleaned_interactions = self.rem_ext_int.clean_interactions()
        self.assertTrue(
            set(computed_cleaned_interactions.user_id.tolist()).issubset(
                set(self.users_df.index.tolist())
            )
        )
        self.assertTrue(
            set(computed_cleaned_interactions.item_id.tolist()).issubset(
                set(self.items_df.index.tolist())
            )
        )
