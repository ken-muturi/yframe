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

class db implements Iterator
{
	public $pk;
    	
    public          $query;
    public          $params;
    public	        $limit = array();
    public  static  $conn;
    public          $count;
    
    public          $select;
    public          $from;
    public          $join;
    public          $where;
    public          $and;
    public          $or;
    public          $in;
    public          $order;
    public          $insert;
    public          $update;
    public          $delete;
    public          $set;
    
    public          $get = null;
    public			$executed = false;
    public $dbconn ;
    
    /**
     * __construct - Does the db connections by calling db_conn class
     * @param string $dbname 
     */
    public function __construct ($dbname = 'default')
    {
        $this->dbname = $dbname;
        $this->dbconn = db_conn::instance($dbname);

        //get the primary key
        $this->pk = $this->dbconn->pk ;
    }

    /**
     * Static Instance - Singleton function that does the class instantiation 
     * @param  string $dbname [description]
     * @return [type]         [description]
     */
    public static function instance($dbname = 'default')
    {
        static $db = null;
        // if connection does not exists
        if ($db == null) 
        {
            $db = new self($dbname);
        }
        
        return $db;
    }

    /**
     * Static Factory - Class instantiation 
     * @param  string $dbname [description]
     * @return type         [description]
     */
    public static function factory($dbname = 'default')
    {
        return new self($dbname);
    }

    /**
     * insert -  Prepare insert data stmt to a table and returns $this for method chaning
     * @param  string $table  
     * @param  array $fields 
     * @return  object       
     */
    public function insert ($table, $fields = null)
    {
        //table exists ?
        $table = self::table ($table) ;
       
	    $keys = array();
        $values = array();
        if (is_array($fields)) 
        {
            foreach ($fields as $field => $value) 
            {
                $keys[] = "{$field}";
                $values[] = $value;
            }

			$keys = join(",", $keys);
			$values = join(",", $values);
        } 
        else 
        {
            throw new Exception("Please supply an array(key => value) pairs to insert");
        }      
        
        //apply insert string to query string     
        $this->query = " INSERT INTO {$table} ({$keys}) VALUES ({$values})";

        return $this;
    }

    /**
     * Update -  Prepare update data stmt of a table and returns $this for method chaning
     * @param  string $table  
     * @param  array $fields
     * @return object
     */
    public function update ($table, $fields = null)
    {
        //table exists ?
        $table = self::table ($table) ;
                
        $updates = array();
        if (is_array($fields)) 
        {
            foreach ($fields as $field => $value) 
            {    
                if (isset(db_conn::instance()->tables[$table]['types'][$field]) 
				and db_conn::instance()->tables[$table]['types'][$field] == 'tiny_int(1)') ;
                
                $updates[] = "$field = '$value'";
            }
            
            $updates = join(",", $updates);
        } 
        else 
        {
            throw new Exception("Please supply an array(key => value) pairs to update");
        }
        
        //apply update string to query string
        $this->query = " UPDATE {$table} SET {$updates} WHERE true ";

        return $this;
    }

    /**
     * Prepare Delete stmt from a table
     * @param  string $table
     * @return object
     */
    public function delete ($table)
    {
        //table exists ?
        $table = self::table ($table) ;        
        
        if (!isset($table))
            throw new Exception("Please supply table name");
                
        //apply delete string to query string
        $this->query = " DELETE FROM {$table} WHERE true ";
        
        return $this; 
    }
    
    /**
     * Select - Prepare the select column fields
     * @param  [type] $fields [description]
     * @return [type]         [description]
     */
    public function select ($fields = null)
    {        
        $select = "*";
        if (isset($fields)) 
        { 
            $select = array();
            if (!is_array($fields))
                $fields = func_get_args();

            $sel = array();
            foreach ($fields as $field => $alias) 
            {
               $sel[] = (!is_numeric($field)) ? "$field AS $alias" : "$alias";
            }
            
            $select = join(",", $sel);
        }

		//apply select string to query string
        $this->query = " SELECT {$select} ";

        return $this; 
    }

