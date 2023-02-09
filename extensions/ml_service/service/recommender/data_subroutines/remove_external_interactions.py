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


class RemoveExternalInteractions:
    """
    This is a conceptual representation of the process to remove interactions of the
    users and the items from a tenant that are not found in that tenant's users data and
    items data, respectively
    """

    def __init__(self, users_df=None, items_df=None, interactions_df=None):
        """
        Class constructor method

        :param users_df: The users data exported from the Totara instance
        :type users_df: An instance of pandas DataFrame
        :param items_df: The items data exported from the Totara instance
        :type items_df: An instance of pandas DataFrame
        :param interactions_df: The interactions data as exported from the Totara
            instance
        :type interactions_df: An instance of pandas DataFrame
        """
        self.users_df = users_df
        self.items_df = items_df
        self.interactions_df = interactions_df

    def clean_interactions(self):
        """
        This method filters the provided interactions data for the users that are found
        in the provided users dataset and also for the items that are found in the
        provided dataset

        :return: Cleaned interactions dataset
        :rtype: An instance of pandas DataFrame
        """
        users_list = self.users_df.index.tolist()
        items_list = self.items_df.index.tolist()
        clean_users = self.interactions_df[
            self.interactions_df.user_id.isin(users_list)
        ]
        clean_users_items = clean_users[clean_users.item_id.isin(items_list)]
        clean_users_items.reset_index(drop=True, inplace=True)
        return clean_users_items
