<?php
/**
  execute() is the main function that is called when a special page is accessed. 
  The function overloads the function SpecialPage::execute(). 
  
  It passes a single parameter $par, the subpage component of the current title. 
  For example, if someone follows a link to Special:MyExtension/blah, 
  $par will contain "blah". 
*/
class specialPagesExport extends SpecialPage {
	public function __construct() {
		parent::__construct ( 'specialPagesExport' );
	}
	public function execute($par) {
		global $wgRequest, $wgOut;
		
		$this->setHeaders ();
		
		
		$do_show = $wgRequest->getText ( 'show' );
		if (strlen ( trim ( $do_show ) )) {
			$page_touched = (int)$wgRequest->getText ( 'from_year' )?:2000;
			$page_touched .= (int)$wgRequest->getText ( 'from_month' )?$wgRequest->getText ( 'from_month' ):01;
			$page_touched .= (int)$wgRequest->getText ( 'from_day' )?$wgRequest->getText ( 'from_day' ):01;
			$page_touched .= '000000';
		}else{
			//Default page touched (if nothing wasn't selected
			$page_touched = '20000101000000';
		}

		$sql = "SELECT page_id, page_namespace, CONVERT(page_title USING utf8) page_title, 
												CONVERT(page_touched USING utf8) page_touched
					FROM page
					WHERE page_touched > '$page_touched'
					ORDER BY page_touched DESC";
		
		
		$do_export = $wgRequest->getText ( 'export' );
		if (strlen ( trim ( $do_export ) )) {
			$this->_exportData ( $sql );
		}
		$dbr = wfGetDB ( DB_SLAVE );
		$result = $dbr->query ( $sql );
		
		//Show form
		$output = '<h3>Limit export time period from</h3>';
		$output .= '<form action="" method="get">';
		$output .= '<select name="from_day">';
		$output .= $this->_getDaysOptions((int)$wgRequest->getText ( 'from_day' ));
		$output .= '</select>';
		$output .= '<select name="from_month">';
		$output .= $this->_getMonthsOptions((int)$wgRequest->getText ( 'from_month' ));
		$output .= '</select>';
		$output .= '<select name="from_year">';
		$output .= $this->_getYearsOptions((int)$wgRequest->getText ( 'from_year' ), 10);
		$output .= '</select>';
		$output .= '<br/>';
		$output .= '<input type="hidden" name="show" value="true">';
		$output .= '<input type="submit" value="show data">';
		$output .= '</form>';
		$output .= '<br/><br/>';
		
		$output .= '<ul>';
		while ( $row = $result->fetchRow () ) {
			$page_title = $this->_setPageTitlePrefix ( $row ['page_namespace'] );
			$output .= '<li><b>' . $page_title . $row ['page_title'] . '</b>
					 (Namespace: ' . $row ['page_namespace'] . '), last edited - ' . date('H:i:s d-m-y', strtotime($row ['page_touched'])) . '</li>';
		}
		$output .= '</ul>';
		$output .= '<br/><br/>';

		//Export form
		$output .= '<form action="" method="post">';
		$output .= '<input type="hidden" name="export" value="true">';
		$output .= '<input type="submit" value="export data">';
		$output .= '</form>';	
		
		// Output
		$wgOut->addHTML ( $output );
	}
	
