(function()
 {
   var TEMPLATE = '';

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
       template_html: '',

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
             $.get(OC.filePath('files_irods', 'templates', 'popup.html'))
               .done(function(template) {
                 TEMPLATE = template;
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
     id: 'app-sidebar',
     tabName: 'div',
     className: 'detailsView scroll-container',
     events: { 'click a.close': '_onClose' },

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
       }
     },

     load: function() {
       var self = this;
       $.getJSON(this.apiUrl)
         .done(function(data) {
           // why does getJSON return string ???
           if(typeof data == "string") {
             data = JSON.parse(data);
           }
           self.render(data);
         })
         .fail(function(err) {
           self.render({});
           self.displayError('error', 'failed to load data');
         });
     },

     // render functions
     render: function(data, cfg) {
       var templateVars = {
	 closeLabel: t('files', 'Close'),
         name: data.file,
         data: this.orderEntries(data)
       };
       if (!this._template) {
	 this._template = Handlebars.compile(TEMPLATE);
       }
       console.log('---------');
       console.log(data);
       this.$el.html(this._template(templateVars));
       this.$el.find('.thumbnail').css('background-image', 'url("' + this.iconurl + '")');
       var self = this;
       $(".irods-metadata-group").on('click', '.irods-metadata-add', function(event) {
         self.addMetadata(event);
       });
       $(".irods-metadata-group").on('click', '.irods-metadata-remove', function(event) {
         self.removeMetadata(event);
       });
       $(".irods-metadata-group").on('change keyup paste', '.irods-metadata-entry-value', function(event) {
         $(event.target).removeClass('irods-input-warning');
         $(event.target).removeClass('irods-input-error');
       });
       $("#irods-metadata-save").on('click', function(event) {
         self.saveMetadata(event, "update");
       });
       $("#irods-metadata-submit").on('click', function(event) {
         self.saveMetadata(event, "submit");
       });
       $("#irods-metadata-reject").on('click', function(event) {
         self.approveReject(event, 'reject');
       });
       $("#irods-metadata-approve").on('click', function(event) {
         self.approveReject(event, 'approve');
       });

       ['error', 'warning'].forEach(function(type) {
         if(data[type] != undefined && data[type]) {
           self.displayError(type, data[type]);
         }});
     },

     orderEntries: function(data) {
       var entries = {};
       if(data['fields'] != undefined) {
         data.fields.forEach(function(f) {
           if(data.entries[f] != undefined) {
             entries[f] = data.entries[f];
           }
         });
         data.entries = entries;
       }
       return data;
     },

     _onClose: function(event) {
      OC.Apps.hideAppSidebar(this.$el);
      event.preventDefault();
     },

     addMetadata: function(event) {
       var name = $(event.target).attr('data-field');
       var html = '<li><nobor>' +
           ' <input class="irods-metadata-entry-value" data-field="' + name + '" type="text" value="" style="width: 300px"/>' +
           ' <button type="button" class="irods-metadata-remove">-</button>' +
           '</nobr></li>';
       var sel = $(".irods-metadata-group[data-field='" + name + "'] li:last");
       sel.before(html);
     },

     removeMetadata: function(event) {
       event.preventDefault();
       var numberOfValues = $(event.target).parent().parent().parent().find('.irods-metadata-entry').size();
       if(numberOfValues <= 1) {
         return;
       }
       else {
         $(event.target).parent().parent().remove();
       }
     },

     saveMetadata: function(event, op) {
       var md = {};
       $(".irods-metadata-entry-value").each(function(k, elem) {
         var field = $(elem).attr('data-field');
         var value = $(elem).val().trim();
         if(value) {
           if(md[field] == undefined) {
             md[field] = [value];
           }
           else {
             md[field].push(value);
           }
         }
       });
       var self = this;
       var url = this.apiUrl;
       $.ajax({
          url: url,
          type: 'PUT',    
          data: {"entries": md,
                 "op": op},
          success: function(data) {
            // why does getJSON return string ???
            if(typeof data == "string") {
              data = JSON.parse(data);
            }
            if(op == "submit" && !data.error && data.state == 'SUBMITTED') {
              var url = OC.generateUrl('/apps/files/?dir=/' + data.state_urls['SUBMITTED']);
              $(location).attr('href', url);
            }
            self.render(data);
            if(typeof data.error !== undefined && data.error) {
              self.displayError('error', data.error);
            }
            else if(typeof data.warning !== undefined && data.warning) {
              self.displayError('warning', data.warning);
            }
            else {
              self.hideError();
            }
          },
          error: function(data) {
            self.displayError('Failed to save metadata');
          }
      });
     },

     displayError : function(type, msg) {
       $( "#irods-" + type + "-message" ).html(msg);
       $( "#irods-" + type).show();
     },

     hideError : function() {
       $( "#irods-error" ).hide();
     }

   });

 })();

OC.Plugins.register('OCA.Files.FileList', OCA.IRODS_POPUP);
