.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


.. _admin-manual:

Administrator Manual
====================



.. _admin-installation:

Installation
------------

This extension does not have any special dependencies.

You should install the static template. It can be included at the root of
you website, or only in the folder used for generating plain text content.

.. _admin-configuration:

Configuration
-------------

The important configuration to be performed is to specify the absolute URL
for links in emails. You can do it in the TypoScript constant editor.

Absolute URL for links [plugin.tx_cpshtml2text.config.absRefPrefix]:
Url of you website (e.g. http://example.com/). Do not forget the trailing /.

See :ref:`configuration` for more details.

Direct Mail
^^^^^^^^^^^

cps_html2text extension configures a special page type for plain text (type=99) rendering.
