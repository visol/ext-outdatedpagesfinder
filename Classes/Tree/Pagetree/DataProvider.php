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
	 * @param string $searchFilter
	 * @param integer $mountPoint
	 * @return \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNodeCollection the filtered nodes
	 */
	public function getFilteredNodes(\TYPO3\CMS\Backend\Tree\TreeNode $node, $searchFilter, $mountPoint = 0) {
		/** @var $nodeCollection \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNodeCollection */
		$nodeCollection = GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Tree\\Pagetree\\PagetreeNodeCollection');
		$records = $this->getSubpages(-1, $searchFilter);
		if (!is_array($records) || !count($records)) {
			return $nodeCollection;
		} elseif (count($records) > 10000) { // was 500
			return $nodeCollection;
		}
		// check no temporary mountpoint is used
		$mountPoints = (int) $GLOBALS['BE_USER']->uc['pageTree_temporaryMountPoint'];
		if (!$mountPoints) {
			$mountPoints = array_map('intval', $GLOBALS['BE_USER']->returnWebmounts());
			$mountPoints = array_unique($mountPoints);
		} else {
			$mountPoints = [$mountPoints];
		}
		$isNumericSearchFilter = is_numeric($searchFilter) && $searchFilter > 0;
		list($ageFilter, $searchFilter) = $this->getFilters($searchFilter);
		$searchFilterQuoted = ($searchFilter !== '' ? preg_quote($searchFilter, '/') : '');
		$nodeId = (int) $node->getId();
		$processedRecordIds = [];
		foreach ($records as $record) {
			if ((int) $record['t3ver_wsid'] !== (int) $GLOBALS['BE_USER']->workspace && (int) $record['t3ver_wsid'] !== 0) {
				continue;
			}
			$liveVersion = BackendUtility::getLiveVersionOfRecord('pages', $record['uid'], 'uid');
			if ($liveVersion !== NULL) {
				$record = $liveVersion;
			}

			$record = Commands::getNodeRecord($record['uid'], FALSE);
			if ((int) $record['pid'] === -1
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
			if ($mountPoints != [0]) {
				$isInsideMountPoints = FALSE;
				foreach ($rootline as $rootlineElement) {
					if (in_array((int) $rootlineElement['uid'], $mountPoints, TRUE)) {
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
				$rootlineElement['uid'] = (int) $rootlineElement['uid'];
				$isInWebMount = (int) $GLOBALS['BE_USER']->isInWebMount($rootlineElement['uid']);
				if (!$isInWebMount
					|| ($rootlineElement['uid'] === (int) $mountPoints[0]
						&& $rootlineElement['uid'] !== $isInWebMount)
				) {
					continue;
				}
				if ((int) $rootlineElement['pid'] === $nodeId
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
				$ident = (int) ($rootlineElement['sorting'] . '0' . $rootlineElement['uid']);
				if ($reference && $reference->offsetExists($ident)) {
					/** @var $refNode \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode */
					$refNode = $reference->offsetGet($ident);
					$refNode->setExpanded(TRUE);
					$refNode->setLeaf(FALSE);
					$reference = $refNode->getChildNodes();
					if ($reference == NULL) {
						$reference = GeneralUtility::makeInstance(
							'TYPO3\\CMS\\Backend\\Tree\\Pagetree\\PagetreeNodeCollection'
						);
						$refNode->setChildNodes($reference);
					}
				} else {
					$refNode = Commands::getNewNode($rootlineElement, $mountPoint);
					$replacement = '<span class="typo3-pagetree-filteringTree-highlight">$1</span>';
					$text = $refNode->getText();
					if ($isNumericSearchFilter && (int) $rootlineElement['uid'] === (int) $searchFilter) {
						$text = str_replace('$1', $text, $replacement);
					} elseif ($ageFilter > 0 && $rootlineElement['SYS_LASTCHANGED'] < $ageFilter) {
						$text = str_replace('$1', $text, $replacement);
					} elseif ($searchFilterQuoted !== '') {
						$text = preg_replace('/(' . $searchFilterQuoted . ')/i', $replacement, $text);
					}
					$refNode->setText(
						$text, $refNode->getTextSourceField(), $refNode->getPrefix(), $refNode->getSuffix()
					);
					/** @var $childCollection \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNodeCollection */
					$childCollection = GeneralUtility::makeInstance(
						'TYPO3\\CMS\\Backend\\Tree\\Pagetree\\PagetreeNodeCollection'
					);
					if ($i + 1 > $amountOfRootlineElements) {
						$childNodes = $this->getNodes($refNode, $mountPoint);
						foreach ($childNodes as $childNode) {
							/** @var $childNode \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode */
							$childRecord = $childNode->getRecord();
							$childIdent = (int) ($childRecord['sorting'] . '0' . $childRecord['uid']);
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
			$hookObject->postProcessFilteredNodes($node, $searchFilter, $mountPoint, $nodeCollection);
		}
		return $nodeCollection;
	}

	/**
	 * Returns an array of the requested filter types
	 *
	 * @param string $searchFilter
	 * @return array
	 */
	protected function getFilters($searchFilter) {
		$filters['age'] = 0;
		$filterValues = GeneralUtility::trimExplode(' ', $searchFilter);
		foreach ($filterValues as $index => $filterValue) {
			if (preg_match('/:-age-(\d*)-((?:year|month|week|day|hour|minute)s?)/is', $filterValue, $match)) {
				$filters['age'] = strtotime($match[1] . ' ' . $match[2] . ' ago');
				unset($filterValues[$index]);
				break;
			}
		}
		$filters['search'] = implode(' ', $filterValues);

		return [$filters['age'], $filters['search']];
	}

	/**
	 * Returns the where clause for fetching pages
	 *
	 * @param integer $id
	 * @param string $searchFilter
	 * @return string
	 */
	protected function getWhereClause($id, $searchFilter = '') {
		$where = $GLOBALS['BE_USER']->getPagePermsClause(1) . BackendUtility::deleteClause('pages') .
			BackendUtility::versioningPlaceholderClause('pages');
		if (is_numeric($id) && $id >= 0) {
			$where .= ' AND pid= ' . $GLOBALS['TYPO3_DB']->fullQuoteStr((int) $id, 'pages');
		}

		$excludedDoktypes = $GLOBALS['BE_USER']->getTSConfigVal('options.pageTree.excludeDoktypes');
		if (!empty($excludedDoktypes)) {
			$excludedDoktypes = $GLOBALS['TYPO3_DB']->fullQuoteArray(
				GeneralUtility::intExplode(',', $excludedDoktypes), 'pages'
			);
			$where .= ' AND doktype NOT IN (' . implode(',', $excludedDoktypes) . ')';
		}

		if ($searchFilter !== '') {
			$searchWhere = '';
			if (is_numeric($searchFilter) && $searchFilter > 0) {
				$searchWhere .= 'uid = ' . (int) $searchFilter . ' OR ';
			}

			list($ageFilter, $searchFilter) = $this->getFilters($searchFilter);
			if ($searchFilter !== '') {
				$searchFilter = $GLOBALS['TYPO3_DB']->fullQuoteStr('%' . $searchFilter . '%', 'pages');
				$useNavTitle = $GLOBALS['BE_USER']->getTSConfigVal('options.pageTree.showNavTitle');
				$useAlias = $GLOBALS['BE_USER']->getTSConfigVal('options.pageTree.searchInAlias');

				$searchWhereAlias = '';
				if ($useAlias) {
					$searchWhereAlias = ' OR alias LIKE ' . $searchFilter;
				}

				if ($useNavTitle) {
					$searchWhere .= '(nav_title LIKE ' . $searchFilter .
						' OR (nav_title = "" AND title LIKE ' . $searchFilter . ')' . $searchWhereAlias . ')';
				} else {
					$searchWhere .= 'title LIKE ' . $searchFilter . $searchWhereAlias;
				}

				$where .= ' AND (' . $searchWhere . ')';
			}

			if ($ageFilter > 0) {
				// Hide doktypes that normally don't contain normal content
				$where .= ' AND doktype NOT IN(3,4,6,7,199,254,255)';
				// Hide hidden pages
				$where .= ' AND NOT hidden ';
				$where .= ' AND SYS_LASTCHANGED < ' . $ageFilter;
			}
		}
		return $where;
	}
}