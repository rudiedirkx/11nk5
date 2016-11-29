function notice(msg, fail) {
	var li = $('#notices').removeClass('hide').append('<li>' + msg + '</li>').find('> :last')
	fail && li.addClass('fail')
}

function copy_url(id, tags) {
	if ( tags || (tags = prompt('Tags:', '')) ) {
		$.get(document.URI, { "url": id, "tags": tags }, function(t) {
			if ( 'OK' != t.substr(0,2) ) {
				// Error
				notice(t, 1);
			}
			else {
				// Copy success
				notice('Link was copied to ' + t.substr(2) + ' other tags!');
			}
		})
	}
}

function edit_title(id, title) {
	if ( title || (title = title = prompt('Title:', $('#url_'+id).html())) ) {
		// save to server
		$.get(document.URI, { "url": id, "title": title }, function(t) {
			if ( 'OK' == t ) {
				document.location.reload()
			}
			else {
				notice(t, 1)
			}
		})
	}
}

function displayPopup(x, y) {
	$('#url_popup').css({
		top: y+'px',
		left: x+'px'
	}).addClass('show')
}

function hidePopup() {
	$('#url_popup').removeClass('show')
}

jQuery(function($) {
	var context

	// remember context
	$('a').on('focus', function(e) {
		context = $(e.target)
	})

	// open context menu
	if (!('HTMLMenuItemElement' in window)) {
		$('#urls a').on('contextmenu', function(e) {
			e.preventDefault()

			displayPopup(e.pageX, e.pageY)
		})
	}

	// close context menu
	$(document).on('keydown', function(e, t) {
		if ( 27 == e.keyCode ) {
			hidePopup()
		}
	})
	$(document).on('click', function(e, t) {
		hidePopup()
	})

	// context menu commands
	$('#cmd-open').on('click', function(e) {
		e.preventDefault()
		document.location = '/tags/~out/' + context.data('id') + '/'
	})
	$('#cmd-edit').on('click', function(e) {
		e.preventDefault()
		setTimeout("edit_title(" + context.data('id') + ")", 10)
	})
	$('#cmd-copy').on('click', function(e) {
		e.preventDefault()
		setTimeout("copy_url(" + context.data('id') + ")", 10)
	})

	// close notices
	$('#notices').on('click', function(e) {
		$(this).addClass('hide')
	})
})
