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
 * Class ImageSortWizard
 *
 * @copyright  Daniel Kiesel 2012-2014
 * @author     Daniel Kiesel <https://github.com/icodr8>
 * @package    ImageSortWizard
 */
class ImageSortWizard extends \Widget
{

	/**
	 * Submit user input
	 * @var boolean
	 */
	protected $blnSubmitInput = true;

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'be_widget';


	/**
	 * Add specific attributes
	 * @param string
	 * @param mixed
	 */
	public function __set($strKey, $varValue)
	{
		switch ($strKey)
		{
			case 'mandatory':
				$this->arrConfiguration['mandatory'] = $varValue ? true : false;
				break;

			default:
				parent::__set($strKey, $varValue);
				break;
		}
	}


	/**
	 * Trim values
	 * @param mixed
	 * @return mixed
	 */
	public function validator($varInput)
	{
		if (is_array($varInput))
		{
			return parent::validator($varInput);
		}

		return parent::validator(trim($varInput));
	}


	/**
	 * Generate the widget and return it as string
	 * @return string
	 */
	public function generate()
	{
		$arrButtons = array('up', 'down');
		$strCommand = 'cmd_' . $this->strField;

		// Add JavaScript and css
		if (TL_MODE == 'BE')
		{
			$GLOBALS['TL_JAVASCRIPT'][] = 'system/modules/image_sort_wizard/html/image_sort_wizard.js';
		    $GLOBALS['TL_MOOTOOLS'][] = '<script>Backend.makeParentViewSortable(".tl_imagesortwizard");</script>';
		    $GLOBALS['TL_CSS'][] = 'system/modules/image_sort_wizard/html/image_sort_wizard.css|screen';
		}

		// Change the order
		if ($this->Input->get($strCommand) && is_numeric($this->Input->get('cid')) && $this->Input->get('id') == $this->currentRecord)
		{
			$this->import('Database');

			switch ($this->Input->get($strCommand))
			{
				case 'up':
					$this->varValue = array_move_up($this->varValue, $this->Input->get('cid'));
					break;

				case 'down':
					$this->varValue = array_move_down($this->varValue, $this->Input->get('cid'));
					break;
			}

			$this->Database->prepare("UPDATE " . $this->strTable . " SET " . $this->strField . "=? WHERE id=?")
						   ->execute(serialize($this->varValue), $this->currentRecord);

			$this->redirect(preg_replace('/&(amp;)?cid=[^&]*/i', '', preg_replace('/&(amp;)?' . preg_quote($strCommand, '/') . '=[^&]*/i', '', $this->Environment->request)));
		}

		$tabindex = 0;
		$return .= '<ul id="ctrl_'.$this->strId.'" class="tl_imagesortwizard">';


		// Get sort Images
		$this->sortImages = $this->getSortedImages();

		// Make sure there is at least an empty array
		if (!is_array($this->varValue) || count($this->varValue) < 1)
		{
			$this->varValue = array();
		}

		// Set var sortImages as array if there is none
		if (!is_array($this->sortImages) || count($this->sortImages) < 1)
		{
			$this->sortImages = array();
		}

		// Set var value
		$newVarValue = array();

		// Remove old Images
		if(count($this->varValue) > 0)
		{
			$objFiles = (\FilesModel::findMultipleByIds($this->varValue));

			if($objFiles !== null)
			{
				while($objFiles->next())
				{
					if (in_array($objFiles->id, $this->sortImages))
					{
						$newVarValue[] = $objFiles->id;
					}
				}
			}
		}

		// Set newVarValue in varValue
		$this->varValue = $newVarValue;

		// Add new Images
		if(count($this->sortImages) > 0)
		{
			$objFiles = (\FilesModel::findMultipleByIds($this->sortImages));

			if($objFiles !== null)
			{
				while($objFiles->next())
				{
					if (!in_array($objFiles->id, $this->varValue))
					{
						$this->varValue[] = $objFiles->id;
					}
				}
			}
		}

		$objFiles = (\FilesModel::findMultipleByIds($this->varValue));

		if($objFiles !== null)
		{
			$i = 0;
			$rows = ($objFiles->count()-1);

			while($objFiles->next())
			{
				$first = ($i == 0) ? true : false;
				$last = ($i == $rows) ? true : false;

				$objFile = new \File($objFiles->path);

				// Generate thumbnail
				if ($objFile->isGdImage && $objFile->height > 0)
				{
					if ($GLOBALS['TL_CONFIG']['thumbnails'] && $objFile->height <= $GLOBALS['TL_CONFIG']['gdMaxImgHeight'] && $objFile->width <= $GLOBALS['TL_CONFIG']['gdMaxImgWidth'])
				    {
					    $_height = ($objFile->height < 70) ? $objFile->height : 70;
				    	$_width = (($objFile->width * $_height / $objFile->height) > 400) ? 90 : '';

				    	$thumbnail = '<img src="' . TL_FILES_URL . $this->getImage($objFiles->path, $_width, $_height) . '" alt="thumbnail" style="margin:0px 0px 2px 23px;">';
				    }
				}

				if ($first==true)
				{
					$return .= '<li class="first">';
				}
				else if ($last==true)
				{
					$return .= '<li class="last">';
				}
				else
				{
					$return .= '<li>';
				}
				$return .= $thumbnail;
				$return .= '<input type="hidden" name="'.$this->strId.'[]" class="tl_text" tabindex="'.++$tabindex.'" value="'.specialchars($this->varValue[$i]).'"' . $this->getAttributes() . '> ';

					// Add buttons
					foreach ($arrButtons as $button)
					{
						$return .= '<a class="tl_content_right" href="'.$this->addToUrl('&amp;'.$strCommand.'='.$button.'&amp;cid='.$i.'&amp;id='.$this->currentRecord).'" title="'.specialchars($GLOBALS['TL_LANG']['MSC']['lw_'.$button]).'" onclick="Backend.imageSortWizard(this, \''.$button.'\', \'ctrl_'.$this->strId.'\'); return false;">'.$this->generateImage($button.'.gif', $GLOBALS['TL_LANG']['MSC']['lw_'.$button], 'class="tl_imagesortwizard_img"').'</a> ';
					}

				$return .= '</li>';

				$i++;
			}
		}

		$return .= '</ul>';
		$return .= '<script>Backend.makeParentViewSortable(".tl_imagesortwizard");</script>';

		return $return;
	}


	public function getSortedImages()
	{
		if (!$this->sortfiles)
		{
			return false;
		}

		// Set arrays
		$arrSortfiles = array();

		// Import
		$this->import('Database');
		$this->import('Files');

		// Get Sortfiles
		$objSortfiles = $this->Database->prepare("SELECT " . $this->sortfiles . " FROM " . $this->strTable . " WHERE id=?")
										->execute($this->currentRecord);

		// Fetch
		$arrSortfiles = $objSortfiles->fetchAssoc();
		$arrIds = deserialize($arrSortfiles[$this->sortfiles]);

		// Create new object from ImageSorter and get unsorted files
		$objImageSorter = new ImageSorter($arrIds, $this->extensions);
		$objImageSorter->sortImagesBy('custom', 'ASC');

		return $objImageSorter->getImageIds();
	}
}

?>