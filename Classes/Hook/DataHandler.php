<?php
namespace Visol\Outdatedpagesfinder\Hook;
/***************************************************************
 *  Copyright notice
 *  (c) 2015 Lorenz Ulrich <lorenz.ulrich@visol.ch>
 *  All rights reserved
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

class DataHandler {

	/**
	 * @param $status
	 * @param $table
	 * @param $id
	 * @param array $fieldArray
	 * @param $parentObject \TYPO3\CMS\Core\DataHandling\DataHandler
	 */
	public function processDatamap_afterDatabaseOperations($status, $table, $id, $fieldArray, $parentObject) {
		if ($table === 'tt_content') {
			if ($status === 'new') {
				$parentPage = $fieldArray['pid'];
				$this->updateSysLastchanged($parentPage);
			} elseif (MathUtility::canBeInterpretedAsInteger($id)) {
				$parentPage = BackendUtility::getRecord('tt_content', (int)$id, 'pid');
				$this->updateSysLastchanged($parentPage['pid']);
			}
		}
	}

	/**
	 * @param string $command
	 * @param string $table
	 * @param int $id
	 * @param string $value
	 * @param $parentObject \TYPO3\CMS\Core\DataHandling\DataHandler
	 */
	public function processCmdmap_preProcess($command, &$table, $id, $value, $parentObject) {
		if ($table === 'tt_content') {
			if (MathUtility::canBeInterpretedAsInteger($id)) {
				$parentPage = BackendUtility::getRecord('tt_content', (int)$id, 'pid');
				$this->updateSysLastchanged($parentPage['pid']);
			}
		}
	}

	/**
	 * @param $pageUid
	 */
	protected function updateSysLastchanged($pageUid) {
		/** @var \TYPO3\CMS\Core\Database\DatabaseConnection $databaseConnection */
		$databaseConnection = $GLOBALS['TYPO3_DB'];
		$databaseConnection->exec_UPDATEquery(
			'pages',
			'uid=' . (int)$pageUid,
			array('SYS_LASTCHANGED' => time())
		);
	}

}