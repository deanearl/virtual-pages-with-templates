var vptNavMenu;
(function($) {
	var vpt;
   	vpt = vptNavMenu = {

   		options : {
			menuItemDepthPerLevel : 30, // Do not use directly. Use depthToPx and pxToDepth instead.
			globalMaxDepth : 11
		},

		menuList : undefined,	// Set in init.
		targetList : undefined, // Set in init.
		menusChanged : false,
		isRTL: !! ( 'undefined' != typeof isRtl && isRtl ),
		negateIfRTL: ( 'undefined' != typeof isRtl && isRtl ) ? -1 : 1,

   		init : function() {
   			vpt.menuList = $('#menu-to-edit');
			vpt.targetList = vpt.menuList;

   			this.attachTabsPanelListeners();
   		},

   		attachTabsPanelListeners : function() {
			$('#menu-settings-column #submit-customlinkdivVpt').bind('click', function(e) {
				var selectAreaMatch, panelId, wrapper, items,
					target = $(e.target);
				if ( target.hasClass('submit-add-to-menu') ) {

					vpt.registerChange();
					
					if ( e.target.id && 'submit-customlinkdivVpt' == e.target.id )
						vpt.addCustomLink( vpt.addMenuItemToBottom );
					return false;
				} 
			});
		},
		registerChange : function() {
			vpt.menusChanged = true;
		},

		addCustomLink : function( processMethod ) {
			var url = $('#custom-vpt-menu-item-url').val(),

			titles = $('#custom-vpt-menu-item-titles').val();
			if (!titles || titles == '')
				return;

			processMethod = processMethod || vpt.addMenuItemToBottom;

			if ( '' === url || 'http://' == url )
			{
				url = '*';
			}

			var params = {url:url, titles:titles}; 

			// Show the ajax spinner
			$('.customlinkdivVpt .spinner').show();
			this.addLinkToMenu( params, processMethod, function() {
				// Remove the ajax spinner
				$('.customlinkdivVpt .spinner').hide();
				// Set custom link form back to defaults
				$('#custom-vpt-menu-item-titles').val('').blur();
				$('#custom-vpt-menu-item-url').val('*');
			});
		},

		addLinkToMenu : function(params, processMethod, callback) {
			processMethod = processMethod || vpt.addMenuItemToBottom;
			callback = callback || function(){};

			menu_items = new Array();
			$.each(params.titles.split("\n").reverse(), function(k,v){
				v = $.trim(v);
				menu_items.push({'menu-item-type':'custom', 'menu-item-url':params.url, 'menu-item-title':v, 'menu-item-custom': 'vpt'}); 
			});
			vpt.addItemToMenu(menu_items, processMethod, callback);
		},

		addItemToMenu : function(menuItem, processMethod, callback) {
			var menu = $('#menu').val(),
				nonce = $('#menu-settings-column-nonce').val(),
				params;

			processMethod = processMethod || function(){};
			callback = callback || function(){};

			params = {
				'action': 'add-menu-item',
				'menu': menu,
				'menu-settings-column-nonce': nonce,
				'menu-item': menuItem
			};

			$.post( ajaxurl, params, function(menuMarkup) {
				var ins = $('#menu-instructions');

				menuMarkup = $.trim( menuMarkup ); // Trim leading whitespaces
				processMethod(menuMarkup, params);

				// Make it stand out a bit more visually, by adding a fadeIn
				$( 'li.pending' ).hide().fadeIn('slow');
				$( '.drag-instructions' ).show();
				if( ! ins.hasClass( 'menu-instructions-inactive' ) && ins.siblings().length )
					ins.addClass( 'menu-instructions-inactive' );
				
				callback();
			});

		},

		/**
			 * Process the add menu item request response into menu list item.
			 *
			 * @param string menuMarkup The text server response of menu item markup.
			 * @param object req The request arguments.
			 */
		addMenuItemToBottom : function( menuMarkup ) {
			$(menuMarkup).hideAdvancedMenuItemFields().appendTo( vpt.targetList );
			wpNavMenu.refreshKeyboardAccessibility();
			wpNavMenu.refreshAdvancedAccessibility();
		},


   	};

   	$(document).ready(function(){ 
   		vptNavMenu.init(); 
   		jQuery('#custom-vpt-menu-item-titles').val('').blur();
   	});
})(jQuery);