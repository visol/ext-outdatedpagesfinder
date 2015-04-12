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

use TYPO3\CMS\Core\Utility\ArrayUtility;

/**
 * Data Provider of the Page Tree
 *
 * @author Stefan Galinski <stefan.galinski@gmail.com>
 */
class ExtdirectTreeDataProvider extends \TYPO3\CMS\Backend\Tree\Pagetree\ExtdirectTreeDataProvider {
	/**
	 * Returns the language labels, sprites and configuration options for the pagetree
	 *
	 * @return array
	 */
	public function loadResources() {
		$configuration = parent::loadResources();
		$file = 'LLL:EXT:outdatedpagesfinder/Resources/Private/Language/Module/locallang.xlf:';
		$additionalConfiguration = [
			'LLL' => [
				'ageFilter' => $GLOBALS['LANG']->sL($file . 'tree.ageFilter', TRUE),
				'ageFilterAll' => $GLOBALS['LANG']->sL($file . 'tree.ageFilter.all', TRUE),
				'ageFilter3' => $GLOBALS['LANG']->sL($file . 'tree.ageFilter.3', TRUE),
				'ageFilter6' => $GLOBALS['LANG']->sL($file . 'tree.ageFilter.6', TRUE),
				'ageFilter9' => $GLOBALS['LANG']->sL($file . 'tree.ageFilter.9', TRUE),
				'ageFilter12' => $GLOBALS['LANG']->sL($file . 'tree.ageFilter.12', TRUE),
			],
		];
		ArrayUtility::mergeRecursiveWithOverrule($configuration, $additionalConfiguration);

		return $configuration;
	}
}