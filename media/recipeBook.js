(function(vWin) {
	'use strict';
	var vDoc = vWin.document,
        vBody = vDoc.querySelector('body'),
        vDialog = vDoc.getElementById('recipe-card'),
		vScrollTop = vDoc.querySelector('.scroll-top'),
		recipeCard;

	function ajaxGet(url, callback) { // ajax load resource
		var xmlHttp = new XMLHttpRequest(), data;
		xmlHttp.onreadystatechange = function() {
			if (xmlHttp.readyState === 4 && xmlHttp.status === 200) {
				try {
					data = JSON.parse(xmlHttp.responseText);
				} catch (err) {
					if (vWin.console) {
						console.warn(err.message + ' in ' + xmlHttp.responseText);
					}
					return;
				}
				callback(data);
			}
		};
		xmlHttp.open('GET', url, true);
		xmlHttp.send();
	}

	function attribute(vObj, attr, val) { // get/set/remove attribute of DOM element
		if (typeof(attr) !== 'undefined') {
			if (typeof(val) !== 'undefined' && val !== false) { // set
				vObj.setAttribute(attr, (val === true) ? attr : val);
				return;
			} else if (val === false) { // remove
				vObj.removeAttribute(attr);
				return;
			} else { // get
				return vObj.getAttribute(attr);
			}
		}
		if (vWin.console) {
			console.error('Cannot process attribute "' + attr + '" of', vObj);
		}
		throw new Error('Attribute of "' + attr + '" undefined.');
	}

	function getScrollTop() { // get window scroll position
		return (typeof(pageYOffset) !== 'undefined') ? pageYOffset :
			vDoc[(vDoc.documentElement.clientHeight)? 'documentElement' : 'body'];
	}

	function dateFormat(str) { // human readable date
		if (!str || str === '(n/a)') {
			return '(n/a)';
		}
		str = new Date(str);
		return str.getFullYear() + '-' +
			((str.getMonth() < 9) ? '0' : '') + (str.getMonth() + 1) + '-' +
			((str.getDate() < 10) ? '0' : '') + str.getDate();
	}

	vDoc.addEventListener('DOMContentLoaded', function() { // document ready
		vDoc.querySelector('.modal-mask').addEventListener('click', function(event) { // hide modal, mask
			event.stopPropagation();
			vBody.classList.remove('modal-open');
            attribute(vDialog, 'open', false);
            vDialog.close();
		});

		vDoc.getElementById('modal-close').addEventListener('click', function(event) { // hide modal, button
			event.stopPropagation();
			vBody.classList.remove('modal-open');
            attribute(vDialog, 'open', false);
			vDialog.close();
		});

		vDoc.querySelector('.scroll-top').addEventListener('click', function() { // scroll to top
			vWin.scrollTo(0, 0);
		});

		vWin.addEventListener('scroll', function() { // window scroll
			vScrollTop.classList[(getScrollTop() > 200) ? 'add' : 'remove']('show');
		});
	});

	Vue.component('recipe-book', { // register the component
		template: '#book-template',
		replace: true,
		props: {
			data: Array,
			columns: Array,
			filterKey: String,
			lastCard: null
		},
		data: function() {
			var sortOrders = {};

			this.columns.forEach(function(key) {
				sortOrders[key] = 1;
			});

			return {
				sortKey: '',
				sortOrders: sortOrders
			};
		},
		computed: {
			filteredData: function() {
				var sortKey = this.sortKey,
					filterKey = this.filterKey && this.filterKey.toLowerCase(),
					columns = this.columns,
					order = this.sortOrders[sortKey] || 1,
					data = this.data;

				if (filterKey) {
					data = data.filter(function(row) {
						return Object.keys(row).some(function(key) {
							if (columns.indexOf(key) > -1) { // limit keys to displayed columns
								return String((key === 'date') ? dateFormat(row.date) : row.name)
									.toLowerCase().indexOf(filterKey) > -1;
							}
						});
					});
				}

				if (sortKey) {
					data = data.slice().sort(function(a, b) {
						if (sortKey === 'date') { // get formatted date
							a = dateFormat(a[sortKey]);
							b = dateFormat(b[sortKey]);
						} else { // any other key
							a = a[sortKey];
							b = b[sortKey];
						}
						a = a.toLowerCase();
						b = b.toLowerCase();
						return ((a === b) ? 0 : ((a > b) ? 1 : -1)) * order;
					});
				}
				return data;
			}
		},
		filters: {
			capitalize: function(str) {
				return str.charAt(0).toUpperCase() + str.slice(1);
			},
			date: dateFormat
		},
		methods: {
			content: function(vObj, text) {
				if (typeof(text) === 'undefined') {
					return (vObj.innerText || this.textContent);
				}
				vObj[vObj.innerText ? 'innerText' : 'textContent'] = text;
			},
			sortBy: function(key) {
				this.sortKey = key;
				this.sortOrders[key] = this.sortOrders[key] * -1;
			},
			loadModal: function(idx) {
				if (recipeCard.cardData.index && recipeCard.cardData.index === idx) { // current equals last
					vBody.classList.add('modal-open'); // reopen same card
                    attribute(vDialog, 'open', true);
                    vDialog.show();
				}
				recipeCard.cardData = this.data[idx];
			}
		}
	});

	new Vue({ // bootstrap the application
		el: '#recipe-book',
		data: {
			searchQuery: '',
			listColumns: ['name', 'date'],
			listData: []
		},
		mounted: function() {
			var self = this;
			ajaxGet('media/recipeBook.json', function(data) {
				self.listData = data;
				var vObj = vDoc.getElementById('recipe-count');
				vObj[vObj.innerText ? 'innerText' : 'textContent'] = data.length + ' recipes.';
				attribute(vObj, 'hidden', false);
			});
		}
	});

	Vue.component('recipe-card', { // register the component
		template: '#card-template',
		replace: true,
		props: {
			data: Object
		},
		computed: {
			loadData: function() {
				if (this.data.from) { // card data loaded
					if (this.data.approved && this.data.approved !== '') { // recipe approved
						this.showModal();
						return this.data;
					}
					alert('Unable to display unapproved recipe.');
				}
			}
		},
		filters: {
			date: dateFormat,
			stripSlash: function(str) {
				return str.replace(/\\/g, '');
			}
		},
		methods: {
			showModal: function() {
				vBody.classList.add('modal-open'); // open new card
                attribute(vDialog, 'open', true);
                vDialog.show();
				vDoc.querySelector('.modal-window > *').scrollTop = 0; // scroll to top
			},
			setUnits: function(units) {
				var vUnits = vDoc.querySelector('#recipe-card article');
				vUnits.classList.remove('imperial', 'metric');
				vUnits.classList.add(units);
			}
		}
	});

	recipeCard = new Vue({ // bootstrap the application
		el: '#recipe-card',
		data: {
			cardData: {}
		}
	});
})(window || {});
