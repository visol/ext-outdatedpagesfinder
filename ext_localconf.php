<?php
defined('TYPO3_MODE') or die();

if (TYPO3_MODE === 'BE') {
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerExtDirectComponent('TYPO3.Components.PageTree.DataProvider', 'Visol\\Outdatedpagesfinder\\Tree\\Pagetree\\ExtdirectTreeDataProvider', 'web', 'user,group');
}