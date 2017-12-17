// *** LICENSE ***
//
// This file is part of C60.
// Since 2016, by Timo Van Neerden.
// C60 is free software, under MIT/X11 Licence.

"use strict";

/* reproduces the PHP « date(#, 'c') » output format */
Date.prototype.dateToISO8601String  = function() {
	var padDigits = function padDigits(number, digits) {
		return Array(Math.max(digits - String(number).length + 1, 0)).join(0) + number;
	}

	var offsetMinutes = - this.getTimezoneOffset();
	var offsetHours = offsetMinutes / 60;
	var offset= "Z";
	if (offsetHours < 0)
		offset = "-" + padDigits((offsetHours.toString()).replace("-","") + ":00", 5);
	else if (offsetHours > 0)
		offset = "+" + padDigits(offsetHours  + ":00", 5);


	return this.getFullYear()
		+ "-" + padDigits((this.getMonth()+1),2)
		+ "-" + padDigits(this.getDate(),2)
		+ "T"
		+ padDigits(this.getHours(),2)
		+ ":" + padDigits(this.getMinutes(),2)
		+ ":" + padDigits(this.getSeconds(),2)
		//+ "." + padDigits(this.getMilliseconds(),2)
		+ offset;


}

/*Date.dateFromISO8601 = function(isoDateString) {
	var parts = isoDateString.match(/\d+/g);
	var isoTime = Date.UTC(parts[0], parts[1] - 1, parts[2], parts[3], parts[4], parts[5]);
	var isoDate = new Date(isoTime);
	return isoDate;       
}*/

Date.prototype.getWeekNumber = function () {
    var target  = new Date(this.valueOf());
    var dayNr   = (this.getDay() + 6) % 7;
    target.setDate(target.getDate() - dayNr + 3);
    var firstThursday = target.valueOf();
    target.setMonth(0, 1);
    if (target.getDay() != 4) {
        target.setMonth(0, 1 + ((4 - target.getDay()) + 7) % 7);
    }
    return 1 + Math.ceil((firstThursday - target) / 604800000);
}

/* date from YYYYMMDDHHIISS format */
Date.dateFromYMDHIS = function(d) {
	var d = new Date(d.substr(0, 4), d.substr(4, 2) - 1, d.substr(6, 2), d.substr(8, 2), d.substr(10, 2), d.substr(12, 2));
	//var d = d.substr(0, 4) + '' + d.substr(4, 2) - 1 + d.substr(6, 2) + d.substr(8, 2) + d.substr(10, 2) + d.substr(12, 2);
	return d;
}


/*
	menu icons : onclick.
*/

// close already open menus, but not the current menu
function closeOpenMenus(target) {
	// close already open menus, but not the current menu
	var openMenu = document.querySelectorAll('#top > [id] > ul.visible');
	for (var i=0, len=openMenu.length ; i<len ; i++) {
		if (!openMenu[i].parentNode.contains(target)) openMenu[i].classList.remove('visible');
	}

}
window.addEventListener('click', function(e) {
	var openMenu = document.querySelectorAll('#top > [id] > ul.visible');
	// no open menus: abord
	if (!openMenu.length) return;
	// open menus ? close them.
	else closeOpenMenus(null);
});

// add "click" listeners on the list of menus
['nav-acc', 'notif-icon'].forEach(function(elem) {
	document.getElementById(elem).addEventListener('click', function(e) {
		closeOpenMenus(e.target);
		var menu = document.getElementById(elem).querySelector('ul');
		if (this === (e.target)) menu.classList.toggle('visible');
		e.stopPropagation();
	});
});

/*
	cancel button on forms.
*/
function goToUrl(pagecible) {
	window.location = pagecible;
}


/*
	in page maintenance : switch visibility of forms.
*/
function switch_form(activeForm) {
	var form_export = document.getElementById('form_export');
	var form_import = document.getElementById('form_import');
	var form_optimi = document.getElementById('form_optimi');
	form_export.style.display = form_import.style.display = form_optimi.style.display = 'none';
	document.getElementById(activeForm).style.display = 'block';
}

