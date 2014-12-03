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

        $varInput = $this->fixUuidToBinary($varInput);
        $varInput = trim($varInput);

		return parent::validator($varInput);
	}

    protected function fixUuidToBinary($strUuid)
    {
        if (\Validator::isStringUuid($strUuid)) {
            $strUuid = \String::uuidToBin($strUuid);
        }

        return $strUuid;
    }

	/**
	 * Generate the widget and return it as string
	 * @return string
	 */
	public function generate()
	{
        $return = '';
		$arrButtons = array('up', 'down');
		$strCommand = 'cmd_' . $this->strField;

		// Add JavaScript and css
		if (TL_MODE == 'BE')
		{
			$GLOBALS['TL_JAVASCRIPT'][] = 'system/modules/imagesortwizard/assets/js/imagesortwizard.min.js';
		    $GLOBALS['TL_CSS'][] = 'system/modules/imagesortwizard/assets/css/imagesortwizard.min.css|screen';
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
		$return .= '<div id="ctrl_'.$this->strId.'" class="tl_imagesortwizard">';
			$return .= '<ul class="sortable">';

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
				$objFiles = (\FilesModel::findMultipleByUuids($this->varValue));

				if($objFiles !== null)
				{
					while($objFiles->next())
					{
						if (in_array($objFiles->uuid, $this->sortImages) || in_array($objFiles->id, $this->sortImages)) // Backwards compatibility (id)
						{
							$newVarValue[] = $objFiles->uuid;
						}
					}
				}
			}

			// Set newVarValue in varValue
			$this->varValue = $newVarValue;

			// Add new Images
			if(count($this->sortImages) > 0)
			{
				$objFiles = (\FilesModel::findMultipleByUuids($this->sortImages));

				if($objFiles !== null)
				{
					while($objFiles->next())
					{
						if (!in_array($objFiles->uuid, $this->varValue))
						{
							$this->varValue[] = $objFiles->uuid;
						}
					}
				}
			}

			$objFiles = (\FilesModel::findMultipleByUuids($this->varValue));

			if($objFiles !== null)
			{
				$i = 0;
				$rows = ($objFiles->count()-1);

				while($objFiles->next())
				{
					$objFile = new \File($objFiles->path);

					// Generate thumbnail
					if ($objFile->isGdImage && $objFile->height > 0)
					{
						if ($GLOBALS['TL_CONFIG']['thumbnails'] && $objFile->height <= $GLOBALS['TL_CONFIG']['gdMaxImgHeight'] && $objFile->width <= $GLOBALS['TL_CONFIG']['gdMaxImgWidth'])
					    {
					    	$_width = ($objFile->width < 80) ? $objFile->width : 80;
						    $_height = ($objFile->height < 60) ? $objFile->height : 60;

					    	$thumbnail = '<img src="' . TL_FILES_URL . $this->getImage($objFiles->path, $_width, $_height, 'center_center') . '" alt="thumbnail">';
					    }
					}

					$return .= '<li>';
						$return .= $thumbnail;
						$return .= '<input type="hidden" name="'.$this->strId.'[]" class="tl_text" tabindex="'.++$tabindex.'" value="'.specialchars(\String::binToUuid($objFiles->uuid)).'"' . $this->getAttributes() . '>';
					$return .= '</li>';

					$i++;
				}
			}

			$return .= '</ul>';
		$return .= '</div>';

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
		$arrUuids = deserialize($arrSortfiles[$this->sortfiles]);

		// Create new object from ImageSorter and get unsorted files
		$objImageSorter = new ImageSorter($arrUuids, $this->extensions);
		$objImageSorter->sortImagesBy('custom', 'ASC');

		return $objImageSorter->getImageUuids();
	}
}

?>