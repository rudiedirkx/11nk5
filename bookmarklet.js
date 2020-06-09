javascript:
	(document.head||document.documentElement).appendChild((function(el, tags) {
		if (!tags) return;
		var surl = '__BASE__?url=' + encodeURIComponent(location.href) + '&title=' + encodeURIComponent(document.title) + '&tags=' + encodeURIComponent(tags);
		var div = document.createElement('div');
		div.className = 'loading-11nk5';
		div.innerHTML = '<a href="' + surl + '" target="_blank">. . .</a>';
		div.setAttribute('style', 'z-index: 2000999998; position: fixed; left: 20px; top: 50px; border: solid 20px black; padding: 10px 20px; background: white; color: black; font-size: 30px;');
		document.body.insertBefore(div, document.body.firstElementChild);
		el.src = surl;
		div.onclick = function() {
			this.remove();
		};
		return el;
	})((document.createElement||Document.prototype.createElement).call(document, 'script'), prompt('Tags:', '')));
	void(0);
