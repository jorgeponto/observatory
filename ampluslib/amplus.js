function Confirma(esto) {
	flag = (confirm(esto)) ? true : false;
	return flag;
}

function selall(obj, formname) {
	var arrInput = formname.getElementsByTagName("input");
	for (i=0; i<arrInput.length; i++) {
		if (arrInput[i].type == "checkbox") {
			arrInput[i].checked = obj.checked;
		}
	}
}

( function( $ ) {
	var theme_js_vars = {"tooltip":"1","tabs":"1"};
	$(".accordion").accordion({ header:"h4", collapsible:true, heightStyle:"content", active:false });
	$("#list").accordion({ header:"h3", collapsible:true, heightStyle:"content", active:false });
	
	var curTab = "table";
	var wWidth = $(window).width();
	var wWidth = (wWidth > 600) ? 800 : (wWidth * 0.9);
	$(".help").dialog({
		autoOpen: false, 
		title: "WCAG 2.0", 
		closeText: "", 
		width: wWidth, 
		maxHeight: 600,
		buttons: [{
				id: "toggler",
				text: $viewTable,
				click: function() {
					$('.toggs').toggle();
					if (curTab == "table") {
						$("#toggler span").text($viewChart);
						curTab = "grafico";
					} else {
						$("#toggler span").text($viewTable);
						curTab = "table";
					}
				}
			},
			{ 
				text: "Fechar",
				click: function() {
					$(this).dialog("close");
				  }
		}]
	});
	$('.changer').each(function(){
		$(this).click(function(e) {
			var $clas = $(this).attr('id');
			$('.'+$clas).toggle();
			$(this).text(function(i,v){
				return v === $viewTable ? $viewChart : $viewTable
			})
		});
	});
	$('.ui-dialog-buttonset').css('float','none');
	$('.ui-dialog-buttonset>button:last-child').css('float','right');
	$("a.dialog-scores").click(function(evt) {
			evt.preventDefault();
			$("#scores").dialog("option" , "title" , $(this).text());
			$("#scores").dialog("open");
	});
	$("a.dialog-errors").click(function(evt) {
			evt.preventDefault();
			$("#errors").dialog("option" , "title" , $(this).text());
			$("#errors").dialog("open");
	});
	$("a.dialogsee").each(function(){
		$(this).click(function(e) {
			href = $(this).attr("href").split("#")[1];
			$texto = $("#"+href).html();
			$("<div class=\"fontmono\"></div>").text($texto).dialog({title: $dialogTitle, closeText: "", width: 600,
			buttons: { "OK": function() { $(this).dialog("close"); } }});
			e.preventDefault();
		});
	});
	
	
	$('.toggleforms').each(function() {
		$(this).click(function(e) {
			$href = $(this).attr("href").split("#")[1];
			$href2 = $href === 'form2' ? 'form3' : 'form2';
			
			if($('#'+$href2).is(':hidden')) {
				$('#'+$href).toggle();
			} else {
				$('#'+$href2).toggle();
				$('#'+$href).toggle();
			}
			e.preventDefault();
		});
	});
	
	
	
	// Responsive videos
	var all_videos = $( '.post-content' ).find( 'iframe[src^="http://player.vimeo.com"], iframe[src^="http://www.youtube.com"], iframe[src^="http://www.dailymotion.com"], object, embed' ),
    	input = document.createElement( 'input' ),
    	i, footer_height;

	all_videos.each(function() {
		var el = $(this);
		el
			.attr( 'data-aspectRatio', el.height() / el.width() )
			.attr( 'data-oldWidth', el.attr( 'width' ) );
	} );
	$(window)
		.resize( function() {
			all_videos.each( function() {
				var el = $(this),
					newWidth = el.parents( '.post-content' ).width(),
					oldWidth = el.attr( 'data-oldWidth' );

				if ( oldWidth > newWidth ) {
					el
						.removeAttr( 'height' )
						.removeAttr( 'width' )
					    .width( newWidth )
				    	.height( newWidth * el.attr( 'data-aspectRatio' ) );
				}
			} );

			if ( $(window).width() > 600 ) {
				$( '#site-navigation' ).show();
				$( '#drop-down-search' ).show();
			}

			footer_height();
		} )
		.resize()
		.load( function() {
			footer_height();
		} );

	// Placeholder fix for older browsers
    if ( ( 'placeholder' in input ) == false ) {
		$( '[placeholder]' ).focus( function() {
			i = $( this );
			if ( i.val() == i.attr( 'placeholder' ) ) {
				i.val( '' ).removeClass( 'placeholder' );
				if ( i.hasClass( 'password' ) ) {
					i.removeClass( 'password' );
					this.type = 'password';
				}
			}
		} ).blur( function() {
			i = $( this );
			if ( i.val() == '' || i.val() == i.attr( 'placeholder' ) ) {
				if ( this.type == 'password' ) {
					i.addClass( 'password' );
					this.type = 'text';
				}
				i.addClass( 'placeholder' ).val( i.attr( 'placeholder' ) );
			}
		} ).blur().parents( 'form' ).submit( function() {
			$( this ).find( '[placeholder]' ).each( function() {
				i = $( this );
				if ( i.val() == i.attr( 'placeholder' ) )
					i.val( '' );
			} )
		} );
	}

	// Lightbox effect for gallery
	$( '#primary' ).find( '.lightbox .gallery-item img' ).click( function() {
		$( '#lightbox' ).remove();

		var el = $( this ),
			full = el.data( 'full-image' ),
			caption = el.data( 'caption' ),
			next = el.data( 'next-image' ),
			prev = el.data( 'prev-image' ),
			count = $( '.gallery-item img' ).length,
			prev_text = ( 'img-0' != prev ) ? '<span class="prev-image" data-prev-image="' + prev + '">&larr;</span>' : '';
			next_text = ( 'img-' + ( count + 1 ) != next ) ? '<span class="next-image" data-next-image="' + next + '">&rarr;</span>' : '';

		$( '#page' ).append( '<div id="lightbox">' + prev_text + next_text + '<div class="lightbox-container"><img src="' + full + '" /><p>' + caption + '</p></div></div>' );
	} );

	$( '#page' )
		.on( 'click', '#lightbox', function() {
			$( this ).fadeOut();
		} )
		.on( 'click', '#lightbox .prev-image', function(e) {
			e.stopPropagation();
			var prev = $( this ).data( 'prev-image' );

			$( '.' + prev ).trigger( 'click' );
		} )
		.on( 'click', '#lightbox .next-image', function(e) {
			e.stopPropagation();
			var next = $( this ).data( 'next-image' );

			$( '.' + next ).trigger( 'click' );
		} )
		.on( 'click', '#lightbox img', function(e) {
			e.stopPropagation();
			$( '#lightbox .next-image' ).trigger( 'click' );
		} );

	// Mobile menu
	$( '#header' ).on( 'click', '#mobile-menu a', function(e) {
		var el = $( this ),
			div = el.data( 'div' ),
			speed = el.data( 'speed' );

		if ( el.hasClass( 'home' ) )
			return true;

		e.preventDefault();
		$(div).slideToggle(speed);
	} );

	// Footer height
	function footer_height() {
		f_height = $( '#footer-content' ).height();
		f_height = ( 0 != $( '#extended-footer' ).length ) ? f_height + $( '#extended-footer' ).height() + 11 : f_height;
		$( '#page' ).css({ marginBottom: -f_height + 'px' });
		$( '#main' ).css({ paddingBottom: f_height  + 'px' });
	}

	// Back to top button
	$( '#footer, #extended-footer' ).find( '.backtotop' ).click( function() {
		$( 'html, body' ).animate( { scrollTop : 0 }, 'slow' );
	} )

	// Prevent default behaviour
	$( 'a[href="#"]' ).click( function(e) {
		e.preventDefault();
	});

	// Add image anchor class
	$( 'a:has(img)' ).addClass('image-anchor');

	// Shortcode
	if ( theme_js_vars['tooltip'] )
		$( 'a[rel="tooltip"]' ).tooltip();

	if ( theme_js_vars['tabs'] ) {
		$( '.nav-tabs a' ).click( function(e) {
			e.preventDefault();
			$(this).tab( 'show' );
		} );
	}

} )( jQuery );
