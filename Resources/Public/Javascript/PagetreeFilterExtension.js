var SG = SG || {};

SG.addFilterInformation = function(selectField) {
	var searchFilter = Ext.getCmp('typo3-pagetree-topPanel-filter');
	var filterValue = parseInt(selectField.getValue());
	selectField.clearValue();

	if (filterValue === 0) {
		filterValue = '';
	} else if (filterValue === 3) {
		filterValue = ':-age-3-months';
	} else if (filterValue === 6) {
		filterValue = ':-age-6-months';
	} else if (filterValue === 9) {
		filterValue = ':-age-9-months';
	} else if (filterValue === 12) {
		filterValue = ':-age-12-months';
	}

	searchFilter.setValue(filterValue);
	searchFilter.fireEvent('keydown', searchFilter);
};

Ext.onReady(
	function() {
		/**
		 * Callback method for the module menu
		 *
		 * @return {TYPO3.Components.PageTree.App}
		 */
		TYPO3.ModuleMenu.App.registerNavigationComponent(
			'typo3-pagetree', function() {
				TYPO3.Backend.NavigationContainer.PageTree = new TYPO3.Components.PageTree.App();

				// compatibility code
				top.nav = TYPO3.Backend.NavigationContainer.PageTree;
				top.nav_frame = TYPO3.Backend.NavigationContainer.PageTree;
				top.content.nav_frame = TYPO3.Backend.NavigationContainer.PageTree;

				SG.addAgeSelectBox = function() {
					var searchFilterWrap = Ext.getCmp('typo3-pagetree-topPanel-filterWrap');

					if (searchFilterWrap !== undefined) {
						clearInterval(SG.addAgeSelectBoxInterval);

						var ageSelectBox = new Ext.form.ComboBox(
							{
								id: 'typo3-pagetree-topPanel-ageFilter',
								width: 150,
								valueField: 'age',
								displayField: 'label',
								mode: 'local',
								selectOnFocus: true,
								triggerAction: 'all',
								editable: false,
								forceSelection: true,
								store: new Ext.data.SimpleStore(
									{
										autoLoad: true,
										fields: ['age', 'label'],
										data: [
											['0', TYPO3.Components.PageTree.LLL.ageFilterAll],
											['3', TYPO3.Components.PageTree.LLL.ageFilter3],
											['6', TYPO3.Components.PageTree.LLL.ageFilter6],
											['9', TYPO3.Components.PageTree.LLL.ageFilter9],
											['12', TYPO3.Components.PageTree.LLL.ageFilter12]
										]
									}
								),
								value: 0,
								listeners: {
									'select': {
										fn: SG.addFilterInformation,
										scope: this
									}
								}

							}
						);

						Ext.getCmp('typo3-pagetree-topPanel-filter').setWidth(200);

						searchFilterWrap.add(ageSelectBox);
						searchFilterWrap.doLayout();
					}
				};
				SG.addAgeSelectBox();
				SG.addAgeSelectBoxInterval = window.setInterval(SG.addAgeSelectBox, 1000);

				return TYPO3.Backend.NavigationContainer.PageTree;
			}
		);
	}
);