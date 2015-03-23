<?php
namespace Visol\Outdatedpagesfinder\Tree\Pagetree;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Backend\Tree\Pagetree\Commands;
use TYPO3\CMS\Backend\Utility\IconUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Data Provider of the Page Tree
 *
 * @author Stefan Galinski <stefan.galinski@gmail.com>
 */
class ExtdirectTreeDataProvider extends \TYPO3\CMS\Backend\Tree\Pagetree\ExtdirectTreeDataProvider {

	/**
	 * Data Provider
	 *
	 * @var \Visol\Outdatedpagesfinder\Tree\Pagetree\DataProvider
	 */
	protected $dataProvider = NULL;

	/**
	 * Sets the data provider
	 *
	 * @return void
	 */
	protected function initDataProvider() {
		/** @var $dataProvider \Visol\Outdatedpagesfinder\Tree\Pagetree\DataProvider */
		$dataProvider = GeneralUtility::makeInstance('Visol\\Outdatedpagesfinder\\Tree\\Pagetree\\DataProvider');
		$this->setDataProvider($dataProvider);
	}

	/**
	 * Returns a tree that only contains elements that match the given search string
	 *
	 * @param integer $nodeId
	 * @param stdClass $nodeData
	 * @param integer $filterValue
	 * @return array
	 */
	public function getAgeFilteredTree($nodeId, $nodeData, $filterValue) {
		if (strval($filterValue) === 0) {
			return array();
		}
		/** @var $node \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode */
		$node = GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Tree\\Pagetree\\PagetreeNode', (array) $nodeData);
		$this->initDataProvider();
		if ($nodeId === 'root') {
			$nodeCollection = $this->dataProvider->getTreeMounts($filterValue);
		} else {
			$nodeCollection = $this->dataProvider->getFilteredNodes($node, $filterValue, $node->getMountPoint());
		}
		return $nodeCollection->toArray();
	}

	/**
	 * Returns the language labels, sprites and configuration options for the pagetree
	 *
	 * @return array
	 */
	public function loadResources() {
		$configuration = parent::loadResources();
		$file = 'LLL:EXT:outdatedpagesfinder/Resources/Private/Language/Module/locallang.xlf:';
		$additionalConfiguration = array(
			'LLL' => array(
				'ageFilter' => $GLOBALS['LANG']->sL($file . 'tree.ageFilter', TRUE),
				'ageFilterAll' => $GLOBALS['LANG']->sL($file . 'tree.ageFilter.all', TRUE),
				'ageFilter3' => $GLOBALS['LANG']->sL($file . 'tree.ageFilter.3', TRUE),
				'ageFilter6' => $GLOBALS['LANG']->sL($file . 'tree.ageFilter.6', TRUE),
				'ageFilter9' => $GLOBALS['LANG']->sL($file . 'tree.ageFilter.9', TRUE),
				'ageFilter12' => $GLOBALS['LANG']->sL($file . 'tree.ageFilter.12', TRUE),
			),
			'Sprites' => array(
				'AgeFilter' => IconUtility::getSpriteIconClasses('actions-document-history-open'),
			)
		);
		\TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule($configuration, $additionalConfiguration);
		return $configuration;
	}

}
