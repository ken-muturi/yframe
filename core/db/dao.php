<?php

/**
 * A db access object or an ORM if you like
 * @depends : PDO PECL extension (see http://php.net/manual/en/book.pdo.php)
 * @depends : PHP >= 5.2.9
 * @license : 
 * *********************************************************************
 *  Basically, you can do whatever you want, as long as you don’t say you 
 *  wrote the library or hold the developer liable for any damages using 
 *  it might cause.
 * *********************************************************************
 * */
 
class DAO implements Iterator
{
    public static $db;
    public $table;
    public $rships ;
    public $pk = 'id';
    public $fk ;
    public $fields;
    public $conditions = array();
    public $order = array('field' => "id", 'direction' => "ASC");

    // Iterator relevant variables
    public $objs_query;
    public $dbname = 'default';
    
    public $auto_update_timestamp = false;
    
    public function __construct($table, $id = null)
    {   
        db_conn::instance($this->dbname)->pk = $this->pk;

        $this->table = $table;
        
        $this->fields = db_conn::instance($this->dbname)->tables[$this->table]['fields'];
        $this->fk = preg_replace("/s$/i", "", $this->table) . "_" . $this->pk;

        //map out the relationships between different entities and store in rships array
        $this->rships = db_conn::instance($this->dbname)->tables[$this->table]['rships']; 

        if (is_array($id)) 
        {	
            $this->conditions = $id;            
            $this->objs_query = db::factory($this->dbname)
                            ->select('*')
                            ->from($this->table)
                            ->where(db::stmt_keys($id))
                            ->params(db::stmt_values($id))
                            ;    
        } 
        elseif ($id instanceof db) 
        {            
            $this->objs_query = $id;

        } 
        elseif ($id == "*") 
        {    
            $this->objs_query = db::factory($this->dbname)
                            ->select('*')
                            ->from($this->table)
                            ;
        } 
        else 
        {        
            $id = (isset($this->id) and $this->id) ? $this->id : $id ;            
            $this->objs_query = db::factory($this->dbname)
                            ->select('*')
                            ->from($this->table)
                            ->where(db::stmt_keys(array('id' => $id)))
                            ->params(db::stmt_values(array('id' => $id)))
                            ;            
        }       
    }

    public function __get($var)
    {
        if (in_array($var . 's', $this->rships['has_one'])) 
        {
            return $this->load_has_one()->$var;    
        }        
        
        if (in_array($var, $this->rships['has_many'])) 
        {
            return $this->load_has_many()->$var;    
        }        
        
        if (in_array($var, $this->rships['many_to_many'])) 
        {
            return $this->load_many_to_many($var)->$var;    
        }        
        
        if (in_array($var, $this->fields)) 
        {  
            $obj = $this->load_simple();
            return isset($this->$var) ? $this->$var : null;
        } 
        
        return ;
    }

    public static function factory($obj, $id = null)
    {
        return new self($obj, $id);   
    }

    public function multirow () 
    {
        return ($this->count() > 1);
    }

    /*
     * do an ad-hoc join on an object
     * */
    public function with($obj, $conditions)
    {
        $q = $this->objs_query->query;
    }

    /**
     * load_simple function - load the table/objects own 1-to-1 attributes from the table's fields
     * @param  string $property [description]
     * @return $this  object
     */
    public function load_simple($property = '*') 
    {
        $db = db::factory();
        $db->query = $this->objs_query->query;
        $db->params = $this->objs_query->params;
        
        $attrs_r = $db->order($this->order['field'], $this->order['direction'])->limit(1, 0)->run();

        if (isset($attrs_r[0])) 
        {    
            foreach ($attrs_r[0] as $attr => $value) 
            {
                $this->$attr = $value ;
            }            
        }  
        return $this;
    }
 
    /**
     * load_simple function - load the objects 1-to-1 attributes whose info is referenced in other tables
     * @param  string $property [description]
     * @return $this  object
     */      
    public function load_has_one()
    {
        foreach ($this->rships['has_one'] as $key => $table) 
        {    
            $attr = preg_replace("/s$/", "", $table);
            $attr_fk = $attr . "_{$this->pk}";

            if (in_array($attr_fk, $this->fields)) 
            {                
                if (class_exists($model = preg_replace("/s$/", "", $table))) 
                {
                    $id = $this->$attr_fk;
                    $this->$attr = new $model($id);
                } 
                else 
                {   
                    $this->$attr = new DAO($table, $this->$attr_fk);   
                }
            }
        }

        return $this;        
    }
    
