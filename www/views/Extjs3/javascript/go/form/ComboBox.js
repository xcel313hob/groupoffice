go.form.ComboBox = Ext.extend(Ext.form.ComboBox, {
	setValue: function (value) {
		var me = this;
		
		//create record from entity store if not exists
		if(value && this.store.entityStore.entity && !this.findRecord(me.valueField, value)) {
			this.store.entityStore.get([value], function (entities) {
				var comboRecord = Ext.data.Record.create([{
					name: me.valueField
				},{
					name: me.displayField
				}]);
				var currentRecord = new comboRecord(entities[0], entities[0][me.valueField]);
				
				me.store.add(currentRecord);
				
				go.form.ComboBox.superclass.setValue.call(me, value);
			});
		} else
		{
			go.form.ComboBox.superclass.setValue.call(this, value);
		}
	}
});
