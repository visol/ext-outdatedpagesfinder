<?php
defined('TYPO3_MODE') or die();

if (TYPO3_MODE === 'BE') {
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addNavigationComponent('web', 'typo3-pagetree', 'outdatedpagesfinder');
}