    /**
     * load_simple function - get the has_many (one-to-many) attributes
     * @param  string $property [description]
     * @return $this  object
     */        
    public function load_has_many()
    {
        foreach ($this->rships['has_many'] as $key => $attr) 
        {    
            $attr_fk = preg_replace("/s$/", "_{$this->pk}", $attr);
            
            $objs_query = db::factory($this->dbname)->select('*')
                        ->from($attr)
                        ->and_in($this->fk, preg_replace("/\*/", 'id', $this->objs_query->query))
                        ->params($this->objs_query->params)                        
                        ;

            $this->$attr = (class_exists($model = preg_replace("/s$/", "", $attr))) ? new $model($objs_query) : new DAO($attr, $objs_query);
        }        
        return $this;
    }
    
    /**
     * load_many_to_many get the many-to-many attributes
     * @param  [type] $attr [description]
     * @return [type]       [description]
     */
    public function load_many_to_many($attr)
    {
        $attr_fk = preg_replace("/s$/", "_id", $attr);
        $pivot = self::get_pivot($attr, $this->table);
        
        $objs_query = db::factory()
                    ->select(" {$attr}.*  ")
                    ->from($attr, $pivot)
                    ->join(array(
                        "{$attr}.id" => "{$pivot}.{$attr_fk}",
                        "{$this->fk}" => "{$pivot}.{$this->fk}"
                    ))
                    ->and_in("{$pivot}.{$this->fk}", preg_replace("/\*/", 'id', $this->objs_query->query))
                    ->params($this->objs_query->params)
                    ;
            
        $this->$attr = (class_exists($model = preg_replace("/s$/", "", $attr))) ? new $model($objs_query) : new DAO($attr, $objs_query);

        return $this;
    }
    
    /* add a many-to-many attribute association
     * @param   string  $table  the other table in the many to many rship
     * @param   string  $attr   the attribute from $table to be added
     * 
     * @return  DAO $this   returns itself
     * */
    public function add($table, $attr)
    {
        $obj = new self($table, array(preg_replace("/s$/i", "", $table) => $attr));
        return $this->associate($obj);
    }
    
    /**
     * [belongs_and_has description]
     * @param  [type] $obj [description]
     * @return [type]      [description]
     */
    public function belongs_and_has($obj) 
    {
        $pivot = self::get_pivot($this->table, $obj->table);
        
        return db::factory()->insert($pivot, array("{$this->fk}" => "?",
                                              "{$obj->fk}" => "?"))
                ->run($this->{$this->pk}, $obj->{$this->pk});                
    }

    /*
     * belongs_and_has = does a belongs_and_has()
     * recursive method
     * */
    public function associate($obj) 
    {
        if (is_array($obj)) 
        {    
            foreach ($obj as $dao) 
            {
                $this->associate($dao);                   
            }           
            return true;            
        } 
        else 
        {
            return !$this->has($obj) and 
                db::factory()->insert(self::get_pivot($this->table, $obj->table),
                array("{$this->fk}" => "?",
                       "{$obj->fk}" => "?"))
                ->run($this->{$this->pk}, $obj->{$this->pk});
        }            
    }

    /**
     * dissociate - undoes a belongs_and_has()
     * @param  DAO    $obj [description]
     * @return [type]      [description]
     */
    public function dissociate(DAO $obj) 
    {    
        if ($obj->multirow()) 
        {
            $del = db::factory()->delete(self::get_pivot($this->table, $obj->table))
            ->where(array($this->fk => $this->id))
            ->run();
            return true;    
        } 
        else 
        {
            return db::factory()->delete(self::get_pivot($this->table, $obj->table))
                    ->where( 
                         array("{$this->fk}" => "?",
                               "{$obj->fk}" => "?"))
                    ->run($this->{$this->pk}, $obj->{$obj->pk});       
        }
    }

    /**
     * removes a many-to-many attribute association
     * @param   string  $table  the other table in the many to many rship
     * @param   string  $attr   the attribute from $table to be added
     * @return [type]        [description]
     */
    public function remove($table, $attr)
    {
        //select $attr id
        $attr_r = db::factory()->select($this->pk)->from($table)
                       ->where(array(preg_replace("/s$/i", "", $table) => "?"))
                       ->run($attr);
             
        $attr_fk = preg_replace("/s$/i", "", $table) . "_{$this->pk}";
        
        db::factory()->delete(self::get_pivot($this->table, $table))
             ->where(array("{$this->fk}" => $this->id,
                          "{$attr_fk}" => "?"))
             ->run($attr_r[0][$this->pk]);

        return $this;
    }
    
