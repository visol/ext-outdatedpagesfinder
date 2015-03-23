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
Ext.namespace('TYPO3.Components.PageTree');

/**
 * @class TYPO3.Components.PageTree.AgeFilteringTree
 *
 * Filtering Tree
 *
 * @namespace TYPO3.Components.PageTree
 * @extends TYPO3.Components.PageTree.Tree
 * @author Stefan Galinski <stefan.galinski@gmail.com>
 */
TYPO3.Components.PageTree.AgeFilteringTree = Ext.extend(TYPO3.Components.PageTree.Tree, {
	/**
	 * Filter Value
	 *
	 * @type {Integer}
	 */
	filterValue: 0,

	/**
	 * Tree loader implementation for the filtering tree
	 *
	 * @return {void}
	 */
	addTreeLoader: function() {
		this.loader = new Ext.tree.TreeLoader({
			directFn: this.treeDataProvider.getAgeFilteredTree,
			paramOrder: 'nodeId,attributes,filterValue',
			nodeParameter: 'nodeId',
			baseAttrs: {
				uiProvider: this.uiProvider
			},

			listeners: {
				beforeload: function(treeLoader, node) {
					if (!node.ownerTree.filterValue || node.ownerTree.filterValue === 0) {
						return false;
					}

					treeLoader.baseParams.nodeId = node.id;
					treeLoader.baseParams.filterValue = node.ownerTree.filterValue;
					treeLoader.baseParams.attributes = node.attributes.nodeData;
				}
			}
		});
	}
});

// XTYPE Registration
Ext.reg('TYPO3.Components.PageTree.AgeFilteringTree', TYPO3.Components.PageTree.AgeFilteringTree);
