## CodeIgniter Google Drive Spreadsheet Library

Use to retrieve data feeds from publicy shared spreadsheet from Google Drive


## How to use

$this->load->library('gds');<br/><br/>

// Param<br/>
// - code = google doc spreadsheet code (REQUIRED)<br/>
// - data_type = Type of data to retrieve (DEFAULT: json)<br/>
// - dir = Dir path where to save the local file (OPTIONAL)<br/>
$this->gds->set('code=<google doc spreadsheet code>&data_type=json');<br/><br/>

// Get row<br/>
$this->gds->get_row(1,'<column name>');<br/>