    /**
     * [from description]
     * @param  [type] $tables [description]
     * @return [type]         [description]
     */
    public function from ($tables = null)
    {
        if (!is_array($tables))        
            $tables = func_get_args();
            
        if (is_array($tables)) 
        {            
            $from = array();   
            foreach ($tables as $table => $alias) 
            {    
                // not empty table and not a int value
                if ($table && ! is_int($table)) 
                {                        
                    $table = self::table ($table) ;
                    $from[] = " {$table} AS {$alias} ";        
                } 
                else 
                {    
                    $alias = self::table ($alias) ;
                    $from[] = " {$alias} ";                    
                }
            }            
        } 
        else 
        {    
            $from = $tables;    
        }

        //apply from string to query string    
        $this->query .= "FROM " . join(',', $from) . " WHERE true ";
        
        return $this;
    }    

    /**
     * [join description]
     * @param  array  $joins [description]
     * @return [type]        [description]
     */
    public function join ($joins = array())
    {
        $join = array();
        foreach ($joins as $field => $value) 
        {
            $join[] = " {$field} = {$value} ";
        }

        //apply join /inner string to query string
        $this->query .= " AND " . join(" AND ", $join);
        
        return $this;
    }

    /**
     * [left_join description]
     * @param  [type] $table [description]
     * @param  array  $on    [description]
     * @return [type]        [description]
     */
    public function left_join ($table, $on = array())
    {
        $condition = '';
        foreach ($on as $field => $value) 
        {
            $condition .= " {$field} = {$value} ";
        }

        //apply left join string to query string
        $this->query = preg_replace("/(WHERE true)/i", "LEFT JOIN {$table} ON {$condition} \\1", $this->query);

        return $this;
    }

    /**
     * [where description]
     * @param  array  $conditions [description]
     * @return [type]             [description]
     */
    public function where ($conditions = array())
    {
        if (!is_array($conditions)) 
        {
            $params = func_get_args();
            $conditions = array($this->pk => $params[0]);
        }
        
        foreach ($conditions as $field => $value) 
        {
			// handle sub-selects
			if ($value instanceof db) 
            {
				$value = "({$value->query})";
			}
			
            //if starts with an optional space followed by multiple occurence of regexp / between / is null / is not null / greater than or less than value passed with an optional space and other statements following
            if (preg_match("/^ *(regexp|between|is not null|is null|\>|\<)/i", $value) 
                || preg_match("/ *(regexp|between|is not null|is null|\>|\<) *$/i", $field)) 
            {
                $where[] = " {$field} {$value} ";
            } 
            else 
            {
				if (is_bool($value)) 
                {
                    //true / false to 1 / 0
					$value = ($value) ? 1 : 0;
					$where[] = " {$field} = {$value} ";
				} 
                elseif (is_int($value))
                { 
					$where[] = " {$field} = {$value} ";
				} 
                else 
                {
                    //quote the strings
					$where[] = " {$field} = '{$value}' ";
				}
			}
        }
        
        //apply where string to query string
        if (count($conditions))
        {
            $this->query .= " AND " . join(" AND ", $where);
        }

        return $this;
    }

    /**
     * [_and description]
     * @param  array  $conditions [description]
     * @return [type]             [description]
     */
    public function _and($conditions = array())
    {
        if (!is_array($conditions)) 
        {
            $params = func_get_args();
            $conditions = array($this->pk => $params[0]);
        }
        
        foreach ($conditions as $field => $value) 
        {
			// handle sub-selects
			if ($value instanceof db) 
            {
				$value = "({$value->query})";
			}
			
            if (preg_match("/^ *(between|is not null|is null|\>|\<)/i", $value) 
                || preg_match("/ *(between|is not null|is null|\>|\<) $/i", $field))
                $where[] = " {$field} {$value} ";
            else
                $where[] = " {$field} = '{$value}' ";
        }

        //apply AND to query string
        $this->query .= " AND " . implode(" AND ", $where);

        return $this;
    }

