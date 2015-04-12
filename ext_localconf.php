<?php

defined('TYPO3_MODE') or die();

//if (TYPO3_MODE === 'BE') {
//	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerExtDirectComponent('TYPO3.Components.PageTree.DataProvider', 'Visol\\Outdatedpagesfinder\\Tree\\Pagetree\\ExtdirectTreeDataProvider', 'web', 'user,group');
//}

// add own backend items
$GLOBALS['TYPO3_CONF_VARS']['typo3/backend.php']['additionalBackendItems'][] =
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('outdatedpagesfinder', 'backend_ext.php');

// Xclasses
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\CMS\Backend\Tree\Pagetree\ExtdirectTreeDataProvider'] =
	array('className' => 'Visol\Outdatedpagesfinder\Tree\Pagetree\ExtdirectTreeDataProvider');

$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\CMS\Backend\Tree\Pagetree\DataProvider'] =
	array('className' => 'Visol\Outdatedpagesfinder\Tree\Pagetree\DataProvider');