function switch_export_type(activeForm) {
	var e_json = document.getElementById('e_json');
	var e_html = document.getElementById('e_html');
	var e_zip = document.getElementById('e_zip');
	e_json.style.display = e_html.style.display = e_zip.style.display = 'none';
	document.getElementById(activeForm).style.display = 'block';
}

function hide_forms(blocs) {
	var radios = document.getElementsByName(blocs);
	var e_json = document.getElementById('e_json');
	var e_html = document.getElementById('e_html');
	var e_zip = document.getElementById('e_zip');
	var checked = false;
	for (var i = 0, length = radios.length; i < length; i++) {
		if (!radios[i].checked) {
			var cont = document.getElementById('e_'+radios[i].value);
			while (cont.firstChild) {cont.removeChild(cont.firstChild);}
		}
	}
}


/* for links : hide the FAB button when focus on link field (more conveniant for mobile UX) */
function hideFAB() {
	if (document.getElementById('fab')) {
		document.getElementById('fab').classList.add('hidden');
	}
}
function unHideFAB() {
	if (document.getElementById('fab')) {
		document.getElementById('fab').classList.remove('hidden');
	}
}

/* for several pages: eventlistener to show/hide FAB on scrolling (avoids FAB from beeing in the way) */
function scrollingFabHideShow() {
	if ((document.body.getBoundingClientRect()).top > scrollPos) {
		unHideFAB();
	} else {
		hideFAB();
	}
	scrollPos = (document.body.getBoundingClientRect()).top;
}


/**************************************************************************************************************************************
	*********        ****          ****
	***********    ********      ********
	***     ***  ***      ***  ***      ***
 	***     ***  ***           ***
	**********    **********    **********
	********      **********    **********
	***  ***              ***           ***
	***   ***    ***      ***  ***      ***
	***    ***     ********      ********
	***     ***      ****          ****

	RSS PAGE HANDLING
**************************************************************************************************************************************/

// animation loading (also used in images wall/slideshow)
function loading_animation(onoff) {
	var notifNode = document.getElementById('counter');
	if (onoff == 'on') {
		notifNode.style.display = 'inline-block';
	}
	else {
		notifNode.style.display = 'none';
	}
	return false;
}

/* open-close rss-folder */
function hideFolder(btn) {
	btn.parentNode.parentNode.classList.toggle('open');
	return false;
}



