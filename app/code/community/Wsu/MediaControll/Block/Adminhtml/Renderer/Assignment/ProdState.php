<?php

class Wsu_Mediacontroll_Block_Adminhtml_Renderer_Assignment_ProdState extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract {
    /**
     * Format variables pattern
     *
     * @var string
     */
    protected $_variablePattern = '/\\$([a-z0-9_]+)/i';

    /**
     * Renders grid column
     *
     * @param Varien_Object $row
     * @return mixed
     */
    public function _getValue(Varien_Object $row) {
		
		$data = parent::_getValue($row);
		$prodImgProf = $row->getData("productImageProfile");
		//var_dump($prodImgProf);
		
		$missingSorted = $prodImgProf['missingSorted']?"true":"false";
		$hasSorted = $prodImgProf['hasSorted']?"true":"false";
		$hasSortIndexStart = $prodImgProf['hasSortIndexStart']?"true":"false";
		
		/*

		
		$format = ( $this->getColumn()->getFormat() ) ? $this->getColumn()->getFormat() : null;
        $defaultValue = $this->getColumn()->getDefault();
        if (is_null($format)) {
            // If no format and it column not filtered specified return data as is.
            $data = parent::_getValue($row);
            $string = is_null($data) ? $defaultValue : $data;
            $url	= htmlspecialchars($string);
        }
        elseif (preg_match_all($this->_variablePattern, $format, $matches)) {
            // Parsing of format string
            $formatedString = $format;
            foreach ($matches[0] as $matchIndex=>$match) {
                $value = $row->getData($matches[1][$matchIndex]);
                $formatedString = str_replace($match, $value, $formatedString);
            }
            $url	= $formatedString;
        } else {
            $url	= htmlspecialchars($format);
        }
		*/
		$location = Mage::getStoreConfig('web/secure/base_url');
		
		$html = "<ul>
			<li>Missing Sorted: ${missingSorted}</li>
			<li>Has Sorted: ${hasSorted}</li>
			<li>Sort Index Start @: ${hasSortIndexStart}</li>
		</ul>";

		return $html;
	
	}
}