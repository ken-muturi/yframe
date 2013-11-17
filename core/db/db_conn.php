<?php
/**
 * An agnostic database wrapper
 * @depends : PDO PECL extension (see http://php.net/manual/en/book.pdo.php)
 * @depends : PHP >= 5.2.9
 * @yours-truly : ndungi@gmail.com
 * @license : 
 * *********************************************************************
 *  Basically, you can do whatever you want, as long as you don’t say you 
 *  wrote the library or hold the developer liable for any damages using 
 *  it might cause.
 * *********************************************************************
 * */

class db_conn extends PDO
{
	public $pk = 'id';
    public $prepared_stmt = array();
    public $tables = array();
    public $prepared_queries = 0;
    public $unprepared_queries = 0;
    public $Qstats = array();
    public $have_errors = FALSE;
    public $table_aliases = array();
    public $dbname = '';
    
    public static $_db = array();

    /**
     * __construct function
     * @param string $user   
     * @param string $pass   
     * @param string $dbname 
     * @param string $dbhost 
     * @param string $dbtype 
     */
    public function __construct ($user, $pass, $dbname, $dbhost, $dbtype)
    {		
        $this->dbname = $dbname;
        try 
        {
            $conn = parent::__construct("{$dbtype}:host=" . $dbhost . ";dbname=" . $dbname , 
                                        $user, $pass, 
                                        array(  PDO::ATTR_PERSISTENT => true, 
                                                PDO::ATTR_ERRMODE => true, 
                                                PDO::ERRMODE_EXCEPTION => true
                                        )
                            );

            $get_tables_sql = "
            SELECT information_schema.columns.table_name AS 'table', information_schema.columns.column_name AS 'field' 
            FROM information_schema.columns
            CROSS JOIN information_schema.tables ON information_schema.tables.table_name = information_schema.columns.table_name 
            WHERE information_schema.tables.table_type = 'BASE TABLE' 
            AND information_schema.tables.table_schema =  DATABASE()
            AND information_schema.columns.table_schema = DATABASE()
            ";

            $tables_meta = $this->query($get_tables_sql)->fetchAll();
            
            //this get the fields of each table and prepares the tables relationships
            $this->fields($tables_meta)->relations();

            return $this;            
        } 
        catch (PDOException $e) 
        {            
            echo($e->getMessage());            
        }
           
    }

    /**
     * instance - create a factory pattern instance connection to the db.
     * @param  string $conn 
     * @return resource   -  db connection instance or empty array
     */
    public static function instance($conn = 'default')
    {			
        static $_db = array();

        if (!isset($_db[$conn])) 
        {	
			if (!count($_db))
				$_db = array();
				
			$_db[$conn] = null;
		}
        
        //create a db connection instace from connection parameters
        if ($_db[$conn] == null) 
        {
			$conf = util::conf();
			$_db[$conn] = new self(
				$conf["db_{$conn}"]["username"],
				$conf["db_{$conn}"]["password"],
				$conf["db_{$conn}"]["name"],
				$conf["db_{$conn}"]["host"],
				$conf["db_{$conn}"]["type"]
			);
	
        }

        return $_db[$conn];        
    }

    /**
     * fields  returns an array of table_name => array(respective fields)
     * @return [type] [description]
     */
    private function fields($tables)
    {
        foreach ($tables as $table) 
        {
            $this->tables[$table['table']]['fields'][] = $table['field']; 
        }
        return $this;
    }

    /**
     * relations function - This prepares the tables relationships
     */
    private function relations()
    {
        $tbls = $this->tables;
        foreach ($this->tables as $table => $fields) 
        {
            isset($this->table_aliases[$table]) and ($table = $this->table_aliases[$table]);
                
            $table_fk = preg_replace("/s$/", "", $table) . "_{$this->pk}";
            
            //define the possible relationships
            $this->tables[$table]['rships'] = array('has_one' => array(), 'has_many' => array(), 'many_to_many' => array());

            $table_fields = $fields['fields'] ;
            
            foreach ($tbls as $tbl => $flds) 
            {                    
                isset($this->table_aliases[$tbl]) and ($tbl = $this->table_aliases[$tbl]);
                
                //create a tables foreign key
                // by replacing last space with nothing then appending the primary key to the end
                $tbl_fk = preg_replace("/s$/", "", $tbl) . "_{$this->pk}";
                
                //get all table fields from the tables object
                $tbl_fields = $flds['fields'] ;

                //check for a substring of the foreign key is found among the inner table fields
                if (self::in_fields($table_fk, $tbl_fields)) 
                {    
                    //check for a many to many table
                    //else its a has many relationship
                    if (preg_match("/\_{$table}|{$table}\_/", $tbl)) 
                    {
                        $this->tables[$table]['rships']['many_to_many'][] = preg_replace("/\_{$table}|{$table}\_/", "", $tbl);                        
                    } 
                    else 
                    {                        
                        $this->tables[$table]['rships']['has_many'][] = $tbl;
                    }                                        
                }
                
                //check for a substring of the foreign key is found among the table fields
                // to define a has one relationship
                if (self::in_fields($tbl_fk, $table_fields)) 
                {    
                    $this->tables[$table]['rships']['has_one'][] = $tbl; 
                } 
            }                      
        }
    }

    /**
     * in_fields - Checks for the occurence of a substring in an array of fields
     * @param  [type] $fk     
     * @param  array  $fields 
     * @return boolean     
     */
    private function in_fields( $fk, $fields = array())
    {
        foreach ($fields as $field) 
        {
            if (preg_match("/{$fk}$/i", $field))  
            {
                return true;
            }
        }        
    }
    
    public function __destruct()
    {
        if (DEVELOPMENT) 
        {			
			$query_log  = "<pre>";
			$query_log .= "<br/>Prepared Queries : " . $this->prepared_queries;
			$query_log .= "<br/>Unprepared Queries : " . $this->unprepared_queries;			
			$query_log .= "<br/>Total Queries : " . ($this->prepared_queries + $this->unprepared_queries);

			$query_log .= "<br/>=======================================<br/>";
			
			foreach ($this->Qstats as $key => $times) {
				$query_log .= " " . $this->prepared_stmt[$key]->queryString . " --> " .  $times . "<br/>";
			}
			$query_log .= "<br/></pre>";
        }
    }    
    
}

/**
 *  End of db_conn.php
 */