(function()
 {
   var TEMPLATE_READONLY_MD ='';
   var TEMPLATE_READ_WRITE_MD = '';
   var TEMPLATE_READONLY_MD ='';
   var TEMPLATE_READ_WRITE_MD = '';
   var TEMPLATE =
       '<div class="detailFileInfoContainer">' +
       '  <div class="mainFileInfoView">' +
       '    <div class="thumbnailContainer"><a href="#" class="thumbnail action-default"><div class="stretcher"/></a></div>' +
      '     <div class="file-details-container">' +
      '       <div class="fileName">' +
      '         <h3 title="{{name}}" class="ellipsis">{{name}}</h3>' +
      '       </div>' + // div fileName
      '     </div>' + // file-details-container
      '   </div>' + // mainFileInfoView
      '</div>' + // detailFileInfoContainer
/*      '{{#if tabHeaders}}' +
      '  <ul class="tabHeaders">' +
      '    {{#each tabHeaders}}' +
      '	     <li class="tabHeader" data-tabid="{{tabId}}" data-tabindex="{{tabIndex}}">' +
      '	       <a href="#">{{label}}</a>' +
      '	     </li>' +
      '	   {{/each}}' +
      '	 </ul>' +
      '{{/if}}' + */
      '<div class="tabsContainer" style="padding: 15px;">' +
/*      '  <h3>Archive state</h3>' +
      '  <ul>' +
      '    <li>{{state}}</li>' +
      '  </ul> ' +
      '  {{#each md as |group name|}}' +
      '    <h3>{{name}}</h3>' +
      '    <ul class="irods-metadata-group" data-field="{{name}}"> ' +
      '      {{#if group.readonly}}' +
               TEMPLATE_READONLY_MD +
      '      {{else}}' +
               TEMPLATE_READ_WRITE_MD +
      '	     {{/if}}' +
      '    </ul> ' +
      '    <br>' +
      '  {{/each}}' +
      '  <ul> ' +
      '    {{#if savable }}' +
      '      <li> ' +
      '        <button type="button" id="irods-metadata-save">Save</button>' +
      '      </li> ' +
      '    {{/if}} ' +
      '    {{#if submittable }}' +
      '      <li> ' +
      '        <button type="button" id="irods-metadata-submit">Submit</button>' +
      '      </li> ' +
      '    {{/if}} ' +
      '    {{#if approvable }}' +
      '      <li> ' +
      '        <button type="button" id="irods-metadata-reject">Reject</button>' +
      '      </li> ' +
      '    {{/if}} ' +
      '    {{#if approvable }}' +
      '      <li> ' +
      '        <button type="button" id="irods-metadata-approve">Approve</button>' +
      '      </li> ' +
      '    {{/if}} ' +
      '  </ul> ' + */
      '	</div>' + //tabscontainer
      '	<a class="close icon-close" href="#" alt="{{closeLabel}}"></a>';

   TEMPLATE = '<div id="app-sidebar" class="detailsView scroll-container">	<div class="detailFileInfoContainer">	<div class="mainFileInfoView"><div class="thumbnailContainer"><a href="#" class="thumbnail action-default" style="background-image: url(&quot;/core/img/filetypes/folder-external.svg&quot;);"><div class="stretcher"></div></a></div><div class="file-details-container"><div class="fileName"><h3 title="" class="ellipsis" data-original-title="test3">test3</h3><a class="permalink" href="http://127.0.0.1/f/29" title="" data-original-title="Private link:  Only people who have access to the file/folder can use it. Use it as a permanent link for yourself or to point others to files within shares"><span class="icon icon-public"></span><span class="hidden-visually">Private link:  Only people who have access to the file/folder can use it. Use it as a permanent link for yourself or to point others to files within shares</span></a></div>	<div class="file-details ellipsis">		<a href="#" class="action action-favorite favorite permanent">			<span class="icon icon-star" title="" data-original-title="Favorite"></span>		</a>		<span class="size" title="" data-original-title="-1 bytes">&lt; 1 KB</span>, <span class="date" title="" data-original-title="May 25, 2019 5:34 PM">3 hours ago</span>	</div></div><div class="hidden permalink-field"><input type="text" value="http://127.0.0.1/f/29" placeholder="Private link:  Only people who have access to the file/folder can use it. Use it as a permanent link for yourself or to point others to files within shares" readonly="readonly"></div></div><div class="systemTagsInfoView"><div class="systemTagsInputFieldContainer"><div class="select2-container select2-container-multi systemTagsInputField systemtags-select2-container" id="s2id_autogen7"><ul class="select2-choices">  <li class="select2-search-field">    <label for="s2id_autogen8" class="select2-offscreen"></label>    <input type="text" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" class="select2-input select2-default" id="s2id_autogen8" placeholder="" style="width: 100%;">  </li></ul><div class="select2-drop select2-drop-multi select2-display-none systemtags-select2-dropdown">   <ul class="select2-results">   </ul></div></div><input class="systemTagsInputField" type="hidden" name="tags" value="" tabindex="-1" style="display: none;"></div></div></div>		<ul class="tabHeaders">				<li class="tabHeader selected" data-tabid="activityTabView" data-tabindex="0">			<a href="#">Activities</a>		</li>				<li class="tabHeader" data-tabid="commentsTabView" data-tabindex="1">			<a href="#">Comments</a>		</li>				<li class="tabHeader" data-tabid="shareTabView" data-tabindex="2">			<a href="#">Sharing</a>		</li>				<li class="tabHeader hidden" data-tabid="versionsTabView" data-tabindex="3">			<a href="#">Versions</a>		</li>			</ul>		<div class="tabsContainer">	<div id="activityTabView" class="activityTabView tab"><div class="activity-section"><div class="loading hidden" style="height: 50px"></div><ul class="activities">    <li class="empty hidden">No activities</li><li class="activity box">        <div class="activity-icon icon-add-color svg"></div>        <div class="activitysubject">You created <a class="filename has-tooltip" href="http://127.0.0.1/apps/files/?dir=/iRODS/LandingZone/test3" title="" data-original-title="in iRODS/LandingZone">test3</a></div>        <span class="activitytime has-tooltip" title="" data-original-title="May 25, 2019 3:43 PM">5 hours ago</span>        <div class="activitymessage"></div>            </li></ul><input type="button" class="showMore hidden" value="Load more activities" <="" div=""></div></div></div>	<a class="close icon-close" href="#" alt="Close"></a></div>';

   function iRodsMetaDataView(file, context) {
     if(typeof context.fileList._irodsMetaView == 'undefined') {
       let view = new OCA.IRODS_POPUP.View();
       view.$el.insertBefore(context.fileList.$el);
       context.fileList._irodsMetaView = view;
     }
     OC.Apps.showAppSidebar(context.fileList._irodsMetaView.$el);
     context.fileList._irodsMetaView.setPath(context);
     context.fileList._irodsMetaView.load();
   }

   function isIrodsData(attributes, mp) {
     var path = attributes.getNamedItem('data-path');
     if(!path)
     {
       return false;
     }
     var arr = path.value.split('/');
     var file = attributes.getNamedItem('data-file');
     if(!file)
     {
       return false;
     }
     arr.push(file.value);
     if(arr.length >= 4)
     {
       return (mp.indexOf('/' + arr[1]) != -1);
     }
     else
     {
       return false;
     }
   }


   /////////////////////////////////////////////////////////////
   // Context Menu
   /////////////////////////////////////////////////////////////
   if (!OCA.IRODS_POPUP)
   {
     OCA.IRODS_POPUP = {
       attach: function(fileList) {
         var url = OC.generateUrl('/apps/files_irods/api/virtual');
         $.getJSON(url)
           .done(function(data) {
             fileList.fileActions.registerAction({
               name: 'irods_metadata',
               displayName: 'iRODS Meta Data',
               mime: 'all',
               permissions: OC.PERMISSION_READ,
               icon: OC.imagePath('files_irods', 'eye'),
               actionHandler: iRodsMetaDataView
             });

             fileList.fileActions.addAdvancedFilter(function(actions, context) {
               if(!isIrodsData(context.$file[0].attributes,
                               data['mount_points']))
               {
                 delete actions['irods_metadata'];
               }
               return actions;
             });

             data['mount_points'].forEach(function(mp) {
             });
           })
           .fail(function(err) {
           });
       }
     };
   }

   /////////////////////////////////////////////////////////////
   // View
   /////////////////////////////////////////////////////////////
   OCA.IRODS_POPUP.View =  OC.Backbone.View.extend({
     setPath: function(context) {
       this.path = context.dir;
       if(this.path != '/') {
         this.path += '/' + context.fileInfoModel.attributes.name;
       }
       else {
         this.path = '';
       }
       this.apiUrl = OC.generateUrl('/apps/files_irods/api/meta' + this.path);
       if(context.fileInfoModel.isDirectory()) {
         this.iconurl = OC.MimeType.getIconUrl('dir'); 
       }
       else {
         this.iconurl = OC.MimeType.getIconUrl(context.fileInfoModel.get('mimetype'));
         var iconurl = OC.MimeType.getIconUrl(context.fileInfoModel.get('mimetype'));
       }
     },

     load: function() {
       var deferred = $.Deferred();
       var self = this;
       $.getJSON(this.apiUrl)
         .done(function(data) {
           // why does getJSON return string ???
           if(typeof data == "string") {
             data = JSON.parse(data);
           }
           self.render(null, null, null);
         })
         .fail(function(err) {
           console.log(err);
         });
     },

     // render functions
     template: function(vars) {
      if (!this._template) {
	this._template = Handlebars.compile(TEMPLATE);
      }
      return this._template(vars);
    },

     render: function(file, data, cfg) {
       var templateVars = {
	 closeLabel: t('files', 'Close'),
         name: 'XXX',
         //md: data,
         //data: JSON.stringify(data)
       };
       this.$el.html(this.template(templateVars));
       this.$el.find('.thumbnail').css('background-image', 'url("' + this.iconurl + '")');
     }
   });

 })();

OC.Plugins.register('OCA.Files.FileList', OCA.IRODS_POPUP);
