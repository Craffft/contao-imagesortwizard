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
 * Class ImageSortWizard 
 *
 * @copyright  Daniel Kiesel 2012-2013 
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


	/**
	 * Return a form to choose a CSV file and import it
	 * @param object
	 * @return string
	 */
	public function importList(DataContainer $dc)
	{
		if ($this->Input->get('key') != 'list')
		{
			return '';
		}

		// Import CSS
		if ($this->Input->post('FORM_SUBMIT') == 'tl_list_import')
		{
			if (!$this->Input->post('source') || !is_array($this->Input->post('source')))
			{
				$_SESSION['TL_ERROR'][] = $GLOBALS['TL_LANG']['ERR']['all_fields'];
				$this->reload();
			}

			$this->import('Database');
			$arrList = array();

			foreach ($this->Input->post('source') as $strCsvFile)
			{
				$objFile = new File($strCsvFile);

				if ($objFile->extension != 'csv')
				{
					$_SESSION['TL_ERROR'][] = sprintf($GLOBALS['TL_LANG']['ERR']['filetype'], $objFile->extension);
					continue;
				}

				// Get separator
				switch ($this->Input->post('separator'))
				{
					case 'semicolon':
						$strSeparator = ';';
						break;

					case 'tabulator':
						$strSeparator = '\t';
						break;

					case 'linebreak':
						$strSeparator = '\n';
						break;

					default:
						$strSeparator = ',';
						break;
				}

				$resFile = $objFile->handle;

				while(($arrRow = @fgetcsv($resFile, null, $strSeparator)) !== false)
				{
					$arrList = array_merge($arrList, $arrRow);
				}
			}

			$this->createNewVersion($dc->table, $this->Input->get('id'));

			$this->Database->prepare("UPDATE " . $dc->table . " SET listitems=? WHERE id=?")
						   ->execute(serialize($arrList), $this->Input->get('id'));

			setcookie('BE_PAGE_OFFSET', 0, 0, '/');
			$this->redirect(str_replace('&key=list', '', $this->Environment->request));
		}

		$objTree = new FileTree($this->prepareForWidget($GLOBALS['TL_DCA'][$dc->table]['fields']['source'], 'source', null, 'source', $dc->table));

		// Return form
		return '
<div id="tl_buttons">
<a href="'.ampersand(str_replace('&key=list', '', $this->Environment->request)).'" class="header_back" title="'.specialchars($GLOBALS['TL_LANG']['MSC']['backBT']).'" accesskey="b">'.$GLOBALS['TL_LANG']['MSC']['backBT'].'</a>
</div>

<h2 class="sub_headline">'.$GLOBALS['TL_LANG']['MSC']['lw_import'][1].'</h2>
'.$this->getMessages().'
<form action="'.ampersand($this->Environment->request, true).'" id="tl_list_import" class="tl_form" method="post">
<div class="tl_formbody_edit">
<input type="hidden" name="FORM_SUBMIT" value="tl_list_import">
<input type="hidden" name="REQUEST_TOKEN" value="'.REQUEST_TOKEN.'">

<div class="tl_tbox block">
  <h3><label for="separator">'.$GLOBALS['TL_LANG']['MSC']['separator'][0].'</label></h3>
  <select name="separator" id="separator" class="tl_select" onfocus="Backend.getScrollOffset();">
    <option value="comma">'.$GLOBALS['TL_LANG']['MSC']['comma'].'</option>
    <option value="semicolon">'.$GLOBALS['TL_LANG']['MSC']['semicolon'].'</option>
    <option value="tabulator">'.$GLOBALS['TL_LANG']['MSC']['tabulator'].'</option>
    <option value="linebreak">'.$GLOBALS['TL_LANG']['MSC']['linebreak'].'</option>
  </select>'.(($GLOBALS['TL_LANG']['MSC']['separator'][1] != '') ? '
  <p class="tl_help tl_tip">'.$GLOBALS['TL_LANG']['MSC']['separator'][1].'</p>' : '').'
  <h3><label for="source">'.$GLOBALS['TL_LANG']['MSC']['source'][0].'</label> <a href="contao/files.php" title="' . specialchars($GLOBALS['TL_LANG']['MSC']['fileManager']) . '" rel="lightbox[files 765 80%]">' . $this->generateImage('filemanager.gif', $GLOBALS['TL_LANG']['MSC']['fileManager'], 'style="vertical-align:text-bottom;"') . '</a></h3>
'.$objTree->generate().(($GLOBALS['TL_LANG']['MSC']['source'][1] != '') ? '
  <p class="tl_help tl_tip">'.$GLOBALS['TL_LANG']['MSC']['source'][1].'</p>' : '').'
</div>

</div>

<div class="tl_formbody_submit">

<div class="tl_submit_container">
  <input type="submit" name="save" id="save" class="tl_submit" accesskey="s" value="'.specialchars($GLOBALS['TL_LANG']['MSC']['lw_import'][0]).'">
</div>

</div>
</form>';
	}
}

?>