    public function _or($conditions = array())
    {			
        if (!is_array($conditions)) 
        {
            $params = func_get_args();
            $conditions = array($this->pk => $params[0]);
        }
        
        foreach ($conditions as $field => $value) 
        {
			// handle sub-selects
			if ($value instanceof db) 
            {
				$value = "({$values->query})";
			}
			
            if (preg_match("/^ *(between|is not null|is null|\>|\<)/i", $value) 
                or preg_match("/ *(between|is not null|is null|\>|\<) $/i", $field))
                $where[] = " {$field} {$value} ";
            else
                $where[] = " {$field} = '{$value}' ";
        }

        //apply OR to query string
        $this->query .= " OR " . implode(" OR ", $where);

        return $this;
    }

    /**
     * [and_in description]
     * @param  [type] $field  [description]
     * @param  array  $values [description]
     * @return [type]         [description]
     */
    public function and_in ($field, $values = array())
    {
		// handle sub-selects
		if ($values instanceof db) 
        {
			$values = preg_replace('/\*/', "{$field}", "{$values->query}");
		}		
		if (!$values) 
        {
			$values = "''";	
		}

        //apply AND IN to query string
        $this->query .= " AND {$field} IN(" . $values . ") ";

        return $this;
    }

    /**
     * [and_not_in description]
     * @param  [type] $field  [description]
     * @param  array  $values [description]
     * @return [type]         [description]
     */
    public function and_not_in ($field, $values = array())
    {
		// handle sub-selects
		if ($values instanceof db) 
        {
			$values = preg_replace('/\*/', $this->pk, $values->query);
		}		
		
		if (!$values) 
        {
			$values = "''";	
		}
					
		if (is_array($values)) 
        {
			$values = join(", ", $values) ;
		}
        //apply AND NOT IN to query string
        $this->query .= " AND {$field} NOT IN(" . $values . ") ";

        return $this;
    }

    /**
     * [or_in description]
     * @param  [type] $field  [description]
     * @param  array  $values [description]
     * @return [type]         [description]
     */
    public function or_in ($field, $values = array())
    {
		if (!$values) 
        {
			$values = "''";	
		}
		    	
        if (!is_array($values)) 
        {
            throw Exception ('database::in usage -> in ($field, $values = array())');
        }

        //apply OR IN to query string
        $this->query .= " OR {$field} IN(" . join(", ", $values) . ") ";

        return $this;
    }

    /**
     * [limit description]
     * @param  integer $limit  [description]
     * @param  integer $offset [description]
     * @return [type]          [description]
     */
    public function limit ( $limit = 10, $offset = 0) 
    {
        $this->limit = array($limit, $offset);
        
        $this->query = preg_replace("/ *LIMIT (\\?|[0-9]+) *OFFSET *(\\?|[0-9]+)/i", "", $this->query);

        if (preg_match("/^ *select/im", $this->query))
            $this->query .= " LIMIT {$limit} OFFSET {$offset} ";


        return $this;
    }

    /**
     * [order description]
     * @param  [type] $by    [description]
     * @param  [type] $order [description]
     * @return [type]        [description]
     */
    public function order ($by = null, $order = null) 
    {
        if (!$by) return $this;
        if (!$order) return $this;
        
        if (preg_match("/order +by /i", $this->query ))
            $this->query  = preg_replace("/order +by +.+ *, *.+ +(desc|asc|rand\(\))*/i", " ", $this->query );
        else
            $this->query .= " ORDER BY {$by} {$order} ";
        
        return $this;
    }

    /**
     * [group_by description]
     * @param  string $field [description]
     * @return [type]        [description]
     */
    public function group_by ($field = "") 
    {
        $this->query .= " GROUP BY {$field} ";
        return $this;
    }
    