    /**
     * delete method : deleted the db object and its attribs
     * @return [type] [description]
     */
    public function del() 
    {
		// multiple items
		if (isset($this->objs_query->query)) 
        {
			$dbq = db::factory();
            $dbq->query = preg_replace("/ +select *.+ +from/i", 'DELETE FROM', $this->objs_query->query);
            $dbq->run($this->objs_query->params);
			return true;
		}
		
        // single item
        $del = db::factory()->delete($this->table)->where($this->{$this->pk});
		$del->run();
        
        return true;
    }

    /**
     * [has description]
     * @param  DAO     $obj [description]
     * @return boolean      [description]
     */
    public function has (DAO $obj)
    {
    	return (bool) 
            db::factory($this->dbname)->select($this->fk)
			 ->from(self::get_pivot($this->table, $obj->table))
			 ->where(array("{$this->fk}" => "?", "{$obj->fk}" => "?"))
			 ->get($this->fk)
			 ->run($this->{$this->pk}, $obj->{$obj->pk});
    }

    /**
     * [has_one_of description]
     * @param  DAO     $objs [description]
     * @return boolean       [description]
     */
    public function has_one_of (DAO $objs)
    {
        $thisobj = preg_replace("/s$/", "", $this->table);
        foreach ($objs as $obj)
        {
            if ($this->{$this->pk} == $obj->$thisobj->{$this->pk})
            {
                return $obj;
            }    
        }

        return false;
    }
    
    /*
     * save method
     * 1. save only the updated fields ; has faster db writes :)
     * 2. insert if new record, update if existing
     * @return  db $db   this object
     * */
    public function save()
    {
        $data = array();
        $properties = (array)$this;
        foreach ($properties as $field => $value) 
        {            
            if (in_array($field, $this->fields)) 
            {
                $data[$field] = $value;
            } 
            else 
            {
                if ($field == 'timestamp' and $this->auto_update_timestamp) 
                {
                   $data[$field] = time();
                }                
            }            
        } 
    
        if (isset($data[$this->pk]) and $data[$this->pk]) 
        {
            $update = db::factory()
                    ->update($this->table, db::stmt_keys($data))
                    ->where(array(
                        $this->pk => $this->{$this->pk}
                    ))
                    ->run(db::stmt_values($data))
                    ;
                
            return true;        
        }
            
        if ($this->count()) 
        {                
            $ids = $this->objs_query->get($this->pk, $array = true)->run();
            if (count($data) and count($ids))  
            {  
                $ids = "'" . join("', '", $ids). "'" ;
                $update = db::factory()
                    ->update($this->table, db::stmt_keys($data))
                    ->and_in($this->pk, $ids)
                    ->run(db::stmt_values($data));        
                return true;
            }
        } 
        else 
        {
            foreach ($this->fields as $field) 
            {
                if (isset($this->$field)) 
                {
                    $data[$field] = $this->$field;
                } 
                else 
                {
                    if ($field == 'timestamp') 
                    {
                       $data[$field] = time();
                    }
                }
            } 
            
            if (count($data))  
            {  
               $insert = db::factory()
                    ->insert($this->table, db::stmt_keys($data))
                    ->run(db::stmt_values($data));   
               
                $sequence = (PDO::ATTR_DRIVER_NAME == 16)  ? "_seq" : null;    
                $this->{$this->pk} = db_conn::instance($this->dbname)->lastInsertId("{$this->table}_{$this->pk}{$sequence}");
                    
                return $insert;
            }
        }  
  
    }

    /*
     * get_pivot : returns the pivot table of table1 and table2
     * @param   string  $table1
     * @param   string  $table2
     * 
     * @return  string  $table_name;
     * */    
    private function get_pivot($table1, $table2) 
    {
        foreach (db_conn::instance($this->dbname)->tables as $table_name => $desc) 
        { 
            if (preg_match("/^{$table1}\_{$table2}$/", $table_name))
            {
                return $table_name ;
            }
            else if (preg_match("/^{$table2}\_{$table1}$/", $table_name))
            {
                return $table_name ;
            }                
        }
    }
    
    /*
     * Iterator functions
     * */
    public function rewind()
    {
        $this->objs_query->executed = false;
    }

