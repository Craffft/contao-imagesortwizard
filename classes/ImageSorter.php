<?php

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


/**
 * Namespace
 */
namespace ImageSortWizard;


/**
 * Class ImageSorter
 *
 * @copyright  Daniel Kiesel 2012-2014
 * @author     Daniel Kiesel <https://github.com/icodr8>
 * @package    ImageSortWizard
 */
class ImageSorter extends \Controller
{

	/**
	 * arrUuids
	 *
	 * @var array
	 * @access private
	 */
	private $arrUuids;


	/**
	 * arrExtensions
	 *
	 * @var array
	 * @access private
	 */
	private $arrExtensions;


	/**
	 * __construct function.
	 *
	 * @access public
	 * @param array $arrUuids
	 * @param string $strExtensions (default: null)
	 * @return void
	 */
	public function __construct($arrUuids, $strExtensions = null)
	{
		if(!is_array($arrUuids))
		{
			return false;
		}

		// Set extensions
		$this->setExtensions($strExtensions);

		// Set all image ids
		$this->setAllImageUuids($arrUuids);

		parent::__construct();
	}


	/**
	 * setExtensions function.
	 *
	 * @access protected
	 * @param string $strExtensions
	 * @return void
	 */
	protected function setExtensions($strExtensions)
	{
		$this->arrExtensions = array();

		if($strExtensions !== null)
		{
			$this->arrExtensions = explode(',', $strExtensions);
		}
	}


	/**
	 * setAllImageUuids function.
	 *
	 * @access protected
	 * @param array $arrUuids
	 * @return void
	 */
	protected function setAllImageUuids($arrUuids)
	{
		$arrAllUuids = array();

		// Check for array with content
		if (is_array($arrUuids) && count($arrUuids) > 0)
		{
			foreach ($arrUuids as $intId)
			{
				$arrScan = $this->scanDirRecursive($intId);
				$arrAllUuids = array_merge($arrAllUuids, $arrScan);
			}
		}

		$this->arrUuids = array_unique($arrAllUuids);
	}


	/**
	 * scanDirRecursive function.
	 *
	 * @access protected
	 * @param string $uuid
	 * @return array
	 */
	protected function scanDirRecursive($uuid)
	{
		$arrUuids = array();
		$objFile = \FilesModel::findByUuid($uuid);

		switch($objFile->type)
		{
			case 'folder':
				$objChildren = \FilesModel::findByPid($uuid);

				if($objChildren !== null)
				{
					while($objChildren->next())
					{
						$arrScan = $this->scanDirRecursive($objChildren->uuid);

						if (is_array($arrScan) && count($arrScan) > 0)
						{
							$arrUuids = array_merge($arrUuids, $arrScan);
						}
					}
				}
			break;

			case 'file':
				// Set only the file ids with the correct extension
				if(count($this->arrExtensions) > 0)
				{
					if(in_array($objFile->extension, $this->arrExtensions))
					{
						$arrUuids[] = $objFile->uuid;
					}
				}

				// Set all file ids if there are no extensions required
				else
				{
					$arrUuids[] = $objFile->uuid;
				}
			break;
		}

		return array_unique($arrUuids);
	}


	/**
	 * sortImagesBy function.
	 *
	 * @access public
	 * @param string $strSortKey
	 * @param string $strSortDirection (default: 'ASC')
	 * @return bool
	 */
	public function sortImagesBy($strSortKey, $strSortDirection = 'ASC')
	{
		if(!is_array($this->arrUuids) || count($this->arrUuids) < 1)
		{
			return false;
		}

		// Lower and uppercase for attributes
		$strSortKey = strtolower($strSortKey);
		$strSortDirection = strtoupper($strSortDirection);


		/**
		 * SET SORT FIELDS HERE
		 *
		 * metatitle
		 * name
		 * date
		 * random
		 * custom
		 */
		if($strSortKey == 'custom')
		{
			// Do nothing
		}
		else if($strSortKey == 'random')
		{
			shuffle($this->arrUuids);
		}
		else
		{
			$arrSort = array();

			foreach($this->arrUuids as $intId)
			{
				$objFiles = \FilesModel::findByPk($intId);

				if ($objFiles !== null)
				{
					switch($strSortKey)
					{
						case 'metatitle':
							$sortType = SORT_STRING;
							$metaTitle = '';

							if($objFiles->meta != '')
							{
								$objFiles->meta = deserialize($objFiles->meta);

								if($objFiles->meta[$GLOBALS['TL_LANGUAGE']]['title'] != '')
								{
									$metaTitle = $objFiles->meta[$GLOBALS['TL_LANGUAGE']]['title'];
								}
							}

							$arrSort[$objFiles->id] = $metaTitle;
						break;

						case 'name':
							$sortType = SORT_STRING;
							$filename = '';

							if($objFiles->name != '')
							{
								$filename = $objFiles->name;
							}

							$arrSort[$objFiles->id] = $filename;
						break;

						case 'date':
							$sortType = SORT_NUMERIC;
							$tstamp = '';

							if($objFiles->tstamp != '')
							{
								$tstamp = $objFiles->tstamp;
							}

							$arrSort[$objFiles->id] = $tstamp;
						break;
					}
				}
			}

			asort($arrSort, $sortType);
			$this->arrUuids = array_keys($arrSort);
		}

		if($strSortDirection == 'DESC')
		{
			$this->arrUuids = array_reverse($this->arrUuids);
		}

		return true;
	}


	/**
	 * getImageUuids function.
	 *
	 * @access public
	 * @return array
	 */
	public function getImageUuids()
	{
		return $this->arrUuids;
	}
}
