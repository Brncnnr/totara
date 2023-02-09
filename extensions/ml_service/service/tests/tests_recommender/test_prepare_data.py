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
from service.recommender.prepare_data import PrepareData
from service.tests.tests_recommender.generate_data import GenerateData


class TestPrepareData(unittest.TestCase):
    """
    This test object is to test the units of `PrepareData` class in file
    `service.recommender.prepare_data`
    """

    def setUp(self) -> None:
        """
        Hook method to set up the fixtures before exercising it
        """
        data_generator = GenerateData(n_tenants=1)
        self.data = {
            "tenants": data_generator.get_tenants(),
            "user_interactions_0": data_generator.get_interactions(),
            "user_data_0": data_generator.get_users(),
            "item_data_0": data_generator.get_items(),
        }

    def test_get_tenants(self) -> None:
        """
        This method tests if the list of tenants is obtained exactly as expected
        """
        prepare_data = PrepareData(data=self.data)
        computed_tenants_list = prepare_data.get_tenants()

        self.assertIsInstance(
            obj=computed_tenants_list,
            cls=list,
            msg=(
                "The method has returned an object of type "
                f"{type(computed_tenants_list)} while it was expected to be an instance"
                " of <class 'list'>"
            ),
        )

        self.assertEqual(
            first=len(computed_tenants_list),
            second=1,
            msg=(
                "The number of returned tenants from the method was expected to be 1 "
                f"while it is {len(computed_tenants_list)}"
            ),
        )

    def test_get_tenant_data(self) -> None:
        """
        This method tests if the tenants data has been pre-processed correctly in the
        `get_tenants_data` method of the `PrepareData` class
        """
        prepare_data = PrepareData(data=self.data, query="mf")
        computed_tenants_list = prepare_data.get_tenants()

        tenants_data = prepare_data.get_tenant_data(tenant=computed_tenants_list[0])

        self.assertIsInstance(
            obj=tenants_data,
            cls=dict,
            msg=(
                "The returned value from the method is an instance of "
                f"{type(tenants_data)} while it was expected to be an instance of "
                "<class 'dict'>"
            ),
        )

        self.assertIn(
            member="users_processed_data",
            container=tenants_data,
            msg=(
                "The returned dictionary does not contain the key "
                "'users_processed_data'"
            ),
        )

        self.assertIn(
            member="items_processed_data",
            container=tenants_data,
            msg=(
                "The returned dictionary does not contain the key "
                "'items_processed_data'"
            ),
        )

        self.assertIn(
            member="interactions",
            container=tenants_data,
            msg="The returned dictionary does not contain the key 'interactions'",
        )

        self.assertIn(
            member="mappings",
            container=tenants_data,
            msg="The returned dictionary does not contain the key 'mappings'",
        )

        self.assertIn(
            member="item_type_map",
            container=tenants_data,
            msg="The returned dictionary does not contain the key 'item_type_map'",
        )
