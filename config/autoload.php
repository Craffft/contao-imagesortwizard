<?php

/**
 * Contao Open Source CMS
 * 
 * Copyright (C) 2005-2012 Leo Feyer
 * 
 * @package ImageSortWizard
 * @link    http://www.contao.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 */


/**
 * Register the namespaces
 */
ClassLoader::addNamespaces(array
(
	'ImageSortWizard',
));


/**
 * Register the classes
 */
ClassLoader::addClasses(array
(
	// Classes
	'ImageSortWizard\ImageSorter'     => 'system/modules/image_sort_wizard/classes/ImageSorter.php',

	// Widgets
	'ImageSortWizard\ImageSortWizard' => 'system/modules/image_sort_wizard/widgets/ImageSortWizard.php',
));
