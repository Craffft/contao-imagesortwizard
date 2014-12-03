<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (C) 2005-2014 Leo Feyer
 *
 * @package ImageSortWizard
 * @link    http://www.contao.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 */

/**
 * Register the namespaces
 */
ClassLoader::addNamespaces(array(
    'ImageSortWizard',
));

/**
 * Register the classes
 */
ClassLoader::addClasses(array(
    // Classes
    'ImageSortWizard\ImageSorter'     => 'system/modules/imagesortwizard/classes/ImageSorter.php',

    // Widgets
    'ImageSortWizard\ImageSortWizard' => 'system/modules/imagesortwizard/widgets/ImageSortWizard.php',
));
