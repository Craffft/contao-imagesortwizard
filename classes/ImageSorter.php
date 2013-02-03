<?php 

/**
 * Contao Open Source CMS
 * 
 * Copyright (C) 2005-2012 Leo Feyer
 * 
 * @package   ImageSortWizard 
 * @author    Daniel Kiesel <https://github.com/icodr8> 
 * @license   LGPL 
 * @copyright Daniel Kiesel 2012-2013 
 */


/**
 * Namespace
 */
namespace ImageSortWizard;

/**
 * Class ImageSorter 
 *
 * @copyright  Daniel Kiesel 2012-2013 
 * @author     Daniel Kiesel <https://github.com/icodr8> 
 * @package    ImageSortWizard
 */
class ImageSorter extends \Controller
{
	/**
	 * arrIds
	 * 
	 * @var array
	 * @access private
	 */
	private $arrIds;
	
	
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
	 * @param array $arrIds
	 * @param string $strExtensions (default: null)
	 * @return void
	 */
	public function __construct($arrIds, $strExtensions = null)
	{
		if(!is_array($arrIds))
		{
			return false;
		}
		
		// Set extensions
		$this->setExtensions($strExtensions);
		
		// Set all image ids
		$this->setAllImageIds($arrIds);
		
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
	 * setAllImageIds function.
	 * 
	 * @access protected
	 * @param array $arrIds
	 * @return void
	 */
	protected function setAllImageIds($arrIds)
	{
		$arrAllIds = array();
		
		// Check for array with content
		if (is_array($arrIds) && count($arrIds) > 0)
		{
			foreach ($arrIds as $intId)
			{
				$arrScan = $this->scanDirRecursive($intId);
				$arrAllIds = array_merge($arrAllIds, $arrScan);
			}
		}
		
		$this->arrIds = array_unique($arrAllIds);
	}
	
	
	/**
	 * scanDirRecursive function.
	 * 
	 * @access protected
	 * @param int $intId
	 * @return array
	 */
	protected function scanDirRecursive($intId)
	{
		$arrIds = array();
		$objFile = \FilesModel::findByPk($intId);
		
		switch($objFile->type)
		{
			case 'folder':
				$objChildren = \FilesModel::findByPid($intId);
				
				if($objChildren !== null)
				{
					while($objChildren->next())
					{
						$arrScan = $this->scanDirRecursive($objChildren->id, $extensions);
						
						if (is_array($arrScan) && count($arrScan) > 0)
						{
							$arrIds = array_merge($arrIds, $arrScan);
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
						$arrIds[] = $objFile->id;
					}
				}
				
				// Set all file ids if there are no extensions required
				else
				{
					$arrIds[] = $objFile->id;
				}
			break;
		}
		
		return array_unique($arrIds);
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
		if(!is_array($this->arrIds) || count($this->arrIds) < 1)
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
			shuffle($this->arrIds);
		}
		else
		{
			$arrSort = array();
			
			foreach($this->arrIds as $intId)
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
			$this->arrIds = array_keys($arrSort);
		}
		
		if($strSortDirection == 'DESC')
		{
			$this->arrIds = array_reverse($this->arrIds);
		}
		
		return true;
	}
	
	
	/**
	 * getImageIds function.
	 * 
	 * @access public
	 * @return array
	 */
	public function getImageIds()
	{
		return $this->arrIds;
	}
}
