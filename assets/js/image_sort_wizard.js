
/**
 * Contao Open Source CMS
 *
 * Copyright (C) 2005-2014 Leo Feyer
 *
 * @package    ImageSortWizard
 * @author     Daniel Kiesel <https://github.com/icodr8>
 * @license    LGPL
 * @copyright  Daniel Kiesel 2012-2014
 */

Backend.imageSortWizard = function(el, command, id) {
	var container = $(id).getElement('.sortable'),
		parent = $(el).getParent('li'), li;
	Backend.getScrollOffset();
	switch (command) {
	case 'up':
		if ((li = parent.getPrevious('li'))) {
			parent.inject(li, 'before');
		} else {
			parent.inject(container, 'bottom');
		}
		break;
	case 'down':
		if (li = parent.getNext('li')) {
			parent.inject(li, 'after');
		} else {
			parent.inject(container, 'top');
		}
		break;
	}
}
window.addEvent('domready', function() {
	$$('.tl_image_sort_wizard').each(function(el) {
		var els = el.getElement('.sortable');
		if (els.hasClass('sortable-done')) return;
		new Sortables(els, {
			contstrain: true,
			opacity: 0.6,
			handle: '.drag-handle'
		});
		els.addClass('sortable-done');
	});
});
