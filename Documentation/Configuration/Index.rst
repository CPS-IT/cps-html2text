.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


.. _configuration:

Configuration Reference
=======================

Most of the configuration is performed using TypoScript.

.. _configuration-typoscript:

TypoScript Constants
--------------------

All the properties are under ``plugin.tx_cbink``.

.. container::

    ====================== ============ ================================================= ========================================
    Constant               Type         Description                                       Default value
    ====================== ============ ================================================= ========================================
    view.templateRootPath  file path    Path to template files                            EXT:cps_html2text/Resources/Private/Templates/
    view.partialRootPath   file path    Path to partial files                             EXT:cps_html2text/Resources/Private/Partials/
    view.layoutRootPath    file path    Path to layout files                              EXT:cps_html2text/Resources/Private/Layouts/
    config.absRefPrefix    absolute url Absolute URL of the website in plain text
    ====================== ============ ================================================= ========================================


TypoScript Setup
----------------

The extension provides an example to render any page in plain text. It has page **type=999** and uses
the :ref:`t3tsref:stdwrap-postuserfunc` to convert the HTML output into plain text.

html2text configuration
"""""""""""""""""""""""

The HTML to plain text converter is configured under ``plaintext_page.10.stdWrap.postUserFunc``.

ignoreLibXmlErrors
    If not set

ignoreTags
    A list of HTML tags to ignore (content of those tags is stripped)

blockElements
    A list of HTML tags that are considered to be block elements (line break will be inserted before and after tag content)

*any HTML tag*
    A :ref:`t3tsref:stdWrap` that can be used to transform the content of this tag. See provided TypoScript setup for examples.

