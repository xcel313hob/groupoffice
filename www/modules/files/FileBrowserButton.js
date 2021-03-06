GO.files.FileBrowserButton = Ext.extend(Ext.Button, {
	
	model_name : "",
	id: 0,
	setId : function(id){
		this.id=id;
		this.setDisabled(!id);
	},
	
	initComponent : function(){
		Ext.applyIf(this, {
				text: t("Browse"),
				handler: function(){			
					

					GO.request({
						url:'files/folder/checkModelFolder',
						maskEl:this.ownerCt.ownerCt.getEl(),
						jsonData: {},
						params:{								
							mustExist:true,
							model:this.model_name,
							id:this.id
						},
						success:function(response, options, result){														
							var fb = GO.files.openFolder(result.files_folder_id);
							fb.model_name = this.model_name;
							fb.model_id = this.id;
							
							//hack to update entity store
							if(go.stores[fb.model_name]) {
								go.stores[fb.model_name].data[this.id].filesFolderId = result.files_folder_id;
								go.stores[fb.model_name].saveState();
							}
							
							fb.on('hide', function() {
								fb.model_id = null;
								fb.model = null;
								
							}, {single: true});
							
							//reload display panel on close
				
							GO.files.fileBrowserWin.on('hide', function() {
								this.fireEvent('close', this, result.files_folder_id);
							}, this, {single: true});
						},
						scope:this

					});
					
					
				},
				scope: this,
				disabled:true
			});
		
		GO.files.FileBrowserButton.superclass.initComponent.call(this);
	}
	
});


Ext.reg('filebrowserbutton', GO.files.FileBrowserButton);



