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
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Page tree data provider.
 *
 * @author Stefan Galinski <stefan.galinski@gmail.com>
 */
class DataProvider extends \TYPO3\CMS\Backend\Tree\Pagetree\DataProvider {

	/**
	 * Returns a node collection of filtered nodes
	 *
	 * @param \TYPO3\CMS\Backend\Tree\TreeNode $node
	 * @param integer $filterValue
	 * @param integer $mountPoint
	 * @return \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNodeCollection the filtered nodes
	 */
	public function getFilteredNodes(\TYPO3\CMS\Backend\Tree\TreeNode $node, $filterValue, $mountPoint = 0) {

		/** @var $nodeCollection \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNodeCollection */
		$nodeCollection = GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Tree\\Pagetree\\PagetreeNodeCollection');
		$records = $this->getSubpages(-1, $filterValue);

		if (!is_array($records) || !count($records)) {
			return $nodeCollection;
		} elseif (count($records) > 500) {
			return $nodeCollection;
		}

		// check no temporary mountpoint is used
		$mountPoints = (int)$GLOBALS['BE_USER']->uc['pageTree_temporaryMountPoint'];
		if (!$mountPoints) {
			$mountPoints = array_map('intval', $GLOBALS['BE_USER']->returnWebmounts());
			$mountPoints = array_unique($mountPoints);
		} else {
			$mountPoints = array($mountPoints);
		}
		$nodeId = (int)$node->getId();
		$processedRecordIds = array();
		foreach ($records as $record) {
			if ((int)$record['t3ver_wsid'] !== (int)$GLOBALS['BE_USER']->workspace && (int)$record['t3ver_wsid'] !== 0) {
				continue;
			}
			$liveVersion = BackendUtility::getLiveVersionOfRecord('pages', $record['uid'], 'uid');
			if ($liveVersion !== NULL) {
				$record = $liveVersion;
			}

			$record = Commands::getNodeRecord($record['uid'], FALSE);
			if ((int)$record['pid'] === -1
				|| in_array($record['uid'], $this->hiddenRecords)
				|| in_array($record['uid'], $processedRecordIds)
			) {
				continue;
			}
			$processedRecordIds[] = $record['uid'];

			$rootline = BackendUtility::BEgetRootLine($record['uid'], '', $GLOBALS['BE_USER']->workspace != 0);
			$rootline = array_reverse($rootline);
			if ($nodeId === 0) {
				array_shift($rootline);
			}
			if ($mountPoints != array(0)) {
				$isInsideMountPoints = FALSE;
				foreach ($rootline as $rootlineElement) {
					if (in_array((int)$rootlineElement['uid'], $mountPoints, TRUE)) {
						$isInsideMountPoints = TRUE;
						break;
					}
				}
				if (!$isInsideMountPoints) {
					continue;
				}
			}
			$reference = $nodeCollection;
			$inFilteredRootline = FALSE;
			$amountOfRootlineElements = count($rootline);
			for ($i = 0; $i < $amountOfRootlineElements; ++$i) {
				$rootlineElement = $rootline[$i];
				$rootlineElement['uid'] = (int)$rootlineElement['uid'];
				$isInWebMount = (int)$GLOBALS['BE_USER']->isInWebMount($rootlineElement['uid']);
				if (!$isInWebMount
					|| ($rootlineElement['uid'] === (int)$mountPoints[0]
						&& $rootlineElement['uid'] !== $isInWebMount)
				) {
					continue;
				}
				if ((int)$rootlineElement['pid'] === $nodeId
					|| $rootlineElement['uid'] === $nodeId
					|| ($rootlineElement['uid'] === $isInWebMount
						&& in_array($rootlineElement['uid'], $mountPoints, TRUE))
				) {
					$inFilteredRootline = TRUE;
				}
				if (!$inFilteredRootline || $rootlineElement['uid'] === $mountPoint) {
					continue;
				}
				$rootlineElement = Commands::getNodeRecord($rootlineElement['uid'], FALSE);
				$ident = (int)$rootlineElement['sorting'] . (int)$rootlineElement['uid'];
				if ($reference && $reference->offsetExists($ident)) {
					/** @var $refNode \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode */
					$refNode = $reference->offsetGet($ident);
					$refNode->setExpanded(TRUE);
					$refNode->setLeaf(FALSE);
					$reference = $refNode->getChildNodes();
					if ($reference == NULL) {
						$reference = GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Tree\\Pagetree\\PagetreeNodeCollection');
						$refNode->setChildNodes($reference);
					}
				} else {
					$refNode = Commands::getNewNode($rootlineElement, $mountPoint);
					$replacement = '<span class="typo3-pagetree-filteringTree-highlight">$1</span>';
					$text = str_replace('$1', $refNode->getText(), $replacement);
					$refNode->setText($text, $refNode->getTextSourceField(), $refNode->getPrefix(), $refNode->getSuffix());
					/** @var $childCollection \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNodeCollection */
					$childCollection = GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Tree\\Pagetree\\PagetreeNodeCollection');
					if ($i + 1 >= $amountOfRootlineElements) {
						$childNodes = $this->getNodes($refNode, $mountPoint);
						foreach ($childNodes as $childNode) {
							/** @var $childNode \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode */
							$childRecord = $childNode->getRecord();
							$childIdent = (int)$childRecord['sorting'] . (int)$childRecord['uid'];
							$childCollection->offsetSet($childIdent, $childNode);
						}
						$refNode->setChildNodes($childNodes);
					}
					$refNode->setChildNodes($childCollection);
					$reference->offsetSet($ident, $refNode);
					$reference->ksort();
					$reference = $childCollection;
				}
			}
		}
		foreach ($this->processCollectionHookObjects as $hookObject) {
			/** @var $hookObject \TYPO3\CMS\Backend\Tree\Pagetree\CollectionProcessorInterface */
			$hookObject->postProcessFilteredNodes($node, $filterValue, $mountPoint, $nodeCollection);
		}
		return $nodeCollection;
	}

