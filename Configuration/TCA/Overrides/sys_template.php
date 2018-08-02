<?php
defined('TYPO3_MODE') or die();

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
    'cps_html2text',
    'Configuration/TypoScript/',
    'CPS HTML to text'
);
