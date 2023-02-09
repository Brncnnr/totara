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
from scipy.sparse import csr_matrix


from service.recommender.data_subroutines.text_encoder import TextEncoder


class TestTextEncoder(unittest.TestCase):
    """
    To test the units of TextEncoder class
    """

    def setUp(self) -> None:
        """
        Hook method to set up the fixtures before exercising it
        """
        self.encoder = TextEncoder()
        self.longMessage = False

    def test_remove_url(self) -> None:
        """
        To test the remove_url method of the TextEncoder class
        """
        test_doc = (
            "The http://example.co.nz and https://example.com/subpage are examples of "
            "urls"
        )
        clean_doc = "The  and  are examples of urls"
        returned_doc = self.encoder.remove_url(in_doc=test_doc)
        self.assertEqual(
            first=returned_doc,
            second=clean_doc,
            msg=(
                "The returned sentence from the 'TextEncoder.remove_url' method is\n"
                f"'{returned_doc}'\n"
                "while it was expected to be\n"
                f"'{clean_doc}'"
            ),
        )

    def test_remove_emails(self) -> None:
        """
        To test the remove_emails method of the TextEncoder class
        """
        test_doc = (
            "The example.man@example.co.nz and example@example.com are examples of "
            "email addresses"
        )
        clean_doc = "The and are examples of email addresses"
        returned_doc = self.encoder.remove_emails(in_doc=test_doc)
        self.assertEqual(
            first=returned_doc,
            second=clean_doc,
            msg=(
                "The returned sentence from the 'TextEncoder.remove_emails' method is\n"
                f"'{returned_doc}'\n"
                "while it was expected to be\n"
                f"'{clean_doc}'"
            ),
        )

    def test_remove_non_ascii(self) -> None:
        """
        To test the remove_non_ascii mehtod of the TextEncoder class
        """
        test_doc = "àa string withé fuünny charactersß."
        clean_doc = "aa string withe fuunny characters."
        returned_doc = self.encoder.remove_non_ascii(in_doc=test_doc)
        self.assertEqual(
            first=returned_doc,
            second=clean_doc,
            msg=(
                "The returned sentence from the 'TextEncoder.remove_non_ascii' method "
                f"is\n'{returned_doc}'\n"
                "while it was expected to be\n"
                f"'{clean_doc}'"
            ),
        )

    def test_remove_punctuations(self) -> None:
        """
        To test the remove_punctuations method of the TextEncoder class
        """
        test_doc = "string. With.!- Punctuation:"
        clean_doc = "string With Punctuation"
        returned_doc = self.encoder.remove_punctuations(in_doc=test_doc)
        self.assertEqual(
            first=returned_doc,
            second=clean_doc,
            msg=(
                "The returned sentence from the 'TextEncoder.remove_punctuations' "
                f"method is\n'{returned_doc}'\n"
                "while it was expected to be\n"
                f"'{clean_doc}'"
            ),
        )

    def test_remove_digits(self) -> None:
        """
        To test the remove_digits method of the TextEncoder class
        """
        test_doc = "string 25 with -100 digits"
        clean_doc = "string  with - digits"
        returned_doc = self.encoder.remove_digits(in_doc=test_doc)
        self.assertEqual(
            first=returned_doc,
            second=clean_doc,
            msg=(
                "The returned sentence from the 'TextEncoder.remove_digits' method is\n"
                f"'{returned_doc}'\n"
                "while it was expected to be\n"
                f"'{clean_doc}'"
            ),
        )

    def test_remove_stopwords(self) -> None:
        """
        To test the remove_stopwords method of the TextEncoder class
        """
        test_doc = "This string has some stopwords"
        clean_doc = "This string stopwords"
        returned_doc = self.encoder.remove_stopwords(in_doc=test_doc)
        self.assertEqual(
            first=returned_doc,
            second=clean_doc,
            msg=(
                "The returned sentence from the 'TextEncoder.remove_stopwords' method "
                f"is\n'{returned_doc}'\n"
                "while it was expected to be\n"
                f"'{clean_doc}'"
            ),
        )

    def test_clean_text(self) -> None:
        """
        To test the clean_text method of the TextEncoder class
        """
        test_doc = (
            "This.~-! iS the980 dIRTiest pOssiBLE  string from the man who has "
            "example.man@example.com as eMAIL àddreSS. hIS web addreSS IS "
            "https://example.com"
        )
        clean_doc = "dirtiest possible string email address web address "
        returned_doc = self.encoder.clean_text(doc=test_doc)
        self.assertEqual(
            first=returned_doc,
            second=clean_doc,
            msg=(
                "The returned sentence from the 'TextEncoder.clean_text' method is\n"
                f"'{returned_doc}'\n"
                "while it was expected to be\n"
                f"'{clean_doc}'"
            ),
        )

    def test_docs_empty(self) -> None:
        """
        To test if the docs_empty method of the TextEncoder class can accurately detect
        empty documents
        """
        test_docs_nonempty = [
            "",
            "bla bla",
            "",
        ]
        test_docs_empty = [
            "",
            " ",
            "",
        ]
        response_nonempty = self.encoder.docs_empty(docs=test_docs_nonempty)
        response_empty = self.encoder.docs_empty(docs=test_docs_empty)

        self.assertFalse(
            expr=response_nonempty,
            msg="The response from the 'TextEncoder.docs_empty' is not 'False'",
        )

        self.assertTrue(
            expr=response_empty,
            msg="The response from the 'TextEncoder.docs_empty' is not 'False'",
        )

    def test_encode_documents(self) -> None:
        """
        To test if the encode_documents.TextEncoder returns expected response both with
        the empty documents and non-empty documents
        """
        test_docs_nonempty = [
            "",
            "bla bla",
            "another example sentence",
        ]
        test_docs_empty = [
            "1",
            " ",
            ".!",
        ]
        response_nonempty = self.encoder.encode_documents(documents=test_docs_nonempty)
        response_empty = self.encoder.encode_documents(documents=test_docs_empty)

        self.assertIsInstance(
            obj=response_nonempty,
            cls=dict,
            msg=(
                "The returned response from the 'TextEncoder.encode_documents' for a "
                f"non-empty document list is {type(response_nonempty)} while it was "
                "expected to be <class 'dict'>"
            ),
        )

        self.assertIsInstance(
            obj=response_nonempty["features"],
            cls=list,
            msg=(
                "The returned response from the 'TextEncoder.encode_documents' for a "
                f"non-empty document list is {type(response_nonempty['features'])} "
                "while it was expected to be <class 'list'>"
            ),
        )

        self.assertIsInstance(
            obj=response_nonempty["vectors"],
            cls=csr_matrix,
            msg=(
                "The returned response from the 'TextEncoder.encode_documents' for a "
                f"non-empty document list is {type(response_nonempty['vectors'])} while"
                " it was expected to be <class 'scipy.sparse.csr.csr_sparse'>"
            ),
        )

        self.assertIsInstance(
            obj=response_empty,
            cls=dict,
            msg=(
                "The returned response from the 'TextEncoder.encode_documents' for an "
                f"empty document list is {type(response_nonempty)} while it was "
                f"expected to be <class 'dict'>"
            ),
        )

        self.assertIsInstance(
            obj=response_empty["features"],
            cls=list,
            msg=(
                "The returned response from the 'TextEncoder.encode_documents' for an "
                f"empty document list is {type(response_empty['features'])} while it "
                f"was expected to be <class 'list'>"
            ),
        )

        self.assertIsInstance(
            obj=response_empty["vectors"],
            cls=csr_matrix,
            msg=(
                "The returned response from the 'TextEncoder.encode_documents' for an "
                f"empty document list is {type(response_empty['vectors'])} while it was"
                " expected to be <class 'scipy.sparse.csr.csr_sparse'>"
            ),
        )