	/**
	 * Returns the page tree mounts for the current user
	 *
	 * @param integer $filterValue
	 * @return \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNodeCollection
	 */
	public function getTreeMounts($filterValue = 0) {
		/** @var $nodeCollection \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNodeCollection */
		$nodeCollection = GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Tree\\Pagetree\\PagetreeNodeCollection');
		$isTemporaryMountPoint = FALSE;
		$rootNodeIsVirtual = FALSE;
		$mountPoints = (int)$GLOBALS['BE_USER']->uc['pageTree_temporaryMountPoint'];
		if (!$mountPoints) {
			$mountPoints = array_map('intval', $GLOBALS['BE_USER']->returnWebmounts());
			$mountPoints = array_unique($mountPoints);
			if (!in_array(0, $mountPoints)) {
				$rootNodeIsVirtual = TRUE;
				// use a virtual root
				// the real mountpoints will be fetched in getNodes() then
				// since those will be the "subpages" of the virtual root
				$mountPoints = array(0);
			}
		} else {
			$isTemporaryMountPoint = TRUE;
			$mountPoints = array($mountPoints);
		}
		if (!count($mountPoints)) {
			return $nodeCollection;
		}
		foreach ($mountPoints as $mountPoint) {
			if ($mountPoint === 0) {
				$sitename = 'TYPO3';
				if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] !== '') {
					$sitename = $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'];
				}
				$record = array(
					'uid' => 0,
					'title' => $sitename
				);
				$subNode = Commands::getNewNode($record);
				$subNode->setLabelIsEditable(FALSE);
				if ($rootNodeIsVirtual) {
					$subNode->setType('virtual_root');
					$subNode->setIsDropTarget(FALSE);
				} else {
					$subNode->setType('pages_root');
					$subNode->setIsDropTarget(TRUE);
				}
			} else {
				if (in_array($mountPoint, $this->hiddenRecords)) {
					continue;
				}
				$record = $this->getRecordWithWorkspaceOverlay($mountPoint);
				if (!$record) {
					continue;
				}
				$subNode = Commands::getNewNode($record, $mountPoint);
				if ($this->showRootlineAboveMounts && !$isTemporaryMountPoint) {
					$rootline = Commands::getMountPointPath($record['uid']);
					$subNode->setReadableRootline($rootline);
				}
			}
			if (count($mountPoints) <= 1) {
				$subNode->setExpanded(TRUE);
				$subNode->setCls('typo3-pagetree-node-notExpandable');
			}
			$subNode->setIsMountPoint(TRUE);
			$subNode->setDraggable(FALSE);
			if ($filterValue === 0) {
				$childNodes = $this->getNodes($subNode, $mountPoint);
			} else {
				$childNodes = $this->getFilteredNodes($subNode, $filterValue, $mountPoint);
				$subNode->setExpanded(TRUE);
			}
			$subNode->setChildNodes($childNodes);
			$nodeCollection->append($subNode);
		}
		return $nodeCollection;
	}

	/**
	 * Returns the where clause for fetching pages
	 *
	 * @param integer $id
	 * @param integer $filterValue
	 * @return string
	 */
	protected function getWhereClause($id, $filterValue = 0) {
		$where = $GLOBALS['BE_USER']->getPagePermsClause(1) . BackendUtility::deleteClause('pages') . BackendUtility::versioningPlaceholderClause('pages');
		if (is_numeric($id) && $id >= 0) {
			$where .= ' AND pid= ' . $GLOBALS['TYPO3_DB']->fullQuoteStr((int)$id, 'pages');
		}

		$excludedDoktypes = $GLOBALS['BE_USER']->getTSConfigVal('options.pageTree.excludeDoktypes');
		if (!empty($excludedDoktypes)) {
			$excludedDoktypes = $GLOBALS['TYPO3_DB']->fullQuoteArray(GeneralUtility::intExplode(',', $excludedDoktypes), 'pages');
			$where .= ' AND doktype NOT IN (' . implode(',', $excludedDoktypes) . ')';
		}
		if ($filterValue > 0) {
			// $filterValue is the number of months
			$filterValue = strtotime($filterValue . ' months ago');
			$where .= ' AND tstamp < ' . $filterValue;
		}
		return $where;
	}

	/**
	 * Returns all sub-pages of a given id
	 *
	 * @param integer $id
	 * @param integer $filterValue
	 * @return array
	 */
	protected function getSubpages($id, $filterValue = 0) {
		$where = $this->getWhereClause($id, $filterValue);
		return $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid,t3ver_wsid', 'pages', $where, '', 'sorting', '', 'uid');
	}

}