    /**
     * [params description]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function params($params = null) 
    {
        if (!is_array($params)) 
        {
            $this->params = func_get_args();
        } 
        else 
        {
            $this->params = $params;       
        }
        
        return $this;
    }
    
    /**
     * [run description]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function run ($params = null)
    {
        //handle quoted params
        $this->query = preg_replace("/= *\'\?\'/", "= ?", $this->query);
        
        //prevent user from doing something extremely stupid .. 
        if (preg_match ("/^delete from * WHERE true $/i", $this->query))
            throw Exception ("What the heck are you trying!! {$this->query} ?! ");
            
        //prevent something else stupid
        if (preg_match ("/^update * WHERE true $/i", $this->query))
            throw Exception ("What the heck are you trying!! {$this->query} ?! ");
                        
        if (!is_array($params))
            $params = func_get_args() ;

        if (isset($this->params))
            $params = array_merge($params, $this->params);

        if (isset($this->limit_params))
            $params = array_merge($params, $this->limit_params);
            
        //retrieve a previously prepared stmt
        if (!$stmt = util::find_stmt($this->query, $this->dbconn->prepared_stmt)) 
        {    
            //dont waste the prepared stmt; store for later use
            $stmt = $this->dbconn->prepare($this->query);
            $this->dbconn->prepared_stmt[] = $stmt;
            
            $this->dbconn->unprepared_queries ++;   
        } 
        else 
        {     
            $this->dbconn->prepared_queries ++;     
        }
        
        try 
        {    
            foreach ($params as $key => $param) 
            {
                $param = (is_int($param)) ? intval($param) : $param ;
                $type = (is_int($param) ? PDO::PARAM_INT : PDO::PARAM_STR );

                $stmt->bindValue($key + 1, $param, $type) ;
            }

            if (!$return = $stmt->execute()) 
            {
				// util::printr(debug_backtrace());
                throw new Exception();  	
            } 
                      
        } 
        catch (Exception $e) 
        {
            error_log('Here is the troublesome query : \n\n' . $this->query . print_r($params ,1));
			error_log($e->getTraceAsString());
        }
        
        $this->limit_params = array();
        
        if (preg_match("/^ *select/i", $this->query)) 
        {    
            $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $rs = self::stripslashes($rs);
            $this->count = count($rs);
            
            if (count($rs)) 
            {    
                if ($this->get) 
                {	
					$_rs = array();
					foreach ($rs as $r) 
                    {
						$_rs[] = $r[$this->get];
					}
						
					if ($this->get_array) 
                    {
                       return $_rs;
                    }
                    
					return (count($_rs) > 1) ? $_rs : $_rs[0];
				}
				
                return $rs;
            } 
            else 
            {    
                return null;       
            }  
        } 
        else 
        {
            return $stmt->rowCount();
        }
            
    }

    public function execute ($params = null)
    {  
        //handle quoted params
        $this->query = preg_replace("/= *\'\?\'/", "= ?", $this->query);
        
        //prevent user from doing something extremely stupid .. 
        if (preg_match ("/^delete from * WHERE true $/i", $this->query))
            throw Exception ("What the heck are you trying!! {$this->query} ?! ");
            
        //prevent something else stupid
        if (preg_match ("/^update * WHERE true $/i", $this->query))
            throw Exception ("What the heck are you trying!! {$this->query} ?! ");
                        
        if (!is_array($params))
            $params = func_get_args() ;

        if (isset($this->params))
            $params = array_merge($params, $this->params);

        if (isset($this->limit_params))
            $params = array_merge($params, $this->limit_params);
            
        //retrieve a previously prepared stmt
        if (!$stmt = util::find_stmt($this->query, $this->dbconn->prepared_stmt)) 
        {    
            //dont waste the prepared stmt; store for later use
            $stmt = $this->dbconn->prepare($this->query);
            $this->dbconn->prepared_stmt[] = $stmt;
            
            $this->dbconn->unprepared_queries ++;
        } 
        else 
        {     
            $this->dbconn->prepared_queries ++;     
        }        
        
        try 
        {    
            foreach ($params as $key => $param) 
            {
                $param = (is_int($param)) ? intval($param) : $param ;
                $type = (is_int($param) ? PDO::PARAM_INT : PDO::PARAM_STR );
                $stmt->bindValue($key + 1, $param, $type) ;
            }
       
            if (!$stmt->execute()) 
            {
                throw new Exception();  
            }
            
            //$this->executed = true;              
        } 
        catch (Exception $e) 
        {
            error_log('Here is the troublesome query : ' . $this->query . print_r($params ,1));
            error_log($e->getMessage());
            error_log($e->getTraceAsString());
        }
        
        $this->limit_params = array();
        
        if (preg_match("/^ *select/i", $this->query)) 
        {    
            $this->count = null;   
			return $stmt;          
        } 
        else 
        {
	       	return $stmt;
            return $stmt->rowCount(); 
        }        
    }

    /**
     * [count description]
     * @return [type] [description]
     */
    public function count()
    {
        $count_query = preg_replace('/^ *(select) *(.+) *(from .+$)/iUm', '$1 count(*) as count $3', $this->query);
        $count_query = preg_replace("/limit  *[^ ]* OFFSET *[^ ]*/im", '', $count_query);
        $count_query = preg_replace("/order *by *(.+) /i", 'order by $1 ', $count_query);

        $db = self::factory($this->dbname);
        $db->query = $count_query;
        $db->params = $this->params;

        return $db->get('count')->run();
    }

