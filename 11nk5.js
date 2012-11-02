//(function() {

	var db = {
		exist: function() {
			sessionStorage.renamed || (sessionStorage.renamed = '{}')
		},
		rename: function(id, title) {
			this.exist()
			var renamed = this.renamed()
			renamed[id] = title
			sessionStorage.renamed= JSON.stringify(renamed)
		},
		renamed: function() {
			this.exist()
			return JSON.parse(sessionStorage.renamed)
		}
	}

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
			// async update
			$('#url_'+id).html(title);
			db.rename(id, title)
			// save to server
			$.get(document.URI, { "url": id, "title": title }, function(t) {
				if ( 'OK' != t ) {
					// Error
					notice(t, 1);
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

		var renamed = db.renamed()
		for ( id in renamed ) {
			$('#url_'+id).html(renamed[id])
		}

		// <command> polyfill
		$('command').each(function(i, cmd) {
			cmd = $(cmd)
			cmd.replaceWith('<a id="' + cmd.attr('id') + '" href="#" onclick="return false">' + cmd.attr('label') + '</a>')
		})

		// open context menu
		$('#urls a').on('contextmenu', function(e) {
			e.preventDefault()

			context = $(e.target)
			displayPopup(e.pageX, e.pageY);
		})

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

//})()