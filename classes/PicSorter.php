<?php 

/**
 * Contao Open Source CMS
 * 
 * Copyright (C) 2005-2012 Leo Feyer
 * 
 * @package   PicSortWizard 
 * @author    Daniel Kiesel <https://github.com/icodr8> 
 * @license   LGPL 
 * @copyright Daniel Kiesel 2012 
 */


/**
 * Namespace
 */
namespace PicSortWizard;

/**
 * Class PicSorter 
 *
 * @copyright  Daniel Kiesel 2012 
 * @author     Daniel Kiesel <https://github.com/icodr8> 
 * @package    PicSortWizard
 */
class PicSorter extends \Controller
{
	private $arrIds;
	private $arrExtensions;
	
	public function __construct($arrIds, $strExtensions = null)
	{
		if(!is_array($arrIds))
		{
			return false;
		}
		
		// Set extensions
		$this->setExtensions($strExtensions);
		
		// Set all pic ids
		$this->setAllPicIds($arrIds);
		
		parent::__construct();
	}
	
	
	protected function setExtensions($strExtensions)
	{
		$this->arrExtensions = array();
		
		if($strExtensions !== null)
		{
			$this->arrExtensions = explode(',', $strExtensions);
		}
	}
	
	
	protected function setAllPicIds($arrIds)
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
	
	
	public function sortPicsBy($varSortField, $varSortType = 'ASC')
	{
		if(!is_array($this->arrIds) || count($this->arrIds) < 1)
		{
			return false;
		}
		
		$varSortType = strtoupper($varSortType);
		
		/**
		 * SET SORT FIELDS HERE
		 * 
		 * metatitle
		 * filename
		 * date
		 * random
		 * custom
		 */
		if($varSortField == 'custom')
		{
			// Do nothing
		}
		else if($varSortField == 'random')
		{
			$this->arrIds = array_shuffle($this->arrIds);
		}
		else
		{
			$arrSort = array();
			
			foreach($this->arrIds as $intId)
			{
				$objFiles = \PicSortWizard\FilesModel::findByPk($intId);
				
				switch($varSortField)
				{
					case 'metatitle':
						// Meta title
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
					break;
					
					case 'filename':
						// Date
						$sortType = SORT_STRING;
						$filename = '';
						
						if($objFiles->name != '')
						{
							$filename = $objFiles->name;
						}
						
						$arrSort[$objFiles->id] = $filename;
					break;
					
					case 'date':
						// Date
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
			
			asort($arrSort, $sortType);
			$this->arrIds = array_keys($arrSort);
		}
		
		if($varSortType == 'DESC')
		{
			$this->arrIds = array_reverse($this->arrIds);
		}
		
		return true;
	}
	
	
	public function getPicIds()
	{
		return $this->arrIds;
	}
}