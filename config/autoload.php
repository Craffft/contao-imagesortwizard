<?php

/**
 * Contao Open Source CMS
 * 
 * Copyright (C) 2005-2012 Leo Feyer
 * 
 * @package Pic_sort_wizard
 * @link    http://www.contao.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 */


/**
 * Register the namespaces
 */
ClassLoader::addNamespaces(array
(
	'PicSortWizard',
));


/**
 * Register the classes
 */
ClassLoader::addClasses(array
(
	// Classes
	'PicSortWizard\PicSorter'     => 'system/modules/pic_sort_wizard/classes/PicSorter.php',

	// Models
	'PicSortWizard\FilesModel'    => 'system/modules/pic_sort_wizard/models/FilesModel.php',

	// Widgets
	'PicSortWizard\PicSortWizard' => 'system/modules/pic_sort_wizard/widgets/PicSortWizard.php',
));