function RssReader() {
	var _this = this;

	/***********************************
	** Some properties & misc actions
	*/
	// init JSON List
	this.feedList = rss_entries.list;

	// init local "mark as read" buffer
	this.readQueue = {"count": "0", "urlList": []};

	// get some DOM elements
	this.postsList = document.getElementById('post-list');
	this.feedsList = document.getElementById('feed-list');
	this.notifNode = document.getElementById('message-return');

	// init the « open-all » toogle-button.
	this.openAllButton = document.getElementById('openallitemsbutton');
	this.openAllButton.addEventListener('click', function(){ _this.openAll(); });

	// init the « mark as read button ».
	this.markAsReadButton = document.getElementById('markasread');
	this.markAsReadButton.addEventListener('click', function(){ _this.markAsRead(); });

	// init the « refresh all » button event
	this.refreshButton = document.getElementById('refreshAll');
	this.refreshButton.addEventListener('click', function(){ _this.refreshAllFeeds(); });

	// init the « delete old » button event
	this.deleteButton = document.getElementById('deleteOld');
	this.deleteButton.addEventListener('click', function(){ _this.deleteOldFeeds(); });

	// init the « add new feed » button event
	this.fabButton = document.getElementById('fab');
	this.fabButton.addEventListener('click', function(){ _this.addNewFeed(); });

	// Global Page listeners
	// onkeydown : detect "open next/previous" action with keyboard
	window.addEventListener('keydown', function(e) { _this.kbActionHandle(e); } );

	// beforeunload : to send a "mark as read" request when closing the tab or reloading whole page
	window.addEventListener("beforeunload", function(e) { _this.markAsReadOnUnloadXHR(); } );

	var DateTimeFormat = new Intl.DateTimeFormat('fr', {weekday: "short", month: "short", day: "numeric", hour: "numeric", minute: "numeric"});


	/***********************************
	** The HTML tree builder :
	** Rebuilts the whole list of posts.
	*/
	this.rebuiltTree = function(RssPosts) {
		// empties the actual list
		while (this.postsList.firstChild) {
			 this.postsList.removeChild(this.postsList.firstChild);
		}

		if (0 === RssPosts.length) return false;

		// populates the new list
		for (var i = 0, len = RssPosts.length ; i < len ; i++) {
			var item = RssPosts[i];

			// new list element
			var li = document.createElement("li");
			li.id = 'i_'+item.id;
			li.dataset.sitehash = item.feedhash;
			if (0 === item.statut) { li.classList.add('read'); }

			// li-head: title-block
			var title = document.createElement("div");
			title.classList.add('post-title');

			// site name
			var site = document.createElement("div");
			site.classList.add('site');
			site.appendChild(document.createTextNode(item.sitename));
			title.appendChild(site);

			// post title
			var titleLink = document.createElement("a");
			titleLink.href = item.link;
			titleLink.title = item.title;
			titleLink.target = "_blank";
			titleLink.appendChild(document.createTextNode(item.title));
			titleLink.dataset.id = li.id;
			titleLink.addEventListener('click', function(e){ if(!_this.openThisItem(document.getElementById(this.dataset.id))) e.preventDefault(); } );
			title.appendChild(titleLink);

			// post date
			var date = document.createElement("div");
			date.classList.add('date');
			date.appendChild(document.createTextNode(DateTimeFormat.format(Date.dateFromYMDHIS(item.datetime))));
			title.appendChild(date);

			// post share link & fav link
			var share = document.createElement("div");
			share.classList.add('share');
			var shareLink = document.createElement("a");
			shareLink.href = 'links.php?url='+item.link;
			shareLink.target = "_blank";
			shareLink.classList.add("lien-share");
			share.appendChild(shareLink);
			var favLink = document.createElement("a");
			favLink.href = '#';
			favLink.target = "_blank";
			favLink.classList.add("lien-fav");
			favLink.dataset.isFav = item.fav;
			favLink.dataset.favId = item.id;
			favLink.addEventListener('click', function(e){ _this.markAsFav(this); e.preventDefault(); } );
			share.appendChild(favLink);

			title.appendChild(share);
			li.appendChild(title);

			// bloc with main content of feed in a comment (it’s uncomment when open, to defer media loading).
			var content = document.createElement("div");
			content.classList.add('rss-item-content');
			var comment = document.createComment(item.content);
			content.appendChild(comment);
			li.appendChild(content);

			var hr = document.createElement("hr");
			hr.classList.add('clearboth');
			li.appendChild(hr);

			this.postsList.appendChild(li);
		}

		// displays the number of items (local counter)
		var count = document.querySelector('#post-counter');
		if (count.firstChild) {
			count.firstChild.nodeValue = RssPosts.length;
			//count.dataset.nbrun = RssPosts.length;
		} else {
			count.appendChild(document.createTextNode(RssPosts.length));
			//count.dataset.nbrun = RssPosts.length;
		}

		return false;
	}
	// init the whole DOM list
	this.rebuiltTree(this.feedList);


	/***********************************
	** Methods to "open" elements (all, one, next…)
	*/
	// open ALL the items
	this.openAll = function() {
		var posts = this.postsList.querySelectorAll('li');
		if (!this.openAllButton.classList.contains('unfold')) {
			for (var i=0, len=posts.length ; i<len ; i++) {
				posts[i].classList.add('open-post');
				var content = posts[i].querySelector('.rss-item-content');
				if (content.childNodes[0] && content.childNodes[0].nodeType == 8) {
					content.innerHTML = content.childNodes[0].data;
				}
			}
			this.openAllButton.classList.add('unfold');
		} else {
			for (var i=0, len=posts.length ; i<len ; i++) {
				posts[i].classList.remove('open-post');
			}
			this.openAllButton.classList.remove('unfold');
		}
		return false;
	}

	// open clicked item
	this.openThisItem = function(theItem) {
		if (theItem.classList.contains('open-post')) { return true; }
		// close open posts
		var posts = this.postsList.querySelectorAll('.open-post');
		for (var i=0, len=posts.length ; i<len ; i++) {
			posts[i].classList.remove('open-post');
		}
		this.openAllButton.classList.remove('unfold');
		// open this post
		theItem.classList.add('open-post');

		// unhide the content
		var content = theItem.querySelector('.rss-item-content');
		if (content.childNodes[0].nodeType == 8) {
			content.innerHTML = content.childNodes[0].data;
		}

		// jump to post (anchor + 120px)
		var rect = theItem.getBoundingClientRect();
		var isVisible = ( (rect.top < 120) || (rect.bottom > window.innerHeight) ) ? false : true ;
		if (!isVisible) {
			window.location.hash = theItem.id;
			window.scrollBy(0, -120);
		}

		// mark as read in DOM and saves for mark as read in DB
		if (!theItem.classList.contains('read')) {
			this.markAsReadPost(theItem);
			theItem.classList.add('read');
		}
		return false;
	}


	// handle keyboard actions
	this.kbActionHandle = function(e) {
		// first actual open item
		var openPost = this.postsList.querySelector('li.open-post');
		// ... or first post if list is empty
		if (!openPost) { openPost = this.postsList.querySelector('li'); var isFirst = true; }
		// ... or return if no post in list
		if (!openPost) return false;

		// down
		if (e.keyCode == '40' && e.ctrlKey && openPost.nextSibling) {
			if (isFirst)
				this.openThisItem(openPost);
			else
				this.openThisItem(openPost.nextSibling);
			e.preventDefault();
		}
		// up
		if (e.keyCode == '38' && e.ctrlKey && openPost.previousSibling) {
			this.openThisItem(openPost.previousSibling);
			e.preventDefault();
		}
	}


	/***********************************
	** Methods to "sort" elements (by site, folder, favs…)
	*/
	// create list of items matching the selected site
	this.sortItemsBySite = function(theSite) {
		var newList = new Array();
		for (var i = 0, len = this.feedList.length ; i < len ; i++) {
			if (this.feedList[i].feedhash == theSite) { // if match
				newList.push(this.feedList[i]);
			}
		}
		// unhighlight previously highlighted site
		if (document.querySelector('.active-site')) { document.querySelector('.active-site').classList.remove('active-site'); }
		// and highlight new site
		document.querySelector('#feed-list li[data-feed-hash="'+theSite+'"]').classList.add('active-site');
		window.location.hash = '';
		this.rebuiltTree(newList);
		this.openAllButton.classList.remove('unfold');
		return false;
	}


	// create list of items matching the selected folder
	this.sortItemsByFolder = function(theFolder) {
		var newList = new Array();
		for (var i = 0, len = this.feedList.length ; i < len ; i++) {
			if (this.feedList[i].folder == theFolder) {
				newList.push(this.feedList[i]);
			}
		}
		// unhighlight previously highlighted site
		if (document.querySelector('.active-site')) { document.querySelector('.active-site').classList.remove('active-site'); }
		// highlight selected folder
		document.querySelector('#feed-list li[data-folder="'+theFolder+'"]').classList.add('active-site');
		window.location.hash = '';
		this.rebuiltTree(newList);
		this.openAllButton.classList.remove('unfold');
		return false;
	}


	// rebuilt the list with all the items
	this.sortAll = function() {
		// unhighlight previously selected site
		document.querySelector('.active-site').classList.remove('active-site');
		// highlight favs
		document.querySelector('.all-feeds').classList.add('active-site');

		window.location.hash = '';
		this.rebuiltTree(this.feedList);
		this.openAllButton.classList.remove('unfold');
		return false;
	}


	// Create list with the favs
	this.sortFavs = function() {
		var newList = new Array();
		// create list of items that are favs
		for (var i = 0, len = this.feedList.length ; i < len ; i++) {
			if (this.feedList[i].fav == 1) {
				newList.push(this.feedList[i]);
			}
		}
		// unhighlight previously selected site
		if (document.querySelector('.active-site')) { document.querySelector('.active-site').classList.remove('active-site'); }
		// highlight favs
		document.querySelector('.fav-feeds').classList.add('active-site');
		window.location.hash = '';
		this.rebuiltTree(newList);
		this.openAllButton.classList.remove('unfold');
		return false;
	}


	/***********************************
	** Methods to "mark as read" item in the local list and on screen
	*/
	this.markAsRead = function() {
		var markWhat = this.feedsList.querySelector('.active-site');

		// Mark ALL as read.
		if (markWhat.classList.contains('all-feeds')) {
			// ask confirmation
			if (!confirm("Tous les éléments seront marqués comme lus ?")) {
				loading_animation('off');
				return false;
			}
			// send XHR
			if (!this.markAsReadXHR('all', 'all')) return false;

			// mark items as read in list
			for (var i = 0, len = this.feedList.length ; i < len ; i++) { this.feedList[i].statut = 0; }

			// recount unread items in the list of sites/folders
			for (var i = 0, liList = document.querySelectorAll('#feed-list li:not(.fav-feeds)'), len = liList.length ; i < len ; i++) {
				liList[i].dataset.nbrun = 0;
				liList[i].querySelector('.counter').firstChild.nodeValue = '(0)';
			}
			this.sortAll();
		}

		// Mark one FOLDER as read
		else if (markWhat.classList.contains('feed-folder')) {
			var folder = markWhat.dataset.folder;

			// send XHR
			if (!this.markAsReadXHR('folder', folder)) return false;

			// update global counter by substracting unread items from the folder
			var gcount = document.getElementById('global-post-counter');
			gcount.dataset.nbrun -= markWhat.dataset.nbrun;
			gcount.firstChild.nodeValue = '('+gcount.dataset.nbrun+')';

			// mark items as read in list
			for (var i = 0, len = this.feedList.length ; i < len ; i++) {
				if (this.feedList[i].folder == folder) {
					this.feedList[i].statut = 0;
				}
			}

			// mark 0 for that folder folder’s unread counters
			markWhat.dataset.nbrun = 0;
			markWhat.querySelector('.counter').firstChild.nodeValue = '(0)';
			// mark 0 for the sites in that folder also
			var sitesInFolder = markWhat.querySelectorAll('ul > li');
			for (var i = 0, len = sitesInFolder.length ; i < len ; i++) {
				sitesInFolder[i].dataset.nbrun = 0;
				sitesInFolder[i].querySelector('.counter').firstChild.nodeValue = '(0)';
			}

			this.sortItemsByFolder(folder);
		}

		// else… mark one SITE as read
		else if (markWhat.classList.contains('feed-site')) {
			var siteHash = markWhat.dataset.feedHash;
			var site = this.feedsList.querySelector('li[data-feed-hash="'+siteHash+'"]').title;

			// send XHR
			if (!this.markAsReadXHR('site', site)) return false;

			// update global counter by substracting unread items from the folder
			var gcount = document.getElementById('global-post-counter');
			gcount.dataset.nbrun -= markWhat.dataset.nbrun;
			gcount.firstChild.nodeValue = '('+gcount.dataset.nbrun+')';

			// if site is in a folder, update amount of unread for that folder too
			var parentFolder = markWhat.parentNode.parentNode;
			if (parentFolder.dataset.folder) {
				parentFolder.dataset.nbrun -= markWhat.dataset.nbrun;
				parentFolder.querySelector('.counter').firstChild.nodeValue = '('+parentFolder.dataset.nbrun+')';
			}

			// mark items as read in list
			for (var i = 0, len = this.feedList.length ; i < len ; i++) {
				if (this.feedList[i].feedhash == siteHash) {
					this.feedList[i].statut = 0;
				}
			}

			// mark 0 for that folder folder’s unread counters
			markWhat.dataset.nbrun = 0;
			markWhat.querySelector('.counter').firstChild.nodeValue = '(0)';

			this.sortItemsBySite(siteHash);
		}
	}


	// This is called when a post is opened (for the first time)
	// counters are updated here
	this.markAsReadPost = function(thePost) {
		// add thePost to local read posts buffer, to be send as XHR when full
		this.readQueue.urlList.push(thePost.id.substr(2));
		// if 10 items in queue, send XHR request and reset list to zero.
		if (this.readQueue.urlList.length >= 10) {
			var list = this.readQueue.urlList;
			this.markAsReadXHR('postlist', JSON.stringify(list));
			this.readQueue.urlList = [];
		}

		// mark a read in list
		for (var i = 0, len = this.feedList.length ; i < len ; i++) {
			if (this.feedList[i].id == thePost.id.substr(2)) {
				this.feedList[i].statut = 0;
				break;
			}
		}
		// decrement global counter
		var gcount = document.getElementById('global-post-counter');
		gcount.dataset.nbrun -= 1;
		gcount.firstChild.nodeValue = '('+gcount.dataset.nbrun+')';
		// decrement site
		var site = this.feedsList.querySelector('li[data-feed-hash="'+thePost.dataset.sitehash+'"]');
		site.dataset.nbrun -= 1;
		site.querySelector('.counter').firstChild.nodeValue = '('+site.dataset.nbrun+')';
		// decrement folder (if any)
		var parentFolder = site.parentNode.parentNode;
		if (parentFolder.dataset.folder) {
			parentFolder.dataset.nbrun -= 1;
			parentFolder.querySelector('.counter').firstChild.nodeValue = '('+parentFolder.dataset.nbrun+')';
		}
	}


	/***********************************
	** Methods to init and send the XHR request
	*/
	// Mark as read by user input.
	this.markAsReadXHR = function(marType, marWhat) {
		loading_animation('on');
		var notifDiv = document.createElement('div');

		var xhr = new XMLHttpRequest();
		xhr.open('POST', '_rss.ajax.php', true);

		// onload
		xhr.onload = function() {
			var resp = this.responseText;
			loading_animation('off');
			return (resp.indexOf("Success") == 0);
		};

		// onerror
		xhr.onerror = function(e) {
			loading_animation('off');
			notifDiv.appendChild(document.createTextNode('AJAX Error ' +e.target.status));
			notifDiv.classList.add('no_confirmation');
			document.getElementById('top').appendChild(notifDiv);
			notfiNode.appendChild(document.createTextNode(resp));
		};

		// prepare and send FormData
		var formData = new FormData();
		formData.append('token', token);
		formData.append('mark-as-read', marType);
		formData.append('mark-as-read-data', marWhat);
		xhr.send(formData);

		return true;
	}


	// mark as read on page-unload (transparent for user)
	this.markAsReadOnUnloadXHR = function() {
		if (this.readQueue.urlList.length == 0) return true;

		var xhr = new XMLHttpRequest();
		xhr.open('POST', '_rss.ajax.php', false);

		// onload
		xhr.onload = function() {
			var resp = this.responseText;
			return (resp.indexOf("Success") == 0);
		};

		// prepare and send FormData
		var formData = new FormData();
		formData.append('token', token);
		formData.append('mark-as-read', 'postlist');
		formData.append('mark-as-read-data', JSON.stringify(this.readQueue.urlList));
		xhr.send(formData);
		return true;
	}


	/***********************************
	** Methods to mark a post a favorite
	*/
	this.markAsFav = function(thePost) {

		// mark as fav on screen and in favCounter
		thePost.dataset.isFav = 1 - parseInt(thePost.dataset.isFav);
		var favCounter = document.getElementById('favs-post-counter')
		favCounter.dataset.nbrun = parseInt(favCounter.dataset.nbrun) + ((thePost.dataset.isFav == 1) ? 1 : -1 );
		favCounter.firstChild.nodeValue = '('+favCounter.dataset.nbrun+')';

		// mark as fav in local list
		for (var i = 0, len = this.feedList.length ; i < len ; i++) {
			if (this.feedList[i].id == thePost.dataset.favId) {
				this.feedList[i].fav = thePost.dataset.isFav;
				break;
			}
		}

		// mark as fav in DB (with XHR)
		loading_animation('on');

		var notifDiv = document.createElement('div');

		var xhr = new XMLHttpRequest();
		xhr.open('POST', '_rss.ajax.php', true);

		// onload
		xhr.onload = function() {
			var resp = this.responseText;
			loading_animation('off');
			return (resp.indexOf("Success") == 0);
		};

		// onerror
		xhr.onerror = function(e) {
			var resp = this.responseText;
			loading_animation('off');
			notifDiv.appendChild(document.createTextNode('AJAX Error ' +e.target.status));
			notifDiv.classList.add('no_confirmation');
			document.getElementById('top').appendChild(notifDiv);
			notfiNode.appendChild(document.createTextNode(resp));
			return false;
		};

		// prepare and send FormData
		var formData = new FormData();
		formData.append('token', token);
		formData.append('mark-as-fav', 1);
		formData.append('url', thePost.dataset.favId);
		xhr.send(formData);
		return false;
	}


	/***********************************
	** Methods to refresh the feeds
	** This call is long, also it updates gradually on screen.
	**
	*/
	this.refreshAllFeeds = function() {
		var _refreshButton = this.refreshButton;
		// if refresh ongoing : abbord !
		if (_refreshButton.dataset.refreshOngoing == 1) {
			return false;
		} else {
			_refreshButton.dataset.refreshOngoing = 1;
		}
		// else refresh
		loading_animation('on');

		// prepare XMLHttpRequest
		var xhr = new XMLHttpRequest();
		xhr.open('POST', '_rss.ajax.php', true);

		// Counts the feeds that have been updated already and displays it like « 10/42 feeds »
		var glLength = 0;
		_this.notifNode.appendChild(document.createTextNode(''));
		xhr.onprogress = function() {
			if (glLength != this.responseText.length) {
				var posSpace = (this.responseText.substr(0, this.responseText.length-1)).lastIndexOf(" ");
				_this.notifNode.firstChild.nodeValue = this.responseText.substr(posSpace);
				glLength = this.responseText.length;
			}
		}
		// when finished : displays amount of items gotten.
		xhr.onload = function() {
			var resp = this.responseText;
			// grep new feeds
			var newFeeds = JSON.parse(resp.substr(resp.indexOf("Success")+7));
			// update status
			_this.notifNode.firstChild.nodeValue = newFeeds.length+' new feeds'; // TODO $[lang]

			// in not empty, add them to list & display them
			if (0 != newFeeds.length) {
				_this.rebuiltTree(newFeeds);
				for (var i = 0, len = newFeeds.length ; i < len ; i++) {
					_this.feedList.unshift(newFeeds[i]); // TODO : recount elements (site, folder, total)
				}
			}
			_refreshButton.dataset.refreshOngoing = 0;
			loading_animation('off');
			return false;
		};

		xhr.onerror = function() {
			_this.notifNode.appendChild(document.createTextNode(this.responseText));
			loading_animation('off');
			_refreshButton.dataset.refreshOngoing = 0;
		};

		// prepare and send FormData
		var formData = new FormData();
		formData.append('token', token);
		formData.append('refresh_all', 1);
		xhr.send(formData);
		return false;
	}


	/***********************************
	** Method to delete old feeds from DB
	*/
	this.deleteOldFeeds = function() {
		// ask confirmation
		if (!confirm("Les vieilles entrées seront supprimées ?")) {
			loading_animation('off');
			return false;
		}

		loading_animation('on');
		var notifDiv = document.createElement('div');

		var xhr = new XMLHttpRequest();
		xhr.open('POST', '_rss.ajax.php', true);

		xhr.onload = function() {
			var resp = this.responseText;
			if (resp.indexOf("Success") == 0) {
				// adding notif
				notifDiv.textContent = BTlang.confirmFeedClean;
				notifDiv.classList.add('confirmation');
			} else {
				notifDiv.textContent = 'Error: '+resp;
				notifDiv.classList.add('no_confirmation');
			}
			document.getElementById('top').appendChild(notifDiv);
			loading_animation('off');
		};
		xhr.onerror = function(e) {
			loading_animation('off');
			// adding notif
			notifDiv.textContent = BTlang.errorPhpAjax + e.target.status;
			notifDiv.classList.add('no_confirmation');
			document.getElementById('top').appendChild(notifDiv);
		};

		// prepare and send FormData
		var formData = new FormData();
		formData.append('token', token);
		formData.append('delete_old', 1);
		xhr.send(formData);
		return false;
	}



	/***********************************
	** Method to add a new feed (promt for URL and send to server)
	*/
	this.addNewFeed = function() {
		var newLink = window.prompt(BTlang.rssJsAlertNewLink, '');
		// if empty string : stops here
		if (!newLink) return false;
		// ask folder
		var newFolder = window.prompt(BTlang.rssJsAlertNewLinkFolder, '');

		var notifDiv = document.createElement('div');
		loading_animation('on');

		var xhr = new XMLHttpRequest();
		xhr.open('POST', '_rss.ajax.php');

		xhr.onload = function() {
			var resp = this.responseText;
			// if error : stops
			if (resp.indexOf("Success") == -1) {
				loading_animation('off');
				_this.notifNode.appendChild(document.createTextNode(this.responseText));
				return false;
			}

			// recharge la page en cas de succès
			loading_animation('off');
			_this.notifNode.appendChild(document.createTextNode('FLux ajouté, rechargez la page.'));
			return false;
		};

		xhr.onerror = function(e) {
			loading_animation('off');
			// adding notif
			notifDiv.textContent = 'Une erreur PHP/Ajax s’est produite :'+e.target.status;
			notifDiv.classList.add('no_confirmation');
			document.getElementById('top').appendChild(notifDiv);
		};
		// prepare and send FormData
		var formData = new FormData();
		formData.append('token', token);
		formData.append('add-feed', newLink);
		formData.append('add-feed-folder', newFolder);
		xhr.send(formData);
		return false;
	}

};