	/**
	 * Export the data
	 * 
	 * @param string $query - The SQL for the export        	
	 */
	private function _exportData($query) {
		$dbr = wfGetDB ( DB_SLAVE );
		$result = $dbr->query ( $query );
		$i = 0;
		while ( $row = $result->fetchRow () ) {
			$data_arr [$i] ['id'] = $row ['page_id'];
			$data_arr [$i] ['namespace'] = $row ['page_namespace'];
			$data_arr [$i] ['title'] = $this->_setPageTitlePrefix($row ['page_namespace']) . $row ['page_title'];
			$data_arr [$i] ['last_edited'] = date('H:i:s d-m-y', strtotime($row ['page_touched']));
			$i ++;
		}
		$fields = array (
				'Id',
				'Namespace',
				'Title',
				'LastEdited' 
		);
		$header = '';
		foreach ( $fields as $key => $value ) {
			$header .= $value . "\t";
		}
		
		$data = '';
		foreach ( $data_arr as $key => $values ) {
			$line = '';
			foreach ( $values as $key2 => $val2 ) {
				
				if ((! isset ( $val2 )) || ($val2 == "")) {
					$val2 = '" "' . "\t";
				} else {
					$val2 = str_replace ( '"', '""', $val2 );
					$val2 = '"' . $val2 . '"' . "\t";
				}
				$line .= $val2;
			}
			$data .= trim ( $line ) . "\n";
		}
		ob_clean ();
		header ( "Content-type: application/octet-stream" );
		header ( "Content-Disposition: attachment; filename=pages_export_from.xls" );
		header ( "Pragma: no-cache" );
		header ( "Expires: 0" );
		print "$header\n$data";
		exit ();
	}
	
	/**
	 * Sets the prefix for the page title
	 *
	 * @param string $namespace - The namespace of the page
	 * @return string $page_title - The title of the page to be as prefix
	 */
	private function _setPageTitlePrefix($namespace) {
		$page_title = '';
		switch ($namespace) {
			case NS_FILE :
				$page_title = 'File:';
				break;
			case NS_FILE_TALK :
				$page_title = 'File_talk:';
				break;
			case NS_CATEGORY :
				$page_title = 'Category:';
				break;
			case NS_CATEGORY_TALK :
				$page_title = 'Category_talk:';
				break;
			case NS_MEDIAWIKI :
				$page_title = 'Mediawiki:';
				break;
			case NS_MEDIAWIKI_TALK :
				$page_title = 'Mediawiki_template:';
				break;
			case NS_TEMPLATE :
				$page_title = 'Template:';
				break;
			case NS_TEMPLATE_TALK :
				$page_title = 'Template_talk:';
				break;
			case NS_USER :
				$page_title = 'User:';
				break;
			case NS_USER_TALK :
				$page_title = 'User_talk:';
				break;
			case NS_HELP :
				$page_title = 'Help:';
				break;
			case NS_HELP_TALK :
				$page_title = 'Help_talk:';
				break;
			case NS_PROJECT :
				$page_title = 'Project:';
				break;
			case NS_PROJECT_TALK :
				$page_title = 'Project_title:';
				break;
			case NS_SPECIAL :
				$page_title = 'Special:';
				break;
			case NS_TALK :
				$page_title = 'Talk:';
				break;
		}
	
		return $page_title;
	}
	
	/**
	 * Build days options
	 * @return string
	 */
	private function _getDaysOptions($selected){
		$options = '<option value="">Day</options>';
		for ($i=1;$i<=31;$i++){
			if ($selected == $i){
				$selected_text = 'selected="selected"';
			}else{
				$selected_text = '';
			}
			$options .= "<option $selected_text value='$i'>$i</options>";
		}
		
		return $options;
		
	}
	
	/**
	 * Build months options
	 * @return string
	 */
	private function _getMonthsOptions($selected){
		$options = '<option value="">Month</options>';
		for ($i=1;$i<=12;$i++){
			if ($selected == $i){
				$selected_text = 'selected="selected"';
			}else{
				$selected_text = '';
			}
			$val = ($i<10) ? "0$i" : $i;
			$options .= "<option $selected_text value='$val'>$i</options>";
		}
	
		return $options;
	
	}
	
	/**
	 * Build years options
	 * @param $year_back - The last year that we show on the dropdown
	 * @return string
	 */
	private function _getYearsOptions($selected, $year_back){
		$options = '<option value="">Year</options>';
		for ($i=date('Y');$i>=$year_back;$i--){
			if ($selected == $i){
				$selected_text = 'selected="selected"';
			}else{
				$selected_text = '';
			}
			$val = ($i<10) ? "0$i" : $i;
			$options .= "<option $selected_text value='$val'>$i</options>";
		}
	
		return $options;
	
	}
}
?>