    /**
     * [get description]
     * @param  [type]  $field     [description]
     * @param  boolean $get_array [description]
     * @return [type]             [description]
     */
    public function get($field, $get_array = false)
    {
        $this->get = $field;
        $this->get_array = $get_array;
        
        return $this;
    }

    /**
     * [stmt_keys description]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public static function stmt_keys($params)
    {
        $keys = array();
        foreach ($params as $key => $value) 
        {
            $keys[$key] = "?";
        }
        return $keys;
    }

    /**
     * [stmt_values description]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public static function stmt_values($params)
    {
        $values = array();
        foreach ($params as $value) 
        {
            $values[] = $value;
        }
        return $values;
    }
    
    /**
     * [stripslashes description]
     * @param  array  $array [description]
     * @return [type]        [description]
     */
    public static function stripslashes ($array = array())
    {
        $rows = $array;
        if (is_array($rows)) 
        {
            foreach ($rows as $k => $row) 
            {
                $rows[$k] = self::stripslashes($row);
            }      
        }  
        else 
        {
            return stripslashes($rows);    
        } 	
        return $rows;
    }
    
    /**
     * [table description]
     * @param  [type] $table [description]
     * @return [type]        [description]
     */
    public function table ($table) 
    {
        ($key = array_search($table, $this->dbconn->table_aliases)) and 
        !is_int($key);

        if ($key) return $key;
        else return $table;        
    }
    
    /*
     * Iterator functions
     * */
    public function rewind()
    {
        ;
    }
    
    public function next()
    {
        ;
    }
    
    public function current()
    {
        
    }
    
    public function key()
    {
        ;
    }
    
    public function valid()
    {
        ;       
    } 
  
}



/* 
 * 
 * Some tests
 * 
$user = new DAO("users",1);


$rs = $db->insert("users", array("username"=> "?",
                            "password" => "?",
                            "email" => "?"))
         ->exec("alizabeth",
                "password('ng'a maina')", 
                "ndungi@gmail.com");
            
$rs = $db->select(array("email" => "username"))->from("users")->exec();
echo "<pre>" .  print_r($rs, 1) . "</pre> {$db->query}";
foreach ($rs as $row)
    echo $row['username'] . "<br/>";


echo "<pre>" .  print_r($user->get(), 1) . "</pre> ";
 */ 
