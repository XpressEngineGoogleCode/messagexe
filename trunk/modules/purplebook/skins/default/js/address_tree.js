/**
 * @fileoverview address tree set
 * @requires address.html
 */

(function($) {
	jQuery(function($) {
		init_purplebook_tree(g_tpl_path+'img/');
	});
}) (jQuery);

/**
 * 해당 node 이동
 * @param {node_id.string}
 * @param {dest_id.string} 이동할 node_id 
 */
function purplebook_move_node(node_id, dest_id) {
	jQuery.ajax({
		type: 'POST',
		dataType: "json",
		contentType: "application/json; charset=utf-8",
		async : false,
		url: "./",
		data : { 
			module : "purplebook"
			, act : "procPurplebookMoveNode"
			, node_id : node_id
			, parent_id : dest_id
		},
		success : function (r) {
			if (r.error==-1) {
				alert(r.message);
			} else {
				var selected_folders = jQuery('#smsPurplebookTree').jstree('get_selected');
				if (selected_folders.length > 0) {
					var node = jQuery(selected_folders[0]);
					pb_load_list(node);
				}
			}
		}
	});
}

/**
 * target tree set
 */
function init_target_tree(element_id, img_base) {
	init_target_tree.img_base = img_base;
	jQuery(element_id).jstree({ 
		// the list of plugins to include
		"plugins" : [ "themes", "json_data", "ui", "crrm", "search", "types", "hotkeys" ],
		// Plugin configuration

		// I usually configure the plugin that handles the data first - in this case JSON as it is most common
		"json_data" : { 
			// I chose an ajax enabled tree - again - as this is most common, and maybe a bit more complex
			// All the options are the same as jQuery's except for `data` which CAN (not should) be a function
			"ajax" : {
				contentType: "application/json; charset=utf-8",
				// the URL to fetch the data
				"url" : "./",
				// this function is executed in the instance's scope (this refers to the tree instance)
				// the parameter is the node being loaded (may be -1, 0, or undefined when loading the root nodes)
				"data" : function (n) { 
					if (typeof(init_target_tree.initial)=='undefined') {
						init_target_tree.initial = 1;
						node_id = 'root';
					}
					if (typeof(n.attr) != 'undefined') {
						node_id = n.attr('node_id');
					}
					// the result is fed to the AJAX request `data` option
					return { 
						module : "purplebook"
						, act : "getPurplebookList"
						, node_id : node_id
						, node_type : "1"
					}; 
				},
				"success" : function(d) { 
					if (d.error == -1) {
						jQuery('#smsPurplebookTargetTree').html(d.message);
						return;
					}
					return d.data; 
				}
			}
		},
		// Configuring the search plugin
		"search" : {
			// As this has been a common question - async search
			// Same as above - the `ajax` config option is actually jQuery's object (only `data` can be a function)
			"ajax" : {
				"url" : "./",
				// You get the search string as a parameter
				"data" : function (str) {
					return { 
						"operation" : "search", 
						"search_str" : str 
					}; 
				}
			}
		},
		// Using types - most of the time this is an overkill
		// Still meny people use them - here is how
		"types" : {
			// I set both options to -2, as I do not need depth and children count checking
			// Those two checks may slow jstree a lot, so use only when needed
			"max_depth" : -2,
			"max_children" : -2,
			// I want only `drive` nodes to be root nodes 
			// This will prevent moving or creating any other type as a root node
			"valid_children" : [ "drive" ],
			"types" : {
				// The default type
				"default" : {
					// I want this type to have no children (so only leaf nodes)
					// In my case - those are files
					"valid_children" : "none",
					// If we specify an icon for the default type it WILL OVERRIDE the theme icons
					"icon" : {
						"image" : img_base + "file.png"
					}
				},
				// The `folder` type
				"folder" : {
					// can have files and other folders inside of it, but NOT `drive` nodes
					"valid_children" : [ "default", "folder" ],
					"icon" : {
						"image" : img_base + "folder.png"
					}
				},
				// The `folder` type
				"shared_folder" : {
					// can have files and other folders inside of it, but NOT `drive` nodes
					"valid_children" : [ "folder" ],
					"icon" : {
						"image" : img_base + "shared_folder.png"
					}
				},
				// The `drive` nodes 
				"root" : {
					// can have files and folders inside, but NOT other `drive` nodes
					"valid_children" : [ "folder" ],
					"icon" : {
						"image" : img_base + "root.png"
					},
					// those options prevent the functions with the same name to be used on the `drive` type nodes
					// internally the `before` event is used
					"start_drag" : false,
					"move_node" : false,
					"delete_node" : false,
					"remove" : false
				}
			}
		},
		// For UI & core - the nodes to initially select and open will be overwritten by the cookie plugin

		// the UI plugin - it handles selecting/deselecting/hovering nodes
		"ui" : {
			// this makes the node with ID node_4 selected onload
			"initially_select" : [ "node_4" ]
		},
		// the core plugin - not many options here
		"core" : { 
			// just open those two nodes up
			// as this is an AJAX enabled tree, both will be downloaded from the server
			"initially_open" : [ "node_2" , "node_3" ] 
		}
	});

}
   