/* in RSS config : mark a feed as "to remove" */
function markAsRemove(link) {
	var li = link.parentNode.parentNode;
	li.classList.add('to-remove');
	li.getElementsByClassName('remove-feed')[0].value = 0;
}
function unMarkAsRemove(link) {
	var li = link.parentNode.parentNode;
	li.classList.remove('to-remove');
	li.getElementsByClassName('remove-feed')[0].value = 1;
}


/**************************************************************************************************************************************
	CANVAS FOR index.php GRAPHS
**************************************************************************************************************************************/
function respondCanvas(){
	for (var i=0, len=containers.length; i<len ; i++) {
		containers[i].querySelector('canvas').width = parseInt(containers[i].querySelector('.graphique').getBoundingClientRect().width);
		draw(containers[i]);
	}
}

function draw(container) {
	var c = container.querySelector('canvas');
	var months = container.querySelectorAll('.graphique .month');
	var ctx = c.getContext("2d");
	var cont = {
		x:container.getBoundingClientRect().left,
		y:container.getBoundingClientRect().top
	};

	// strokes the background lines at 0%, 25%, 50%, 75% and 100%.
	ctx.beginPath();
	for (var i=months.length-1 ; i>=0 ; i--) {
		if (months[i].getBoundingClientRect().top < months[0].getBoundingClientRect().bottom) {
			var topLeft = months[i].getBoundingClientRect().left -15;
			break;
		}
	}

	var coordScale = { x:topLeft, xx:months[1].getBoundingClientRect().left };
	for (var i = 0; i < 5 ; i++) {
		ctx.moveTo(coordScale.x, i*c.height/4 +1);
		ctx.lineTo(coordScale.xx, i*c.height/4 +1);
		ctx.strokeStyle = "rgba(0, 0, 0, .05)";
	}
	ctx.stroke();

	// strokes the lines of the chart
	ctx.beginPath();
	for (var i=1, len=months.length ; i<len ; i++) {
		var coordsNew = months[i].getBoundingClientRect();
		if (i == 1) {
			ctx.moveTo(coordsNew.left - cont.x + coordsNew.width/2, coordsNew.top - cont.y);
		} else {
			if (coordsNew.top - cont.y <= 150)
			ctx.lineTo(coordsNew.left - cont.x + coordsNew.width/2, coordsNew.top - cont.y);
		}
	}
	ctx.lineWidth = 2;
	ctx.strokeStyle = "rgba(33,150,243,1)";
	ctx.stroke();
	ctx.closePath();

	// fills the chart
	ctx.beginPath();
	for (var i=1, len=months.length ; i<len ; i++) {
		var coordsNew = months[i].getBoundingClientRect();
		if (i == 1) {
			ctx.moveTo(coordsNew.left - cont.x + coordsNew.width/2, 150);
			ctx.lineTo(coordsNew.left - cont.x + coordsNew.width/2, coordsNew.top - cont.y);
		} else {
			if (coordsNew.top - cont.y <= 150) {
				ctx.lineTo(coordsNew.left - cont.x + coordsNew.width/2, coordsNew.top - cont.y);
				var coordsOld = coordsNew;
			}
		}
	}
	ctx.lineTo(coordsOld.left - cont.x + coordsOld.width/2, 150);
	ctx.fillStyle = "rgba(33,150,243,.2)";
	ctx.fill();
	ctx.closePath();
}