    public function next()
    {
        $this->limit_counter++;
    }
    
    public function current()
    {
        return $this->fetched_obj;
    }
    
    public function key()
    {
        
    }
    
    public function valid()
    {
		if (!$this->objs_query->executed) 
        {
            $dbq = db::factory();
            $dbq->query = $this->objs_query->query;
            $dbq->params = $this->objs_query->params;
            $dbq->order($this->order['field'], $this->order['direction']);
            
			$this->rs = $dbq->execute();
            $this->objs_query->executed = true;
		}
		
        if (class_exists($model = preg_replace("/s$/", "", $this->table))) 
        {
            $this->fetched_obj = $this->rs->fetchObject($model);
        } 
        else 
        {
            $this->fetched_obj = $this->rs->fetchObject("DAO", array($this->table)); 
		}

        return $this->fetched_obj;
    } 

	/*
	 * some methods to navigate objects of this type
	 * these r hacks really, if you can think of anything better please change this
	 * */	 
    public function limit ($limit = 10, $offset = 0) 
    {
        $this->objs_query->limit($limit, $offset);        
        return $this;
    } 
 
    public function before()
    {
		$before_id = db::factory($this->dbname)
						->select($this->pk)
						->from($this->table)
						->where (array($this->pk => " < " .  $this->{$this->pk}))
						->order($this->pk, 'desc')
						->limit(0, 1)
						->get($this->pk)
						->run();
		
		if (!$before_id) return null;
		
        if (class_exists($model = preg_replace("/s$/", "", $this->table)))
            return new $model($before_id);
        else
            return new DAO($this->table, $before_id);		
    }

    public function after()
    {
		$after_id = db::factory($this->dbname)
						->select($this->pk)
						->from($this->table)
						->where (array($this->pk => " > " .  $this->{$this->pk}))
						->order($this->pk, 'asc')
						->limit(1, 0)
						->get($this->pk)
						->run();
		
		if (!$after_id) return null;
		
        if (class_exists($model = preg_replace("/s$/", "", $this->table)))
            return new $model($after_id);
        else
            return new DAO($this->table, $after_id);
    }
    
    public function last()
    {
        if ($this->count() > 0) 
        {     
            $last = $this->objs_query->order($this->pk, 'DESC')->get($this->pk)->run();
            $attr = $this->table;

            if (class_exists($model = preg_replace("/s$/", "", $attr)))
                return new $model($last);                
            else                  
                return new DAO($attr, $last);              
        } 

        return null;
    }
    
    public function count()
    {
        return $this->objs_query->count();  
    }   
    
    public function as_array($field = null)
    {
        $rs = $this->objs_query->get($field, $array = true)->run();
        return (count($rs)) ? $rs : array();
    }

    public function get_array($field = null)
    {
        $result = array();
        foreach ($this as $item) 
        {
            $result[] = $item->$field ;
        }        
        return $result;        
    }
     
    public function row()
    {
    	/*
    	* this method is getting deprecated unless we find good use case for it
    	* we leave it here for those snippets of code that inadvertently used it	* to keep from breaking
    	*/
    	return $this;    
    }
    
    public function order($by, $asc_desc = null)
    {
        if (preg_match("/limit/i", $this->objs_query->query)) 
        {            
            $this->objs_query->query = 
            preg_replace("/limit/i", " order by {$by} {$asc_desc} LIMIT ", $this->objs_query->query);   
        } 
        else 
        {            
            $this->objs_query->query .= " order by {$by} {$asc_desc}"; //  GROUP BY {$this->table}.{$this->pk}  ";    
        }
        return $this;
    }
        
}


/* 
 * **************
 * Some Examples
 * **************
$user = new DAO("users");
$user->username = "niceguy";
$user->email = "niceguy@goodplace.com";
$user->save() and $user->add("roles", "login");
// you have now created a user with the login role
$user->delete();
// you have deleted niceguy, together with all other associated entries (!! so be careful or override this method)

$rs = $db->insert("users", array("username"=> "?",
                            "password" => "?",
                            "email" => "?"))
         ->run("alizabeth",
                "password('ng'a maina')", 
                "ken@gmail.com");
            
$rs = $db->select(array("email" => "username"))->from("users")->run();
echo "<pre>" .  print_r($rs, 1) . "</pre> {$db->query}";
foreach ($rs as $row)
    echo $row['username'] . "<br/>";


echo "<pre>" .  print_r($user, 1) . "</pre> ";



* 
 *******/ 