/**
 * purplebook tree set
 */
function init_purplebook_tree(img_base) {
	init_purplebook_tree.img_base = img_base;
	jQuery("#smsPurplebookTree").jstree({
		// the list of plugins to include
		"plugins" : [ "themes", "json_data", "ui", "crrm", "cookies", "dnd", "search", "types", "hotkeys", "contextmenu" ],
		// Plugin configuration

		// I usually configure the plugin that handles the data first - in this case JSON as it is most common
		"json_data" : { 
			// I chose an ajax enabled tree - again - as this is most common, and maybe a bit more complex
			// All the options are the same as jQuery's except for `data` which CAN (not should) be a function
			"ajax" : {
				contentType: "application/json; charset=utf-8",
				// the URL to fetch the data
				"url" : "./",
				// this function is executed in the instance's scope (this refers to the tree instance)
				// the parameter is the node being loaded (may be -1, 0, or undefined when loading the root nodes)
				"data" : function (n) { 
					p_show_waiting_message();
					if (typeof(init_purplebook_tree.initial)=='undefined') {
						init_purplebook_tree.initial = 1;
						node_id = 'all';
					}
					if (typeof(n.attr) != 'undefined') {
						node_id = n.attr('node_id');
					}
					// the result is fed to the AJAX request `data` option
					return { 
						module : "purplebook"
						, act : "getPurplebookList"
						, node_id : node_id
						, node_type : "1"
					}; 
				},
				"success" : function(d) { 
					p_hide_waiting_message();
					if (d.error == -1) {
						jQuery('#smsPurplebookTree').html(d.message);
						return;
					}
					return d.data; 
				}
			}
		},
		// we dont use this because cannot support json_data.
		"search" : {
			"ajax" : {
				contentType: "application/json; charset=utf-8",
				"url" : "./",
				"data" : function (str) {
					return { 
						module : "purplebook"
						, act : "getPurplebookSearchFolder"
						, search : str
					}; 
				},
				"success" : function(d) { 
					for(i = 0; i < d.data.length; i++) {
						d.data[i] = '#node_'+d.data[i];
					}
					return d.data;
				}
			}
		},
		// Using types - most of the time this is an overkill
		// Still meny people use them - here is how
		"types" : {
			// I set both options to -2, as I do not need depth and children count checking
			// Those two checks may slow jstree a lot, so use only when needed
			"max_depth" : 12,
			"max_children" : -2,
			// I want only `drive` nodes to be root nodes 
			// This will prevent moving or creating any other type as a root node
			"valid_children" : [ "root","shared","trashcan" ],
			"types" : {
				"default" : {
					// I want this type to have no children (so only leaf nodes)
					// In my case - those are files
					"valid_children" : "none",
					// If we specify an icon for the default type it WILL OVERRIDE the theme icons
					"icon" : {
						"image" : img_base + "file.png"
					}
				},
				"folder" : {
					// can have files and other folders inside of it, but NOT `drive` nodes
					"valid_children" : [ "folder","shared_folder" ],
					"icon" : {
						"image" : img_base + "folder.png"
					}
				},
				"shared_folder" : {
					"valid_children" : [ "folder","shared_folder" ],
					"icon" : {
						"image" : img_base + "shared_folder.png"
					}
				},
				"root" : {
					"valid_children" : [ "folder","shared_folder" ],
					"icon" : {
						"image" : img_base + "root.png"
					},
					"start_drag" : false,
					"move_node" : false,
					"delete_node" : false,
					"remove" : false
				},
				"trashcan" : {
					"valid_children" : [ "folder" ],
					"icon" : {
						"image" : img_base + "trashcan.png"
					},
					"start_drag" : false,
					"move_node" : false,
					"delete_node" : false,
					"remove" : false
				},
				"shared" : {
					"valid_children" : [ "folder" ],
					"icon" : {
						"image" : img_base + "folder_public.png"
					},
					"start_drag" : false,
					"dnd_show" : false,
					"dnd_open" : false,
					"dnd_enter" : false,
					"dnd_finish" : false,
					"move_node" : false,
					"delete_node" : false,
					"remove" : false
				}
			}
		},
		// For UI & core - the nodes to initially select and open will be overwritten by the cookie plugin

		// the UI plugin - it handles selecting/deselecting/hovering nodes
		"ui" : {
			// this makes the node with ID node_4 selected onload
			"initially_select" : [ "node_0" ]
		},
		// the core plugin - not many options here
		"core" : { 
			"html_titles" : "html"
			,"strings" : { loading : "로딩중 ...", new_node : "새폴더" }
		},
		"dnd" : {
			"drag_check" : function() {
				return {
					after : false
					, before : false
					, inside : true
				};
			}
			, "drag_finish" : function(data) {
				$o = jQuery(data.o);
				$r = jQuery(data.r);
				if (!$o.hasClass('jstree-draggable')) {
					$o = $o.parent();
				}
				purplebook_move_node($o.attr('node_id'), $r.attr('node_id'));
			}
			, "drop_check" : function(data) {
				return true;
			}
			, "drop_finish" : function() {
				return true;
			}
		},
		"contextmenu" : {
			"items" : {
				"create" : {
					"separator_before"	: false,
					"separator_after"	: true,
					"label"				: "만들기",
					"action"			: function (obj) { this.create(obj); }
				},
				"rename" : {
					"separator_before"	: false,
					"separator_after"	: false,
					"label"				: "이름변경",
					"action"			: function (obj) { this.rename(obj); }
				},
				"remove" : {
					"separator_before"	: false,
					"icon"				: false,
					"separator_after"	: false,
					"label"				: "삭제",
					"action"			: function (obj) { this.remove(obj); }
				},
				"cut" : {
					"separator_before"	: true,
					"separator_after"	: false,
					"label"				: "잘라내기",
					"action"			: function (obj) { this.cut(obj); }
				},
				"paste" : {
					"separator_before"	: false,
					"icon"				: false,
					"separator_after"	: false,
					"label"				: "붙여넣기",
					"action"			: function (obj) { this.paste(obj); }
				},
				"share" : {
					"separator_before"	: true,
					"icon"				: false,
					"separator_after"	: false,
					"label"				: "공유",
					"action"			: function (obj) { this.share(obj); }
				},
				"properties" : {
					"separator_before"	: false,
					"icon"				: false,
					"separator_after"	: false,
					"label"				: "정보보기",
					"action"			: function (obj) { this.properties(obj); }
				},
				"xldownload" : {
					"separator_before"	: true,
					"icon"				: false,
					"separator_after"	: false,
					"label"				: "엑셀 다운로드",
					"action"			: function (obj) { this.xldownload(obj); }
				}
			}
		}
	})
	.bind("create.jstree", function (e, data) {
		//parent_route = data.rslt.parent.attr("node_route");
		parent_node = data.rslt.parent.attr("node_id");

		jQuery.ajax({
			type: "POST",
			dataType: "json",
			contentType: "application/json; charset=utf-8",
			url : "./", 
			data : { 
				module : "purplebook"
				, act : "procPurplebookAddNode"
				, parent_node : parent_node
				, node_type : "1"
				, node_name : data.rslt.name
			}, 
			success : function(r) {
				if (r.error == -1) {
					jQuery.jstree.rollback(data.rlbk);
					alert(r.message);
				} else {
					data.rslt.obj.attr("id", "node_" + r.id).attr('node_id',r.node_id).attr('node_name',r.node_name).attr('node_route',r.node_route).attr('rel','folder');
				}
			}
		});
	})
	.bind("remove.jstree", function (e, data) {

		data.rslt.obj.each(function () {
			jQuery.ajax({
				type: 'POST',
				dataType: "json",
				contentType: "application/json; charset=utf-8",
				async : false,
				url: "./",
				data : { 
					module : "purplebook"
					, act : "procPurplebookMoveNode"
					, node_id : this.id.replace("node_","")
					, parent_id : 't.'
				}, 
				success : function (r) {
					if (r.error == -1) {
						alert(r.message);
					} else {
						// do nothing
					}
				}
			});
		});
	})
	.bind("rename.jstree", function (e, data) {
		var node_id = data.rslt.obj.attr("node_id");
		var node_name = data.rslt.new_name;

		jQuery.ajax({
			type: "POST",
			dataType: "json",
			contentType: "application/json; charset=utf-8",
			url : "./", 
			data : { 
				module : "purplebook"
				, act : "procPurplebookRenameNode"
				, node_id : node_id
				, node_name : node_name
			}, 
			success : function(r) {
				if (r.error == -1) {
					jQuery.jstree.rollback(data.rlbk);
					alert(r.message);
				}
			}
		});
	})
	.bind("move_node.jstree", function (e, data) {
		data.rslt.o.each(function (i) {
			var node_id = jQuery(this).attr("node_id");
			var parent_id = data.rslt.np.attr("node_id");

			jQuery.ajax({
				type: 'POST',
				dataType: "json",
				contentType: "application/json; charset=utf-8",
				async : false,
				url: "./",
				data : { 
					module : "purplebook"
					, act : "procPurplebookMoveNode"
					, node_id : node_id
					, parent_id : parent_id
					, copy : data.rslt.cy ? 1 : 0
				},
				success : function (r) {
					if (r.error == -1) {
						jQuery.jstree.rollback(data.rlbk);
					} else {
						jQuery(data.rslt.oc).attr("id", "node_" + r.id);
						if (data.rslt.cy && jQuery(data.rslt.oc).children("UL").length) {
							data.inst.refresh(data.inst._get_parent(data.rslt.oc));
						}
					}
				}
			});
		});
	})
	.bind("select_node.jstree", function(e, data) {
		var node = data.rslt.obj;
		pb_load_list(node);
	});
}
