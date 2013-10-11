<?php
if(!defined('BASEPATH')) exit('No direct script access allowed'); 

/**
 * CI Google Drive Spreadsheet
 *
 * @author The Juan Who Code <caserjan@gmail.com>
 *
 *
 *
 * How to use
 *
 *	$gds = new GD_Spreadsheet();
 *	
 *	*-Param-*
 *	code = google doc spreadsheet code (REQUIRED)
 *	data_type = Type of data to retrieve (DEFAULT: json)
 *	dir = Dir path where to save the local file (OPTIONAL)
 *	
 *	$gds->set('code=<google doc spreadsheet code>&data_type=json');
 *	$gds->get_row(1,'<column name>');
 *	
 */
class gds{
	
	/*Google Drive Spreadsheet Cache*/
	private $gds = array(); 
	
	public function __construct(){}
	
	/**
	 * Set Google Drive spreadsheet
	 *
	 * @param mixed $param
	 */
	public function set($param){
		
		if(!is_array($param)) parse_str($param,$param);	
		
		
		extract(array_merge(array(			
			'code' => '',			
			'data_type' => 'json',
			'dir' => dirname(__FILE__).'/.temp'
		),$param));
		
		if(!is_array($cols)) $cols = explode(',',$cols);
		
		if(!empty($code)){
			
			if(is_array($cols)) $this->cols = $cols;
			
			$dir = empty($dir) ? dirname(__FILE__).'/.temp' : $dir;
			
			// Check if temp dir exists, create if not
			if(!file_exists($dir)) mkdir($dir);
			
			
			if(!file_exists($dir.'/'.$code.'.gds')){
				
				$_gds = file_get_contents('http://spreadsheets.google.com/feeds/cells/'.$code.'/od6/public/basic?alt='.$data_type);
				
				$_gds = str_replace('$','_',$_gds);				
				$_gds = json_decode($_gds);
				
				$gds = array();				
				$data = $_gds->feed;
				
				// Updated date
				$gds['info']['updated_date'] = $data->updated->_t;
				
				// Author
				foreach($data->author as $author){
					$gds['info']['author'][] = array(
						'name' => $author->name->_t,
						'email' => $author->email->_t
					);
				}
				
				
				$entries = $data->entry;
				
				// Get columns			
				foreach($entries as $entry){
					
					$col = substr($entry->title->_t,0,1);
					$row = substr($entry->title->_t,1,strlen($entry->title->_t));
					
					if($row == '1' && $entry->content->type == 'text'){
						$gds['entry']['cols'][$col] = array(
							'location' => array(
								'row' => $row,
								'col' => $col,
								'cell' => $entry->title->_t,
							),
							'content' => $entry->content->_t
						);
					}else{
						break;
					}
				}
				
				foreach($entries as $entry){
					
					$col = substr($entry->title->_t,0,1);
					$row = substr($entry->title->_t,1,strlen($entry->title->_t));
					
					if($row > 1 && array_key_exists($col,$gds['entry']['cols'])){
						$colname = $gds['entry']['cols'][$col]['content'];
						
						$content = stripcslashes($entry->content->_t);
						$content = nl2br($content);
						$content = str_replace("\n","",$content);
						
						$content = explode('<br />',$content);					
						$content = count($content) > 1 ? $content : $content[0];
						
						$gds['entry']['rows'][$row - 1][$colname] = array(
							'location' => array(
								'row' => ($row - 1),
								'col' => $col,							
								'cell' => $entry->title->_t
							),
							'type' => count($content) > 1 ? 'array' : $entry->content->type,
							'content' => $content
						);
					}
				}
				
				file_put_contents($dir.'/'.$code.'.gds',json_encode($gds));
				
			}
			
			$this->gds = json_decode(file_get_contents($dir.'/'.$code.'.gds'));
			
			//exit($this->gds);
		}
	}
	
	/**
	 * Get row information
	 *
	 * @param int $row Row number
	 * @param string $key Column label
	 * @param bool $raw True to return raw value, false to return string value only
	 * @return mixed Return value if found, else return NULL
	 */
	public function get_row($row,$key = '',$raw = false){
		
		if(isset($this->gds->entry->rows->{$row})){
			
			if(empty($key)){				
				$t = $this->gds->entry->rows->{$row};
			}else{
				if(!$this->is_col_exists($key)) return NULL;
				
				$t = $this->gds->entry->rows->{$row}->{$key};
			}
			
			if($raw){
				if(empty($key)){
					return $t;
				}else{
					return array(
						'type' => $t->type,
						'content' => $t->content
					);
				}
			}else{
				if(empty($key)){
					$ret = array();
					foreach($t as $key => $_t){
						$ret[$key] = $_t->content;
					}
					return $ret;
				}else{
					return $t->content;
				}				
			}
			
		}else{
			return NULL;
		
		}
	}
	
	/**
	 * Get row count
	 *
	 * @return int Return row count
	 */
	public function get_row_count(){
		if(isset($this->gds->entry->rows)){
			return count($this->gds->entry->rows);
		}else{
			return NULL;
		}
	}
	
	/**
	 * Check if column label exists
	 *
	 * @param string $key Column key
	 * @return bool Return true if found, false if not
	 */
	private function is_col_exists($key){		
		foreach($this->gds->entry->cols as $col => $value){
			if($value->content == $key) return true;
		}
		return false;
	}		
}
/* End of file gds.php */
?>