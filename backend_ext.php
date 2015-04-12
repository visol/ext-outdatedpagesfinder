<?php

if (is_object($TYPO3backend)) {
	/** @var \TYPO3\CMS\Backend\Template\DocumentTemplate $template */
	$template = $GLOBALS['TBE_TEMPLATE'];
    $pageRenderer = $template->getPageRenderer();
    $path = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('outdatedpagesfinder');
    $pageRenderer->addJsFile($path . 'Resources/Public/Javascript/PagetreeFilterExtension.js');

	$pageRenderer->addCssFile($path . 'Resources/Public/StyleSheets/PagetreeFilterStyles.css');
}