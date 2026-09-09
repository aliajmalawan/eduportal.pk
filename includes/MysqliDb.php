<?php
/**
 * MysqliDb Class
 *
 * @category  Database Access
 * @package   MysqliDb
 * @author    Dharm raj Kaushal <info@phpexpertgroup.com>
 * @copyright Copyright (c) 2016-2017
 * @version   2.7-master
 */

class MysqliDb
{

    /**
     * Static instance of self
     * @var MysqliDb
     */
    protected static $_instance;

    /**
     * Table prefix
     * @var string
     */
    public static $prefix = '';

    /**
     * MySQLi instance
     * @var mysqli
     */
    protected $_mysqli;

    /**
     * The SQL query to be prepared and executed
     * @var string
     */
    protected $_query;

    /**
     * The previously executed SQL query
     * @var string
     */
    protected $_lastQuery;

    /**
     * The SQL query options required after SELECT, INSERT, UPDATE or DELETE
     * @var string
     */
    protected $_queryOptions = array();

    /**
     * An array that holds where joins
     * @var array
     */
    protected $_join = array();

    /**
     * An array that holds where conditions
     * @var array
     */
    protected $_where = array();

    /**
     * An array that holds having conditions
     * @var array
     */
    protected $_having = array();

    /**
     * Dynamic type list for order by condition value
     * @var array
     */
    protected $_orderBy = array();

    /**
     * Dynamic type list for group by condition value
     * @var array
     */
    protected $_groupBy = array();

    /**
     * Dynamic array that holds a combination of where condition/table data value types and parameter references
     * @var array
     */
    protected $_bindParams = array(''); // Create the empty 0 index

    /**
     * Variable which holds an amount of returned rows during get/getOne/select queries
     * @var string
     */
    public $count = 0;

    /**
     * Variable which holds an amount of returned rows during get/getOne/select queries with withTotalCount()
     * @var string
     */
    public $totalCount = 0;

    /**
     * Variable which holds last statement error
     * @var string
     */
    protected $_stmtError;

    /**
     * Variable which holds last statement error code
     * @var int
     */
    protected $_stmtErrno;
    
    protected $_transaction_in_progress = false;

    /**
     * Database credentials
     * @var string
     */
    protected $host;
    protected $username;
    protected $password;
    protected $db;
    protected $port;
    protected $charset;

    /**
     * Is Subquery object
     * @var bool
     */
    protected $isSubQuery = false;

    /**
     * Name of the auto increment column
     * @var int
     */
    protected $_lastInsertId = null;

    /**
     * Column names for update when using onDuplicate method
     * @var array
     */
    protected $_updateColumns = null;

    /**
     * Return type: 'array' to return results as array, 'object' as object
     * 'json' as json string
     * @var string
     */
    public $returnType = 'array';

    /**
     * Should join() results be nested by table
     * @var bool
     */
    protected $_nestJoin = false;

    /**
     * Table name (with prefix, if used)
     * @var string 
     */
    private $_tableName = '';

    /**
     * FOR UPDATE flag
     * @var bool
     */
    protected $_forUpdate = false;

    /**
     * LOCK IN SHARE MODE flag
     * @var bool
     */
    protected $_lockInShareMode = false;

    /**
     * Key field for Map()'ed result array
     * @var string
     */
    protected $_mapKey = null;

    /**
     * Variables for query execution tracing
     */
    protected $traceStartQ;
    protected $traceEnabled;
    protected $traceStripPrefix;
    public $trace = array();

    /**
     * Per page limit for pagination
     *
     * @var int
     */

    public $pageLimit = 20;
    /**
     * Variable that holds total pages count of last paginate() query
     *
     * @var int
     */
    public $totalPages = 0;

    /**
     * @param string $host
     * @param string $username
     * @param string $password
     * @param string $db
     * @param int $port
     * @param string $charset
     */
    public function __construct($host = null, $username = null, $password = null, $db = null, $port = null, $charset = 'utf8')
    {
        $isSubQuery = false;

        // if params were passed as array
        if (is_array($host)) {
            foreach ($host as $key => $val) {
                $$key = $val;
            }
        }
        // if host were set as mysqli socket
        if (is_object($host)) {
            $this->_mysqli = $host;
        } else {
            $this->host = $host;
        }

        $this->username = $username;
        $this->password = $password;
        $this->db = $db;
        $this->port = $port;
        $this->charset = $charset;

        if ($isSubQuery) {
            $this->isSubQuery = true;
            return;
        }

        if (isset($prefix)) {
            $this->setPrefix($prefix);
        }

        self::$_instance = $this;
    }

    /**
     * A method to connect to the database
     * 
     * @throws Exception
     * @return void
     */
    public function connect()
    {
        if ($this->isSubQuery) {
            return;
        }

        if (empty($this->host)) {
            throw new Exception('MySQL host is not set');
        }

        $this->_mysqli = new mysqli($this->host, $this->username, $this->password, $this->db, $this->port);

        if ($this->_mysqli->connect_error) {
            throw new Exception('Connect Error ' . $this->_mysqli->connect_errno . ': ' . $this->_mysqli->connect_error);
        }

        if ($this->charset) {
            $this->_mysqli->set_charset($this->charset);
        }
    }

    /**
     * A method to get mysqli object or create it in case needed
     * 
     * @return mysqli
     */
    public function mysqli()
    {
        if (!$this->_mysqli) {
            $this->connect();
        }
        return $this->_mysqli;
    }

    /**
     * A method of returning the static instance to allow access to the
     * instantiated object from within another class.
     * Inheriting this class would require reloading connection info.
     *
     * @uses $db = MySqliDb::getInstance();
     *
     * @return MysqliDb Returns the current instance.
     */
    public static function getInstance()
    {
        return self::$_instance;
    }

    /**
     * Reset states after an execution
     *
     * @return MysqliDb Returns the current instance.
     */
    protected function reset()
    {
        if ($this->traceEnabled) {
            $this->trace[] = array($this->_lastQuery, (microtime(true) - $this->traceStartQ), $this->_traceGetCaller());
        }

        $this->_where = array();
        $this->_having = array();
        $this->_join = array();
        $this->_orderBy = array();
        $this->_groupBy = array();
        $this->_bindParams = array(''); // Create the empty 0 index
        $this->_query = null;
        $this->_queryOptions = array();
        $this->returnType = 'array';
        $this->_nestJoin = false;
        $this->_forUpdate = false;
        $this->_lockInShareMode = false;
        $this->_tableName = '';
        $this->_lastInsertId = null;
        $this->_updateColumns = null;
        $this->_mapKey = null;
    }

    /**
     * Helper function to create dbObject with JSON return type
     *
     * @return MysqliDb
     */
    public function jsonBuilder()
    {
        $this->returnType = 'json';
        return $this;
    }

    /**
     * Helper function to create dbObject with array return type
     * Added for consistency as thats default output type
     *
     * @return MysqliDb
     */
    public function arrayBuilder()
    {
        $this->returnType = 'array';
        return $this;
    }

    /**
     * Helper function to create dbObject with object return type.
     *
     * @return MysqliDb
     */
    public function objectBuilder()
    {
        $this->returnType = 'object';
        return $this;
    }

    /**
     * Method to set a prefix
     *
     * @param string $prefix     Contains a tableprefix
     * 
     * @return MysqliDb
     */
    public function setPrefix($prefix = '')
    {
        self::$prefix = $prefix;
        return $this;
    }

    /**
     * Execute raw SQL query.
     *
     * @param string $query      User-provided query to execute.
     * @param array  $bindParams Variables array to bind to the SQL statement.
     *
     * @return array Contains the returned rows from the query.
     */

     public function rawQuery($query, $bindParams = null)
{
    // Params for mysqli bind_param (first element is types string)
    $params = array('');
    $this->_query = $query;
    $stmt = $this->_prepareQuery();

    // ✅ Log INSERT, UPDATE, DELETE queries first - before execution
    $operationType = null;
    if (preg_match('/^\s*(INSERT|UPDATE|DELETE)/i', $query, $matches)) {
        $operationType = strtoupper($matches[1]);

        if (preg_match('/(?:INSERT\s+INTO|UPDATE|DELETE\s+FROM)\s+`?(\w+)`?/i', $query, $tableMatch)) {
            $tableName = $tableMatch[1];
            
            // Prepare the actual query for logging (with parameters)
            // Build a clean values array for replacePlaceHolders: index 1..N = values.
            $logParams = array();
            if (is_array($bindParams) && !empty($bindParams)) {
                $logParams = array('');
                foreach ($bindParams as $val) {
                    $logParams[] = $val;
                }
            }
            $loggedQuery = $this->replacePlaceHolders($query, $logParams);
            
            // Log before execution
            // if ($tableName !== 'eduportal_application_logs') {
            //     $this->logOperation($operationType, $tableName, $loggedQuery);
            // }
        }
    }

    // Bind parameters for execution
    if (is_array($bindParams) === true && !empty($bindParams)) {
        foreach ($bindParams as $val) {
            $params[0] .= $this->_determineType($val);
            $params[] = $val;
        }
        call_user_func_array(array($stmt, 'bind_param'), $this->refValues($params));
    }

    $stmt->execute();
    $this->count = $stmt->affected_rows;
    $this->_stmtError = $stmt->error;
    $this->_stmtErrno = $stmt->errno;

    // Build params for human-readable lastQuery (no types element)
    $printParams = array();
    if (is_array($bindParams) && !empty($bindParams)) {
        $printParams = array('');
        foreach ($bindParams as $val) {
            $printParams[] = $val;
        }
    }
    $this->_lastQuery = $this->replacePlaceHolders($this->_query, $printParams);

    $res = $this->_dynamicBindResults($stmt);

    $this->reset();
    return $res;
}
    /**
     * Helper function to execute raw SQL query and return only 1 row of results.
     * Note that function do not add 'limit 1' to the query by itself
     * Same idea as getOne()
     *
     * @param string $query      User-provided query to execute.
     * @param array  $bindParams Variables array to bind to the SQL statement.
     *
     * @return array|null Contains the returned row from the query.
     */
    public function rawQueryOne($query, $bindParams = null)
    {
        $res = $this->rawQuery($query, $bindParams);
        if (is_array($res) && isset($res[0])) {
            return $res[0];
        }

        return null;
    }

    /**
     * Helper function to execute raw SQL query and return only 1 column of results.
     * If 'limit 1' will be found, then string will be returned instead of array
     * Same idea as getValue()
     *
     * @param string $query      User-provided query to execute.
     * @param array  $bindParams Variables array to bind to the SQL statement.
     *
     * @return mixed Contains the returned rows from the query.
     */
    public function rawQueryValue($query, $bindParams = null)
    {
        $res = $this->rawQuery($query, $bindParams);
        if (!$res) {
            return null;
        }

        $limit = preg_match('/limit\s+1;?$/i', $query);
        $key = key($res[0]);
        if (isset($res[0][$key]) && $limit == true) {
            return $res[0][$key];
        }

        $newRes = Array();
        for ($i = 0; $i < $this->count; $i++) {
            $newRes[] = $res[$i][$key];
        }
        return $newRes;
    }

    /**
     * A method to perform select query
     * 
     * @param string $query   Contains a user-provided select query.
     * @param int|array $numRows Array to define SQL limit in format Array ($count, $offset)
     *
     * @return array Contains the returned rows from the query.
     */
    public function query($query, $numRows = null)
    {
        $this->_query = $query;
        $stmt = $this->_buildQuery($numRows);
        $stmt->execute();
        $this->_stmtError = $stmt->error;
        $this->_stmtErrno = $stmt->errno;
        $res = $this->_dynamicBindResults($stmt);
        $this->reset();

        return $res;
    }

    /**
     * This method allows you to specify multiple (method chaining optional) options for SQL queries.
     *
     * @uses $MySqliDb->setQueryOption('name');
     *
     * @param string|array $options The optons name of the query.
     * 
     * @throws Exception
     * @return MysqliDb
     */
    public function setQueryOption($options)
    {
        $allowedOptions = Array('ALL', 'DISTINCT', 'DISTINCTROW', 'HIGH_PRIORITY', 'STRAIGHT_JOIN', 'SQL_SMALL_RESULT',
            'SQL_BIG_RESULT', 'SQL_BUFFER_RESULT', 'SQL_CACHE', 'SQL_NO_CACHE', 'SQL_CALC_FOUND_ROWS',
            'LOW_PRIORITY', 'IGNORE', 'QUICK', 'MYSQLI_NESTJOIN', 'FOR UPDATE', 'LOCK IN SHARE MODE');

        if (!is_array($options)) {
            $options = Array($options);
        }

        foreach ($options as $option) {
            $option = strtoupper($option);
            if (!in_array($option, $allowedOptions)) {
                throw new Exception('Wrong query option: ' . $option);
            }

            if ($option == 'MYSQLI_NESTJOIN') {
                $this->_nestJoin = true;
            } elseif ($option == 'FOR UPDATE') {
                $this->_forUpdate = true;
            } elseif ($option == 'LOCK IN SHARE MODE') {
                $this->_lockInShareMode = true;
            } else {
                $this->_queryOptions[] = $option;
            }
        }

        return $this;
    }

    /**
     * Function to enable SQL_CALC_FOUND_ROWS in the get queries
     *
     * @return MysqliDb
     */
    public function withTotalCount()
    {
        $this->setQueryOption('SQL_CALC_FOUND_ROWS');
        return $this;
    }

    /**
     * A convenient SELECT * function.
     *
     * @param string  $tableName The name of the database table to work with.
     * @param int|array $numRows Array to define SQL limit in format Array ($count, $offset)
     *                               or only $count
     * @param string $columns Desired columns
     *
     * @return array Contains the returned rows from the select query.
     */
    public function get($tableName, $numRows = null, $columns = '*')
    {
        if (empty($columns)) {
            $columns = '*';
        }

        $column = is_array($columns) ? implode(', ', $columns) : $columns;

        if (strpos($tableName, '.') === false) {
            $this->_tableName = self::$prefix . $tableName;
        } else {
            $this->_tableName = $tableName;
        }

        $this->_query = 'SELECT ' . implode(' ', $this->_queryOptions) . ' ' .
            $column . " FROM " . $this->_tableName;
        $stmt = $this->_buildQuery($numRows);

        if ($this->isSubQuery) {
            return $this;
        }

        $stmt->execute();
        $this->_stmtError = $stmt->error;
        $this->_stmtErrno = $stmt->errno;
        $res = $this->_dynamicBindResults($stmt);
        $this->reset();

        return $res;
    }

    /**
     * A convenient SELECT * function to get one record.
     *
     * @param string  $tableName The name of the database table to work with.
     * @param string  $columns Desired columns
     * 
     * @return array Contains the returned rows from the select query.
     */
    public function getOne($tableName, $columns = '*')
    {
        $res = $this->get($tableName, 1, $columns);

        if ($res instanceof MysqliDb) {
            return $res;
        } elseif (is_array($res) && isset($res[0])) {
            return $res[0];
        } elseif ($res) {
            return $res;
        }

        return null;
    }

    /**
     * A convenient SELECT COLUMN function to get a single column value from one row
     *
     * @param string  $tableName The name of the database table to work with.
     * @param string  $column    The desired column 
     * @param int     $limit     Limit of rows to select. Use null for unlimited..1 by default
     *
     * @return mixed Contains the value of a returned column / array of values
     */
    public function getValue($tableName, $column, $limit = 1)
    {
        $res = $this->ArrayBuilder()->get($tableName, $limit, "{$column} AS retval");

        if (!$res) {
            return null;
        }

        if ($limit == 1) {
            if (isset($res[0]["retval"])) {
                return $res[0]["retval"];
            }
            return null;
        }

        $newRes = Array();
        for ($i = 0; $i < $this->count; $i++) {
            $newRes[] = $res[$i]['retval'];
        }
        return $newRes;
    }

    /**
     * Insert method to add new row
     *
     * @param string $tableName The name of the table.
     * @param array $insertData Data containing information for inserting into the DB.
     *
     * @return bool Boolean indicating whether the insert query was completed succesfully.
     */


     public function insert($tableName, $insertData)
     {
         $status = $this->_buildInsert($tableName, $insertData, 'INSERT');
     
          // if ($status) {
          //     $this->logOperation('INSERT', $tableName, $this->_query);
          // }


         // Create pseudo SQL for logging
         if (empty($this->_query)) {
             $fields = implode(", ", array_keys($insertData));
             $values = implode(", ", array_map(function ($v) {
                 return "'" . addslashes((string)($v ?? '')) . "'";
             }, $insertData));
             $this->_query = "INSERT INTO $tableName ($fields) VALUES ($values)";
         }
     
      
     
         return $status;
     }
     

    /**
     * Replace method to add new row
     *
     * @param string $tableName The name of the table.
     * @param array $insertData Data containing information for inserting into the DB.
     *
     * @return bool Boolean indicating whether the insert query was completed succesfully.
     */
    public function replace($tableName, $insertData)
    {
        return $this->_buildInsert($tableName, $insertData, 'REPLACE');
    }

    /**
     * A convenient function that returns TRUE if exists at least an element that
     * satisfy the where condition specified calling the "where" method before this one.
     *
     * @param string  $tableName The name of the database table to work with.
     *
     * @return array Contains the returned rows from the select query.
     */
    public function has($tableName)
    {
        $this->getOne($tableName, '1');
        return $this->count >= 1;
    }

    /**
     * Update query. Be sure to first call the "where" method.
     *
     * @param string $tableName The name of the database table to work with.
     * @param array  $tableData Array of data to update the desired row.
     * @param int    $numRows   Limit on the number of rows that can be updated.
     *
     * @return bool
     */
    public function update($tableName, $tableData, $numRows = null)
    {
        if ($this->isSubQuery) {
            return;
        }
    
        $this->_query = "UPDATE " . self::$prefix . $tableName;
    
        $stmt = $this->_buildQuery($numRows, $tableData);
        
        // ✅ Log the UPDATE operation before executing
        //$this->logOperation('UPDATE', $tableName, $this->_query);
        
        $status = $stmt->execute();
    
        $this->_stmtError = $stmt->error;
        $this->_stmtErrno = $stmt->errno;
        $this->count = $stmt->affected_rows;
    
        $this->reset();
        return $status;
    }
    
// this functiona insert all the CRUD operations logs

     protected function logOperation($operationType, $tableName, $query)
     {
         if (php_sapi_name() == 'cli') return;

       $user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;
     $branch_id = isset($_SESSION['branch']) ? intval($_SESSION['branch']) : null;
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
         $agent = $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN';

         $this->rawQuery("INSERT INTO eduportal_application_logs 
          (branch_id,user_id, operation_type, table_name, query_executed, ip_address, user_agent) 
         VALUES (?, ?, ?, ?, ?, ?, ?)", 
           [$branch_id,$user_id, $operationType, $tableName, $query, $ip, $agent]
         );
     }



    /**
     * Delete query. Call the "where" method first.
     *
     * @param string  $tableName The name of the database table to work with.
     * @param int|array $numRows Array to define SQL limit in format Array ($count, $offset)
     *                               or only $count
     *
     * @return bool Indicates success. 0 or 1.
     */
    public function delete($tableName, $numRows = null){

    if ($this->isSubQuery) {
        return;
    }

    $table = self::$prefix . $tableName;

    if (count($this->_join)) {
        $this->_query = "DELETE " . preg_replace('/.* (.*)/', '$1', $table) . " FROM " . $table;
    } else {
        $this->_query = "DELETE FROM " . $table;
    }

    $stmt = $this->_buildQuery($numRows);
    $stmt->execute();

    
    // if ($stmt->affected_rows > 0) {
    //     $this->logOperation('DELETE', $tableName, $this->_query); // move this up
    // } 
   
    $this->_stmtError = $stmt->error;
    $this->_stmtErrno = $stmt->errno;
    $this->reset();

    return ($stmt->affected_rows > 0);
}

    /**
     * This method allows you to specify multiple (method chaining optional) AND WHERE statements for SQL queries.
     *
     * @uses $MySqliDb->where('id', 7)->where('title', 'MyTitle');
     *
     * @param string $whereProp  The name of the database field.
     * @param mixed  $whereValue The value of the database field.
     * @param string $operator Comparison operator. Default is =
     * @param string $cond Condition of where statement (OR, AND)
     *
     * @return MysqliDb
     */
    public function where($whereProp, $whereValue = 'DBNULL', $operator = '=', $cond = 'AND')
    {
        // forkaround for an old operation api
        if (is_array($whereValue) && ($key = key($whereValue)) != "0") {
            $operator = $key;
            $whereValue = $whereValue[$key];
        }

        if (count($this->_where) == 0) {
            $cond = '';
        }

        $this->_where[] = array($cond, $whereProp, $operator, $whereValue);
        return $this;
    }

    /**
     * This function store update column's name and column name of the
     * autoincrement column
     *
     * @param array $updateColumns Variable with values
     * @param string $lastInsertId Variable value
     * 
     * @return MysqliDb
     */
    public function onDuplicate($updateColumns, $lastInsertId = null)
    {
        $this->_lastInsertId = $lastInsertId;
        $this->_updateColumns = $updateColumns;
        return $this;
    }

    /**
     * This method allows you to specify multiple (method chaining optional) OR WHERE statements for SQL queries.
     *
     * @uses $MySqliDb->orWhere('id', 7)->orWhere('title', 'MyTitle');
     *
     * @param string $whereProp  The name of the database field.
     * @param mixed  $whereValue The value of the database field.
     * @param string $operator Comparison operator. Default is =
     *
     * @return MysqliDb
     */
    public function orWhere($whereProp, $whereValue = 'DBNULL', $operator = '=')
    {
        return $this->where($whereProp, $whereValue, $operator, 'OR');
    }
    
    /**
     * This method allows you to specify multiple (method chaining optional) AND HAVING statements for SQL queries.
     *
     * @uses $MySqliDb->having('SUM(tags) > 10')
     *
     * @param string $havingProp  The name of the database field.
     * @param mixed  $havingValue The value of the database field.
     * @param string $operator Comparison operator. Default is =
     *
     * @return MysqliDb
     */

    public function having($havingProp, $havingValue = 'DBNULL', $operator = '=', $cond = 'AND')
    {
        // forkaround for an old operation api
        if (is_array($havingValue) && ($key = key($havingValue)) != "0") {
            $operator = $key;
            $havingValue = $havingValue[$key];
        }

        if (count($this->_having) == 0) {
            $cond = '';
        }

        $this->_having[] = array($cond, $havingProp, $operator, $havingValue);
        return $this;
    }

    /**
     * This method allows you to specify multiple (method chaining optional) OR HAVING statements for SQL queries.
     *
     * @uses $MySqliDb->orHaving('SUM(tags) > 10')
     *
     * @param string $havingProp  The name of the database field.
     * @param mixed  $havingValue The value of the database field.
     * @param string $operator Comparison operator. Default is =
     *
     * @return MysqliDb
     */
    public function orHaving($havingProp, $havingValue = null, $operator = null)
    {
        return $this->having($havingProp, $havingValue, $operator, 'OR');
    }

    /**
     * This method allows you to concatenate joins for the final SQL statement.
     *
     * @uses $MySqliDb->join('table1', 'field1 <> field2', 'LEFT')
     *
     * @param string $joinTable The name of the table.
     * @param string $joinCondition the condition.
     * @param string $joinType 'LEFT', 'INNER' etc.
     * 
     * @throws Exception
     * @return MysqliDb
     */
    public function join($joinTable, $joinCondition, $joinType = '')
    {
        $allowedTypes = array('LEFT', 'RIGHT', 'OUTER', 'INNER', 'LEFT OUTER', 'RIGHT OUTER');
        $joinType = strtoupper(trim($joinType));

        if ($joinType && !in_array($joinType, $allowedTypes)) {
            throw new Exception('Wrong JOIN type: ' . $joinType);
        }

        if (!is_object($joinTable)) {
            $joinTable = self::$prefix . $joinTable;
        }

        $this->_join[] = Array($joinType, $joinTable, $joinCondition);

        return $this;
    }

    /**
     * This method allows you to specify multiple (method chaining optional) ORDER BY statements for SQL queries.
     *
     * @uses $MySqliDb->orderBy('id', 'desc')->orderBy('name', 'desc');
     *
     * @param string $orderByField The name of the database field.
     * @param string $orderByDirection Order direction.
     * @param array $customFields Fieldset for ORDER BY FIELD() ordering
     * 
     * @throws Exception
     * @return MysqliDb
     */
    public function orderBy($orderByField, $orderbyDirection = "DESC", $customFields = null)
    {
        $allowedDirection = Array("ASC", "DESC");
        $orderbyDirection = strtoupper(trim($orderbyDirection));
        $orderByField = preg_replace("/[^-a-z0-9\.\(\),_`\*\'\"]+/i", '', $orderByField);

        // Add table prefix to orderByField if needed.
        //FIXME: We are adding prefix only if table is enclosed into `` to distinguish aliases
        // from table names
        $orderByField = preg_replace('/(\`)([`a-zA-Z0-9_]*\.)/', '\1' . self::$prefix . '\2', $orderByField);


        if (empty($orderbyDirection) || !in_array($orderbyDirection, $allowedDirection)) {
            throw new Exception('Wrong order direction: ' . $orderbyDirection);
        }

        if (is_array($customFields)) {
            foreach ($customFields as $key => $value) {
                $customFields[$key] = preg_replace("/[^-a-z0-9\.\(\),_` ]+/i", '', $value);
            }

            $orderByField = 'FIELD (' . $orderByField . ', "' . implode('","', $customFields) . '")';
        }

        $this->_orderBy[$orderByField] = $orderbyDirection;
        return $this;
    }

    /**
     * This method allows you to specify multiple (method chaining optional) GROUP BY statements for SQL queries.
     *
     * @uses $MySqliDb->groupBy('name');
     *
     * @param string $groupByField The name of the database field.
     *
     * @return MysqliDb
     */
    public function groupBy($groupByField)
    {
        $groupByField = preg_replace("/[^-a-z0-9\.\(\),_\*]+/i", '', $groupByField);

        $this->_groupBy[] = $groupByField;
        return $this;
    }

    /**
     * This methods returns the ID of the last inserted item
     *
     * @return int The last inserted item ID.
     */
    public function getInsertId()
    {
        return $this->mysqli()->insert_id;
    }

    /**
     * Escape harmful characters which might affect a query.
     *
     * @param string $str The string to escape.
     *
     * @return string The escaped string.
     */
    public function escape($str)
    {
        return $this->mysqli()->real_escape_string($str);
    }

    /**
     * Method to call mysqli->ping() to keep unused connections open on
     * long-running scripts, or to reconnect timed out connections (if php.ini has
     * global mysqli.reconnect set to true). Can't do this directly using object
     * since _mysqli is protected.
     *
     * @return bool True if connection is up
     */
    public function ping()
    {
        return $this->mysqli()->ping();
    }

    /**
     * This method is needed for prepared statements. They require
     * the data type of the field to be bound with "i" s", etc.
     * This function takes the input, determines what type it is,
     * and then updates the param_type.
     *
     * @param mixed $item Input to determine the type.
     *
     * @return string The joined parameter types.
     */
    protected function _determineType($item)
    {
        switch (gettype($item)) {
            case 'NULL':
            case 'string':
                return 's';
                break;

            case 'boolean':
            case 'integer':
                return 'i';
                break;

            case 'blob':
                return 'b';
                break;

            case 'double':
                return 'd';
                break;
        }
        return '';
    }

    /**
     * Helper function to add variables into bind parameters array
     *
     * @param string Variable value
     */
    protected function _bindParam($value)
    {
        $this->_bindParams[0] .= $this->_determineType($value);
        array_push($this->_bindParams, $value);
    }

    /**
     * Helper function to add variables into bind parameters array in bulk
     *
     * @param array $values Variable with values
     */
    protected function _bindParams($values)
    {
        foreach ($values as $value) {
            $this->_bindParam($value);
        }
    }

    /**
     * Helper function to add variables into bind parameters array and will return
     * its SQL part of the query according to operator in ' $operator ?' or
     * ' $operator ($subquery) ' formats
     *
     * @param string $operator
     * @param mixed $value Variable with values
     * 
     * @return string
     */
    protected function _buildPair($operator, $value)
    {
        if (!is_object($value)) {
            $this->_bindParam($value);
            return ' ' . $operator . ' ? ';
        }

        $subQuery = $value->getSubQuery();
        $this->_bindParams($subQuery['params']);

        return " " . $operator . " (" . $subQuery['query'] . ") " . $subQuery['alias'];
    }

    /**
     * Internal function to build and execute INSERT/REPLACE calls
     *
     * @param string $tableName The name of the table.
     * @param array $insertData Data containing information for inserting into the DB.
     * @param string $operation Type of operation (INSERT, REPLACE)
     *
     * @return bool Boolean indicating whether the insert query was completed succesfully.
     */
    private function _buildInsert($tableName, $insertData, $operation)
    {
        if ($this->isSubQuery) {
            return;
        }

        $this->_query = $operation . " " . implode(' ', $this->_queryOptions) . " INTO " . self::$prefix . $tableName;
        $stmt = $this->_buildQuery(null, $insertData);
        $status = $stmt->execute();
        $this->_stmtError = $stmt->error;
        $this->_stmtErrno = $stmt->errno;
        $haveOnDuplicate = !empty ($this->_updateColumns);
        $this->reset();
        $this->count = $stmt->affected_rows;

        if ($stmt->affected_rows < 1) {
            // in case of onDuplicate() usage, if no rows were inserted
            if ($status && $haveOnDuplicate) {
                return true;
            }
            return false;
        }

        if ($stmt->insert_id > 0) {
            return $stmt->insert_id;
        }

        return true;
    }

    /**
     * Abstraction method that will compile the WHERE statement,
     * any passed update data, and the desired rows.
     * It then builds the SQL query.
     *
     * @param int|array $numRows Array to define SQL limit in format Array ($count, $offset)
     *                               or only $count
     * @param array $tableData Should contain an array of data for updating the database.
     *
     * @return mysqli_stmt Returns the $stmt object.
     */


    protected function _buildQuery($numRows = null, $tableData = null)
    {
        $this->_buildJoin();
        $this->_buildInsertQuery($tableData);
        $this->_buildCondition('WHERE', $this->_where);
        $this->_buildGroupBy();
        $this->_buildCondition('HAVING', $this->_having);
        $this->_buildOrderBy();
        $this->_buildLimit($numRows);
        $this->_buildOnDuplicate($tableData);
        
        if ($this->_forUpdate) {
            $this->_query .= ' FOR UPDATE';
        }
        if ($this->_lockInShareMode) {
            $this->_query .= ' LOCK IN SHARE MODE';
        }

        $this->_lastQuery = $this->replacePlaceHolders($this->_query, $this->_bindParams);

        if ($this->isSubQuery) {
            return;
        }

        // Prepare query
        $stmt = $this->_prepareQuery();

        // Bind parameters to statement if any
        if (count($this->_bindParams) > 1) {
            call_user_func_array(array($stmt, 'bind_param'), $this->refValues($this->_bindParams));
        }

        return $stmt;
    }

    /**
     * This helper method takes care of prepared statements' "bind_result method
     * , when the number of variables to pass is unknown.
     *
     * @param mysqli_stmt $stmt Equal to the prepared statement object.
     *
     * @return array The results of the SQL fetch.
     */
    protected function _dynamicBindResults(mysqli_stmt $stmt)
    {
        $parameters = array();
        $results = array();
        /**
         * @see http://php.net/manual/en/mysqli-result.fetch-fields.php
         */
        $mysqlLongType = 252;
        $shouldStoreResult = false;

        $meta = $stmt->result_metadata();

        // if $meta is false yet sqlstate is true, there's no sql error but the query is
        // most likely an update/insert/delete which doesn't produce any results
        if (!$meta && $stmt->sqlstate)
            return array();

        $row = array();
        while ($field = $meta->fetch_field()) {
            if ($field->type == $mysqlLongType) {
                $shouldStoreResult = true;
            }

            if ($this->_nestJoin && $field->table != $this->_tableName) {
                $field->table = substr($field->table, strlen(self::$prefix));
                $row[$field->table][$field->name] = null;
                $parameters[] = & $row[$field->table][$field->name];
            } else {
                $row[$field->name] = null;
                $parameters[] = & $row[$field->name];
            }
        }

        // avoid out of memory bug in php 5.2 and 5.3. Mysqli allocates lot of memory for long*
        // and blob* types. So to avoid out of memory issues store_result is used
        // https://github.com/joshcam/PHP-MySQLi-Database-Class/pull/119
        if ($shouldStoreResult) {
            $stmt->store_result();
        }

        call_user_func_array(array($stmt, 'bind_result'), $parameters);

        $this->totalCount = 0;
        $this->count = 0;

        while ($stmt->fetch()) {
            if ($this->returnType == 'object') {
                $result = new stdClass ();
                foreach ($row as $key => $val) {
                    if (is_array($val)) {
                        $result->$key = new stdClass ();
                        foreach ($val as $k => $v) {
                            $result->$key->$k = $v;
                        }
                    } else {
                        $result->$key = $val;
                    }
                }
            } else {
                $result = array();
                foreach ($row as $key => $val) {
                    if (is_array($val)) {
                        foreach ($val as $k => $v) {
                            $result[$key][$k] = $v;
                        }
                    } else {
                        $result[$key] = $val;
                    }
                }
            }
            $this->count++;
            if ($this->_mapKey) {
                $results[$row[$this->_mapKey]] = count($row) > 2 ? $result : end($result);
            } else {
                array_push($results, $result);
            }
        }

        if ($shouldStoreResult) {
            $stmt->free_result();
        }

        $stmt->close();

        // stored procedures sometimes can return more then 1 resultset
        if ($this->mysqli()->more_results()) {
            $this->mysqli()->next_result();
        }

        if (in_array('SQL_CALC_FOUND_ROWS', $this->_queryOptions)) {
            $stmt = $this->mysqli()->query('SELECT FOUND_ROWS()');
            $totalCount = $stmt->fetch_row();
            $this->totalCount = $totalCount[0];
        }

        if ($this->returnType == 'json') {
            return json_encode($results);
        }

        return $results;
    }

    /**
     * Abstraction method that will build an JOIN part of the query
     * 
     * @return void
     */
    protected function _buildJoin()
    {
        if (empty($this->_join)) {
            return;
        }

        foreach ($this->_join as $data) {
            list ($joinType, $joinTable, $joinCondition) = $data;

            if (is_object($joinTable)) {
                $joinStr = $this->_buildPair("", $joinTable);
            } else {
                $joinStr = $joinTable;
            }

            $this->_query .= " " . $joinType . " JOIN " . $joinStr . 
                (false !== stripos($joinCondition, 'using') ? " " : " on ")
                . $joinCondition;
        }
    }

    /**
     * Insert/Update query helper
     * 
     * @param array $tableData
     * @param array $tableColumns
     * @param bool $isInsert INSERT operation flag
     * 
     * @throws Exception
     */
    public function _buildDataPairs($tableData, $tableColumns, $isInsert)
    {
        foreach ($tableColumns as $column) {
            $value = $tableData[$column];

            if (!$isInsert) {
                if(strpos($column,'.')===false) {
                    $this->_query .= "`" . $column . "` = ";
                } else {
                    $this->_query .= str_replace('.','.`',$column) . "` = ";
                }
            }

            // Subquery value
            if ($value instanceof MysqliDb) {
                $this->_query .= $this->_buildPair("", $value) . ", ";
                continue;
            }

            // Simple value
            if (!is_array($value)) {
                $this->_bindParam($value);
                $this->_query .= '?, ';
                continue;
            }

            // Function value
            $key = key($value);
            $val = $value[$key];
            switch ($key) {
                case '[I]':
                    $this->_query .= $column . $val . ", ";
                    break;
                case '[F]':
                    $this->_query .= $val[0] . ", ";
                    if (!empty($val[1])) {
                        $this->_bindParams($val[1]);
                    }
                    break;
                case '[N]':
                    if ($val == null) {
                        $this->_query .= "!" . $column . ", ";
                    } else {
                        $this->_query .= "!" . $val . ", ";
                    }
                    break;
                default:
                    throw new Exception("Wrong operation");
            }
        }
        $this->_query = rtrim($this->_query, ', ');
    }

    /**
     * Helper function to add variables into the query statement
     *
     * @param array $tableData Variable with values
     */
    protected function _buildOnDuplicate($tableData)
    {
        if (is_array($this->_updateColumns) && !empty($this->_updateColumns)) {
            $this->_query .= " ON DUPLICATE KEY UPDATE ";
            if ($this->_lastInsertId) {
                $this->_query .= $this->_lastInsertId . "=LAST_INSERT_ID (" . $this->_lastInsertId . "), ";
            }

            foreach ($this->_updateColumns as $key => $val) {
                // skip all params without a value
                if (is_numeric($key)) {
                    $this->_updateColumns[$val] = '';
                    unset($this->_updateColumns[$key]);
                } else {
                    $tableData[$key] = $val;
                }
            }
            $this->_buildDataPairs($tableData, array_keys($this->_updateColumns), false);
        }
    }

    /**
     * Abstraction method that will build an INSERT or UPDATE part of the query
     * 
     * @param array $tableData
     */
    protected function _buildInsertQuery($tableData)
{
    if (!is_array($tableData)) {
        return;
    }

    $isInsert = preg_match('/^[INSERT|REPLACE]/', $this->_query);
    $dataColumns = array_keys($tableData);
    if ($isInsert) {
        if (isset($dataColumns[0])) {
            // Swap the parameters here
            $this->_query .= ' (`' . implode('`, `', $dataColumns) . '`) ';
        }
        $this->_query .= ' VALUES (';
    } else {
        $this->_query .= " SET ";
    }

    $this->_buildDataPairs($tableData, $dataColumns, $isInsert);

    if ($isInsert) {
        $this->_query .= ')';
    }
}

    /**
     * Abstraction method that will build the part of the WHERE conditions
     * 
     * @param string $operator
     * @param array $conditions
     */
    protected function _buildCondition($operator, &$conditions)
    {
        if (empty($conditions)) {
            return;
        }

        //Prepare the where portion of the query
        $this->_query .= ' ' . $operator;

        foreach ($conditions as $cond) {
            list ($concat, $varName, $operator, $val) = $cond;
            $this->_query .= " " . $concat . " " . $varName;

            switch (strtolower($operator)) {
                case 'not in':
                case 'in':
                    $comparison = ' ' . $operator . ' (';
                    if (is_object($val)) {
                        $comparison .= $this->_buildPair("", $val);
                    } else {
                        foreach ($val as $v) {
                            $comparison .= ' ?,';
                            $this->_bindParam($v);
                        }
                    }
                    $this->_query .= rtrim($comparison, ',') . ' ) ';
                    break;
                case 'not between':
                case 'between':
                    $this->_query .= " $operator ? AND ? ";
                    $this->_bindParams($val);
                    break;
                case 'not exists':
                case 'exists':
                    $this->_query.= $operator . $this->_buildPair("", $val);
                    break;
                default:
                    if (is_array($val)) {
                        $this->_bindParams($val);
                    } elseif ($val === null) {
                        $this->_query .= ' ' . $operator . " NULL";
                    } elseif ($val != 'DBNULL' || $val == '0') {
                        $this->_query .= $this->_buildPair($operator, $val);
                    }
            }
        }
    }

    /**
     * Abstraction method that will build the GROUP BY part of the WHERE statement
     *
     * @return void
     */
    protected function _buildGroupBy()
    {
        if (empty($this->_groupBy)) {
            return;
        }

        $this->_query .= " GROUP BY ";

        foreach ($this->_groupBy as $key => $value) {
            $this->_query .= $value . ", ";
        }

        $this->_query = rtrim($this->_query, ', ') . " ";
    }

    /**
     * Abstraction method that will build the LIMIT part of the WHERE statement
     *
     * @return void
     */
    protected function _buildOrderBy()
    {
        if (empty($this->_orderBy)) {
            return;
        }

        $this->_query .= " ORDER BY ";
        foreach ($this->_orderBy as $prop => $value) {
            if (strtolower(str_replace(" ", "", $prop)) == 'rand()') {
                $this->_query .= "rand(), ";
            } else {
                $this->_query .= $prop . " " . $value . ", ";
            }
        }

        $this->_query = rtrim($this->_query, ', ') . " ";
    }

    /**
     * Abstraction method that will build the LIMIT part of the WHERE statement
     *
     * @param int|array $numRows Array to define SQL limit in format Array ($count, $offset)
     *                               or only $count
     * 
     * @return void
     */
    protected function _buildLimit($numRows)
    {
        if (!isset($numRows)) {
            return;
        }

        if (is_array($numRows)) {
            $this->_query .= ' LIMIT ' . (int) $numRows[0] . ', ' . (int) $numRows[1];
        } else {
            $this->_query .= ' LIMIT ' . (int) $numRows;
        }
    }

    /**
     * Method attempts to prepare the SQL query
     * and throws an error if there was a problem.
     *
     * @return mysqli_stmt
     */
    protected function _prepareQuery()
    {
        if (!$stmt = $this->mysqli()->prepare($this->_query)) {
            $msg = $this->mysqli()->error . " query: " . $this->_query;
            $this->reset();
            throw new Exception($msg);
        }

        if ($this->traceEnabled) {
            $this->traceStartQ = microtime(true);
        }

        return $stmt;
    }

    /**
     * Close connection
     * 
     * @return void
     */
    public function __destruct()
    {
        if ($this->isSubQuery) {
            return;
        }

        if ($this->_mysqli instanceof mysqli) {
            @ $this->_mysqli->close();
            $this->_mysqli = null;
        }
    }

    /**
     * Referenced data array is required by mysqli since PHP 5.3+
     * 
     * @param array $arr
     *
     * @return array
     */
    protected function refValues(array &$arr)
    {
        //Reference in the function arguments are required for HHVM to work
        //https://github.com/facebook/hhvm/issues/5155
        //Referenced data array is required by mysqli since PHP 5.3+
        if (strnatcmp(phpversion(), '5.3') >= 0) {
            $refs = array();
            foreach ($arr as $key => $value) {
                $refs[$key] = & $arr[$key];
            }
            return $refs;
        }
        return $arr;
    }

    /**
     * Function to replace ? with variables from bind variable
     * 
     * @param string $str
     * @param array $vals
     *
     * @return string
     */
    protected function replacePlaceHolders($str, $vals)
    {
        $i = 1;
        $newStr = "";

        if (empty($vals)) {
            return $str;
        }

        while ($pos = strpos($str, "?")) {
            $val = $vals[$i++];
            if (is_object($val)) {
                $val = '[object]';
            }
            if ($val === null) {
                $val = 'NULL';
            }
            $newStr .= substr($str, 0, $pos) . "'" . $val . "'";
            $str = substr($str, $pos + 1);
        }
        $newStr .= $str;
        return $newStr;
    }

    /**
     * Method returns last executed query
     *
     * @return string
     */
    public function getLastQuery()
    {
        return $this->_lastQuery;
    }

    /**
     * Method returns mysql error
     *
     * @return string
     */
    public function getLastError()
    {
        if (!$this->_mysqli) {
            return "mysqli is null";
        }
        return trim($this->_stmtError . " " . $this->mysqli()->error);
    }

    /**
     * Method returns mysql error code
     * @return int
     */
    public function getLastErrno () {
        return $this->_stmtErrno;
    }

    /**
     * Mostly internal method to get query and its params out of subquery object
     * after get() and getAll()
     *
     * @return array
     */
    public function getSubQuery()
    {
        if (!$this->isSubQuery) {
            return null;
        }

        array_shift($this->_bindParams);
        $val = Array('query' => $this->_query,
            'params' => $this->_bindParams,
            'alias' => $this->host
        );
        $this->reset();
        return $val;
    }
        
    /* Helper functions */

    /**
     * Method returns generated interval function as a string
     *
     * @param string $diff interval in the formats:
     *        "1", "-1d" or "- 1 day" -- For interval - 1 day
     *        Supported intervals [s]econd, [m]inute, [h]hour, [d]day, [M]onth, [Y]ear
     *        Default null;
     * @param string $func Initial date
     *
     * @return string
     */
    public function interval($diff, $func = "NOW()")
    {
        $types = Array("s" => "second", "m" => "minute", "h" => "hour", "d" => "day", "M" => "month", "Y" => "year");
        $incr = '+';
        $items = '';
        $type = 'd';

        if ($diff && preg_match('/([+-]?) ?([0-9]+) ?([a-zA-Z]?)/', $diff, $matches)) {
            if (!empty($matches[1])) {
                $incr = $matches[1];
            }

            if (!empty($matches[2])) {
                $items = $matches[2];
            }

            if (!empty($matches[3])) {
                $type = $matches[3];
            }

            if (!in_array($type, array_keys($types))) {
                throw new Exception("invalid interval type in '{$diff}'");
            }

            $func .= " " . $incr . " interval " . $items . " " . $types[$type] . " ";
        }
        return $func;
    }

    /**
     * Method returns generated interval function as an insert/update function
     *
     * @param string $diff interval in the formats:
     *        "1", "-1d" or "- 1 day" -- For interval - 1 day
     *        Supported intervals [s]econd, [m]inute, [h]hour, [d]day, [M]onth, [Y]ear
     *        Default null;
     * @param string $func Initial date
     *
     * @return array
     */
    public function now($diff = null, $func = "NOW()")
    {
        return array("[F]" => Array($this->interval($diff, $func)));
    }

    /**
     * Method generates incremental function call
     * 
     * @param int $num increment by int or float. 1 by default
     * 
     * @throws Exception
     * @return array
     */
    public function inc($num = 1)
    {
        if (!is_numeric($num)) {
            throw new Exception('Argument supplied to inc must be a number');
        }
        return array("[I]" => "+" . $num);
    }

    /**
     * Method generates decrimental function call
     * 
     * @param int $num increment by int or float. 1 by default
     * 
     * @return array
     */
    public function dec($num = 1)
    {
        if (!is_numeric($num)) {
            throw new Exception('Argument supplied to dec must be a number');
        }
        return array("[I]" => "-" . $num);
    }

    /**
     * Method generates change boolean function call
     * 
     * @param string $col column name. null by default
     * 
     * @return array
     */
    public function not($col = null)
    {
        return array("[N]" => (string) $col);
    }

    /**
     * Method generates user defined function call
     * 
     * @param string $expr user function body
     * @param array $bindParams
     * 
     * @return array
     */
    public function func($expr, $bindParams = null)
    {
        return array("[F]" => array($expr, $bindParams));
    }

    /**
     * Method creates new mysqlidb object for a subquery generation
     * 
     * @param string $subQueryAlias
     * 
     * @return MysqliDb
     */
    public static function subQuery($subQueryAlias = "")
    {
        return new self(array('host' => $subQueryAlias, 'isSubQuery' => true));
    }

    /**
     * Method returns a copy of a mysqlidb subquery object
     *
     * @return MysqliDb new mysqlidb object
     */
    public function copy()
    {
        $copy = unserialize(serialize($this));
        $copy->_mysqli = null;
        return $copy;
    }

    /**
     * Begin a transaction
     *
     * @uses mysqli->autocommit(false)
     * @uses register_shutdown_function(array($this, "_transaction_shutdown_check"))
     */
    public function startTransaction()
    {
        $this->mysqli()->autocommit(false);
        $this->_transaction_in_progress = true;
        register_shutdown_function(array($this, "_transaction_status_check"));
    }

    /**
     * Transaction commit
     *
     * @uses mysqli->commit();
     * @uses mysqli->autocommit(true);
     */
    public function commit()
    {
        $result = $this->mysqli()->commit();
        $this->_transaction_in_progress = false;
        $this->mysqli()->autocommit(true);
        return $result;
    }

    /**
     * Transaction rollback function
     *
     * @uses mysqli->rollback();
     * @uses mysqli->autocommit(true);
     */
    public function rollback()
    {
        $result = $this->mysqli()->rollback();
        $this->_transaction_in_progress = false;
        $this->mysqli()->autocommit(true);
        return $result;
    }

    /**
     * Shutdown handler to rollback uncommited operations in order to keep
     * atomic operations sane.
     *
     * @uses mysqli->rollback();
     */
    public function _transaction_status_check()
    {
        if (!$this->_transaction_in_progress) {
            return;
        }
        $this->rollback();
    }

    /**
     * Query exection time tracking switch
     *
     * @param bool $enabled Enable execution time tracking
     * @param string $stripPrefix Prefix to strip from the path in exec log
     * 
     * @return MysqliDb
     */
    public function setTrace($enabled, $stripPrefix = null)
    {
        $this->traceEnabled = $enabled;
        $this->traceStripPrefix = $stripPrefix;
        return $this;
    }

    /**
     * Get where and what function was called for query stored in MysqliDB->trace
     *
     * @return string with information
     */
    private function _traceGetCaller()
    {
        $dd = debug_backtrace();
        $caller = next($dd);
        while (isset($caller) && $caller["file"] == __FILE__) {
            $caller = next($dd);
        }

        return __CLASS__ . "->" . $caller["function"] . "() >>  file \"" .
            str_replace($this->traceStripPrefix, '', $caller["file"]) . "\" line #" . $caller["line"] . " ";
    }

    /**
     * Method to check if needed table is created
     *
     * @param array $tables Table name or an Array of table names to check
     *
     * @return bool True if table exists
     */
    public function tableExists($tables)
    {
        $tables = !is_array($tables) ? Array($tables) : $tables;
        $count = count($tables);
        if ($count == 0) {
            return false;
        }

        foreach ($tables as $i => $value)
            $tables[$i] = self::$prefix . $value;
        $this->where('table_schema', $this->db);
        $this->where('table_name', $tables, 'in');
        $this->get('information_schema.tables', $count);
        return $this->count == $count;
    }

    /**
     * Return result as an associative array with $idField field value used as a record key
     * 
     * Array Returns an array($k => $v) if get(.."param1, param2"), array ($k => array ($v, $v)) otherwise
     * 
     * @param string $idField field name to use for a mapped element key
     *
     * @return MysqliDb
     */
    public function map($idField)
    {
        $this->_mapKey = $idField;
        return $this;
    }


public function checkdeliverycharge($Searchaddress,$resid)
{
$this->where("postcode", $Searchaddress);              
$this->where("RestaurantID", $resid);
$this->orderBy("id","desc");
$DeliveryChargeVaue = $this->get("tbl_ShopDelievryArea");

$this->where("status", 0);             
$this->where("id", $resid);             
$this->orderBy("id","ASC");
$View = $this->get("tbl_warehouse");

if($DeliveryChargeVaue[0]['delivery_charge']!='')
 {
 $DeliveryCharg=$DeliveryChargeVaue[0]['delivery_charge'];
 return $DeliveryCharg;
 }
 else
 {
  $DeliveryCharg=$View[0]['Home_delivery_Charge'];
  return $DeliveryCharg;
 } 
 
}



public function checkminimumcharge($Searchaddress,$resid)
{
$this->where("postcode", $Searchaddress);              
$this->where("RestaurantID", $resid);
$this->orderBy("id","desc");
$DeliveryChargeVaue = $this->get("tbl_ShopDelievryArea");

$this->where("status", 0);             
$this->where("id", $resid);             
$this->orderBy("id","ASC");
$View = $this->get("tbl_warehouse");

if($DeliveryChargeVaue[0]['minimum_order']!='')
 {
 $MinimumOrderCharg=$DeliveryChargeVaue[0]['minimum_order'];
 return $MinimumOrderCharg;
 }
 else
 {
  $MinimumOrderCharg=$View[0]['minimum_home_order'];
  return $MinimumOrderCharg;
 } 
 
}




public function offerDiscountAppliedPrice($rest_id,$SubTotal)
{
$this->where("orderaboveamount", 1);
$this->where("status", 0);
$this->where("promotionDiscountPrice >= $SubTotal");               
$this->where("promotionShop", $resid);
$this->orderBy("promotionDiscountPrice","DESC");
$plo11 = $this->get("tbl_Shoppromotion");
$upto_Data=count($plo11);

$this->where("orderaboveamount", 2);
$this->where("status", 0);
$this->where("promotionDiscountPrice <= $SubTotal");               
$this->where("promotionShop", $resid);
$this->orderBy("promotionDiscountPrice","ASC");
$plo11 = $this->get("tbl_Shoppromotion");
$above_Data=count($plo11);

if($above_Data>0){
foreach($plo11 as $plttop2){
if(is_array($plttop2)){
 $offerDisc2=$plttop2['promotionDiscountPrice'];
            if($plttop2['promotionDiscountType']=='p'){
             $discountValue=$plttop2['RestaurantOfferPrice'];
            }
            else{
             $discountValue=$SubTotal*$plttop2['promotionDiscountPrice']/100;   
            }
             $retsurValue=$discountValue.'_'.$offerDisc2.'_'.$plttop2['promotionDiscountType'];
            return $retsurValue;
        }
        }
}       
        if($upto_Data>0){
        
        foreach($plo11 as $plttop1){
if(is_array($plttop1)){
         $offerDisc1=$plttop1['promotionDiscountPrice'];
            if($plttop1['promotionDiscountType']=='p'){
             $discountValue=$plttop1['promotionDiscountPrice'];
            }
            else{
             $discountValue=$SubTotal*$plttop1['promotionDiscountPrice']/100;   
            }
             $retsurValue=$discountValue.'_'.$offerDisc1.'_'.$plttop2['promotionDiscountType'];
            return $retsurValue;
        }
        }
}
    
    }

    /**
     * Pagination wraper to get()
     *
     * @access public
     * @param string  $table The name of the database table to work with
     * @param int $page Page number
     * @param array|string $fields Array or coma separated list of fields to fetch
     * @return array
     */
    public function paginate ($table, $page, $fields = null) {
        $offset = $this->pageLimit * ($page - 1);
        $res = $this->withTotalCount()->get ($table, Array ($offset, $this->pageLimit), $fields);
        $this->totalPages = ceil($this->totalCount / $this->pageLimit);
        return $res;
    }
    
    
public function clean($dirty) {
    if (!is_array($dirty)) {
        // Remove unwanted characters
        $dirty = preg_replace("/[\'\")(;|`,<>]/", "", $dirty);
        // Escape the string for SQL injection prevention
        $clean = mysqli_real_escape_string($this->db, trim($dirty));
        return $clean;
    }
    
    // If the input is an array, clean each element
    $clean = array();
    foreach ($dirty as $p => $data) {
        $data = preg_replace("/[\'\")(;|`,<>]/", "", $data);
        $data = mysqli_real_escape_string($this->db, trim($data));
        $clean[$p] = $data;
    }
    
    return $clean;
}

    
    public function secure($data){
        return sha1(md5(sha1(md5(sha1($data)))));
    }
    
    public function checkEmail($email) {
    $reg = "/^([A-Za-z0-9_\-\.])+\@([A-Za-z0-9_\-\.])+\.([A-Za-z]{2,4})$/";
    if (!$reg.test($email)) 
    return false;
    else 
    return true;
}

public function is_valid_url($url){
    $p1 ='/(http|https|ftp):\/\/[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(\/.*)?$/i';
    return preg_match($p1, $url); 
}



public function is_valid_email($email){
    if (filter_var($email, FILTER_VALIDATE_EMAIL))
        return true;
    else
        return false;
}


public function is_valid_ip($ip){
    if (filter_var($ip, FILTER_VALIDATE_IP))
        return true;
    else
        return false;
}   


public function anti_injection($input){
$clean=strip_tags(addslashes(trim($input)));
$clean=str_replace('"','\"',$clean);
$clean=str_replace(';','\;',$clean);
$clean=str_replace('--','\--',$clean);
$clean=str_replace('+','\+',$clean);
$clean=str_replace('(','\(',$clean);
$clean=str_replace(')','\)',$clean);
$clean=str_replace('=','\=',$clean);
$clean=str_replace('>','\>',$clean);
$clean=str_replace('<','\<',$clean);
return $clean;
}

public function mysqlCleaner($data)
{
    $data= $this->escape($data);
    $data= stripslashes($data);
    return $data;
    //or in one line code 
    //return(stripslashes(mysql_real_escape_string($data)));
}


public function cleanScriptInput($input) {
 
  $search = array(
    '@<script[^>]*?>.*?</script>@si',   // Strip out javascript
    '@<[\/\!]*?[^<>]*?>@si',            // Strip out HTML tags
    '@<style[^>]*?>.*?</style>@siU',    // Strip style tags properly
    '@<![\s\S]*?--[ \t\n\r]*>@'         // Strip multi-line comments
  );
 
    $output = preg_replace($search, '', $input);
    return $output;
  }
 
public function xss_cleaner($input_str) {
    $return_str = str_replace( array('<',';','|','&','>',"'",'"',')','('), array('&lt;','&#58;','&#124;','&#38;','&gt;','&apos;','&#x22;','&#x29;','&#x28;'), $input_str );
    $return_str = str_ireplace( '%3Cscript', '', $return_str );
    return $return_str;
}

public function xssafe($data,$encoding='UTF-8')
{
   return htmlspecialchars($data,ENT_QUOTES | ENT_HTML401,$encoding);
}

  
public function get_phpexpert_ip() {
    $ipaddress = '';
    if ($_SERVER['HTTP_CLIENT_IP'])
        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    else if($_SERVER['HTTP_X_FORWARDED_FOR'])
        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    else if($_SERVER['HTTP_X_FORWARDED'])
        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    else if($_SERVER['HTTP_FORWARDED_FOR'])
        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    else if($_SERVER['HTTP_FORWARDED'])
        $ipaddress = $_SERVER['HTTP_FORWARDED'];
    else if($_SERVER['REMOTE_ADDR'])
        $ipaddress = $_SERVER['REMOTE_ADDR'];
    else
        $ipaddress = 'UNKNOWN';
    return $ipaddress;
}

public function urlParser($url)
    {
    $query  = explode('&', $url);
    $params = array();
    foreach( $query as $param )
    {
        if($param!=''){
      list($name, $value) = explode('=', $param);
      $params[urldecode($name)][] = urldecode($value);
    }
    }
    return $params;
    }


public function shorturl($url){
//$longurl= "http://www.google.com/search?q=symfony+project&ie=utf-8&oe=utf-8&aq=t&rls=org.mozilla:tr:official&client=firefox-a";
//$shorturl = shorturl($longurl);
//echo "<a href=\"$longurl\">$shorturl</a>";

    $length = strlen($url);
    if($length > 45){
        $length = $length - 30;
        $first = substr($url, 0, -$length);
        $last = substr($url, -15);
        $new = $first."[ ... ]".$last;
        return $new;
    }else{
        return $url;
    }
}


function encryptIt($data) {
    $encryption_key = 'qJB0rGtIn5UB1xG03efyCp'; // Replace with your secret key
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('AES-256-CBC')); // Generate a secure IV
    $encrypted = openssl_encrypt($data, 'AES-256-CBC', $encryption_key, 0, $iv);
    return base64_encode($encrypted . '::' . $iv); // Combine encrypted data and IV
}

function decryptIt($data) {
    $encryption_key = 'qJB0rGtIn5UB1xG03efyCp'; // Same secret key as used in encryption
    list($encrypted_data, $iv) = explode('::', base64_decode($data), 2); // Separate encrypted data and IV
    return openssl_decrypt($encrypted_data, 'AES-256-CBC', $encryption_key, 0, $iv);
}






public function curPageURL() {
 $pageURL = 'http';
 if ($_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
 $pageURL .= "://";
 if ($_SERVER["SERVER_PORT"] != "80") {
  $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
 } else {
  $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
 }
 return $pageURL;
}


public function relativedate($secs) {
//echo relativedate(60); // 1 minute
        $second = 1;
        $minute = 60;
        $hour = 60*60;
        $day = 60*60*24;
        $week = 60*60*24*7;
        $month = 60*60*24*7*30;
        $year = 60*60*24*7*30*365;
 
        if ($secs <= 0) { $output = "now";
        }elseif ($secs > $second && $secs < $minute) { $output = round($secs/$second)." second";
        }elseif ($secs >= $minute && $secs < $hour) { $output = round($secs/$minute)." minute";
        }elseif ($secs >= $hour && $secs < $day) { $output = round($secs/$hour)." hour";
        }elseif ($secs >= $day && $secs < $week) { $output = round($secs/$day)." day";
        }elseif ($secs >= $week && $secs < $month) { $output = round($secs/$week)." week";
        }elseif ($secs >= $month && $secs < $year) { $output = round($secs/$month)." month";
        }elseif ($secs >= $year && $secs < $year*10) { $output = round($secs/$year)." year";
        }else{ $output = " more than a decade ago"; }
 
        if ($output <> "now"){
            $output = (substr($output,0,2)<>"1 ") ? $output."s" : $output;
        }
        return $output;
    }


public function createRandomKey($amount){
    $keyset  = "abcdefghijklmABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    $randkey = "";
    for ($i=0; $i<$amount; $i++)
        $randkey .= substr($keyset, rand(0, strlen($keyset)-1), 1);
    return $randkey;    
}

public function readFacebookLikes($url) {
    $query = "select total_count from link_stat WHERE url ='" . $url ."'";
    $s = file_get_contents("https://api.facebook.com/method/fql.query?query=".
             urlencode($query)."&format=json");
    preg_match("#(\"total_count\"):([0-9]*)#",$s,$ar);
    if(isset($ar[2])) return $ar[2]; else return null;
}


public function getGoogleImg($k) {
    $url = "http://images.google.it/images?as_q=##query##&hl=it&imgtbs=z&btnG=Cerca+con+Google&as_epq=&as_oq=&as_eq=&imgtype=&imgsz=m&imgw=&imgh=&imgar=&as_filetype=&imgc=&as_sitesearch=&as_rights=&safe=images&as_st=y";
    $web_page = file_get_contents( str_replace("##query##",urlencode($k), $url ));
 
    $tieni = stristr($web_page,"dyn.setResults(");
    $tieni = str_replace( "dyn.setResults(","", str_replace(stristr($tieni,");"),"",$tieni) );
    $tieni = str_replace("[]","",$tieni);
    $m = preg_split("/[\[\]]/",$tieni);
    $x = array();
    for($i=0;$i<count($m);$i++) {
        $m[$i] = str_replace("/imgres?imgurl\\x3d","",$m[$i]);
        $m[$i] = str_replace(stristr($m[$i],"\\x26imgrefurl"),"",$m[$i]);
        $m[$i] = preg_replace("/^\"/i","",$m[$i]);
        $m[$i] = preg_replace("/^,/i","",$m[$i]);
        if ($m[$i]!="") array_push($x,$m[$i]);
    }
    return $x;
}


public function pushMeTo($widgeturl,$text,$signature) {
    $agent = "Mozilla/5.0 (Windows; U; Windows NT 6.0; en-US; rv:1.9.0.12) Gecko/2009070611 Firefox/3.0.12";
    if (!function_exists("curl_init")) die("pushMeTo needs CURL module, please install CURL on your php.");
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $widgeturl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, $agent);
    $page = curl_exec($ch);
    preg_match("/form action=\"(.*?)\"/", $page, $form_action);
    preg_match("/textarea name=\"(.*?)\"/", $page, $message_field);
    preg_match("/input type=\"text\" name=\"(.*?)\"/", $page, $signature_field);
    $ch = curl_init();
    $strpost = $message_field[1].'=' . urlencode($text) . '&'.$signature_field[1].'=' . urlencode($signature);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $strpost );
    curl_setopt($ch, CURLOPT_URL, $form_action[1]);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, $agent);
    $page = curl_exec($ch);
}


public function setFacebookStatus($status, $login_email, $login_pass) {
    $debug = false;
    //CURL stuff
    //This executes the login procedure
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://login.facebook.com/login.php?m&amp;next=http%3A%2F%2Fm.facebook.com%2Fhome.php');
    curl_setopt($ch, CURLOPT_POSTFIELDS, 'email=' . urlencode($login_email) . '&pass=' . urlencode($login_pass) . '&login=' . urlencode("Log in"));
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_COOKIEJAR, "my_cookies.txt");
    curl_setopt($ch, CURLOPT_COOKIEFILE, "my_cookies.txt");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    //make sure you put a popular web browser here (signature for your web browser can be retrieved with 'echo $_SERVER['HTTP_USER_AGENT'];'
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 6.0; en-US; rv:1.9.0.12) Gecko/2009070611 Firefox/3.0.12");
    curl_exec($ch);
 
    //This executes the status update
    curl_setopt($ch, CURLOPT_POST, 0);
    curl_setopt($ch, CURLOPT_URL, 'http://m.facebook.com/home.php');
    $page = curl_exec($ch);
 
    curl_setopt($ch, CURLOPT_POST, 1);
    //this gets the post_form_id value
    preg_match("/input type=\"hidden\" name=\"post_form_id\" value=\"(.*?)\"/", $page, $form_id);
    //we'll also need the exact name of the form processor page
    preg_match("/form action=\"(.*?)\"/", $page, $form_num);
 
    curl_setopt($ch, CURLOPT_POSTFIELDS, 'post_form_id=' . $form_id[1] . '&status=' . urlencode($status) . '&update=' . urlencode("Update status"));
    //set url to form processor page
    curl_setopt($ch, CURLOPT_URL, 'http://m.facebook.com' . $form_num[1]);
    curl_exec($ch);
 
    if ($debug) {
        //show information regarding the request
        print_r(curl_getinfo($ch));
        echo curl_errno($ch) . '-' . curl_error($ch);
        echo "<br><br>Your Facebook status seems to have been updated.";
    }
    //close the connection
    curl_close($ch);
}



public function validateEmailSmtp($email, $probe_address="", $debug=false) {
    # --------------------------------
    # function to validate email address 
    # through a smtp connection with the 
    # mail server.
    # by Giulio Pons
    # http://www.barattalo.it
    # --------------------------------
    $output = "";
    # --------------------------------
    # Check syntax with regular expression
    # --------------------------------
    if (!$probe_address) $probe_address = $_SERVER["SERVER_ADMIN"];
    if (preg_match('/^([a-zA-Z0-9\._\+-]+)\@((\[?)[a-zA-Z0-9\-\.]+\.([a-zA-Z]{2,7}|[0-9]{1,3})(\]?))$/', $email, $matches)) {
        $user = $matches[1];
        $domain = $matches[2];
        # --------------------------------
        # Check availability of DNS MX records
        # --------------------------------
        if (function_exists('checkdnsrr')) {
            # --------------------------------
            # Construct array of available mailservers
            # --------------------------------
            if(getmxrr($domain, $mxhosts, $mxweight)) {
                for($i=0;$i<count($mxhosts);$i++){
                    $mxs[$mxhosts[$i]] = $mxweight[$i];
                }
                asort($mxs);
                $mailers = array_keys($mxs);
            } elseif(checkdnsrr($domain, 'A')) {
                $mailers[0] = gethostbyname($domain);
            } else {
                $mailers=array();
            }
            $total = count($mailers);
            # --------------------------------
            # Query each mailserver
            # --------------------------------
            if($total > 0) {
                # --------------------------------
                # Check if mailers accept mail
                # --------------------------------
                for($n=0; $n < $total; $n++) {
                    # --------------------------------
                    # Check if socket can be opened
                    # --------------------------------
                    if($debug) { $output .= "Checking server $mailers[$n]...\n";}
                    $connect_timeout = 2;
                    $errno = 0;
                    $errstr = 0;
                    # --------------------------------
                    # controllo probe address
                    # --------------------------------
                    if (preg_match('/^([a-zA-Z0-9\._\+-]+)\@((\[?)[a-zA-Z0-9\-\.]+\.([a-zA-Z]{2,7}|[0-9]{1,3})(\]?))$/', $probe_address,$fakematches)) {
                        $probe_domain = str_replace("@","",strstr($probe_address, '@'));
 
                        # --------------------------------
                        # Try to open up socket
                        # --------------------------------
                        if($sock = @fsockopen($mailers[$n], 25, $errno , $errstr, $connect_timeout)) {
                            $response = fgets($sock);
                            if($debug) {$output .= "Opening up socket to $mailers[$n]... Success!\n";}
                            stream_set_timeout($sock, 5);
                            $meta = stream_get_meta_data($sock);
                            if($debug) { $output .= "$mailers[$n] replied: $response\n";}
                            # --------------------------------
                            # Be sure to set this correctly!
                            # --------------------------------
                            $cmds = array(
                                "HELO $probe_domain",
                                "MAIL FROM: <$probe_address>",
                                "RCPT TO: <$email>",
                                "QUIT",
                            );
                            # --------------------------------
                            # Hard error on connect -> break out
                            # --------------------------------
                            if(!$meta['timed_out'] && !preg_match('/^2\d\d[ -]/', $response)) {
                                $codice = trim(substr(trim($response),0,3));
                                if ($codice=="421") {
                                    //421 #4.4.5 Too many connections to this host.
                                    $error = $response;
                                    break;
                                } else {
                                    if($response=="" || $codice=="") {
                                        //c'� stato un errore ma la situazione � poco chiara
                                        $codice = "0";
                                    }
                                    $error = "Error: $mailers[$n] said: $response\n";
                                    break;
                                }
                                break;
                            }
                            foreach($cmds as $cmd) {
                                $before = microtime(true);
                                fputs($sock, "$cmd
");
                                $response = fgets($sock, 4096);
                                $t = 1000*(microtime(true)-$before);
                                if($debug) {$output .= "$cmd\n$response" . "(" . sprintf('%.2f', $t) . " ms)\n";}
                                if(!$meta['timed_out'] && preg_match('/^5\d\d[ -]/', $response)) {
                                    $codice = trim(substr(trim($response),0,3));
                                    if ($codice<>"552") {
                                        $error = "Unverified address: $mailers[$n] said: $response";
                                        break 2;
                                    } else {
                                        $error = $response;
                                        break 2;
                                    }
                                    # --------------------------------
                                    // il 554 e il 552 sono quota
                                    // 554 Recipient address rejected: mailbox overquota
                                    // 552 RCPT TO: Mailbox disk quota exceeded
                                    # --------------------------------
                                }
                            }
                            fclose($sock);
                            if($debug) { $output .= "Succesful communication with $mailers[$n], no hard errors, assuming OK\n";}
                            break;
                        } elseif($n == $total-1) {
                            $error = "None of the mailservers listed for $domain could be contacted";
                            $codice = "0";
                        }
                    } else {
                        $error = "Il probe_address non � una mail valida.";
                    }
                }
            } elseif($total <= 0) {
                $error = "No usable DNS records found for domain '$domain'";
            }
        }
    } else {
        $error = 'Address syntax not correct';
    }
    if($debug) {
        print nl2br(htmlentities($output));
    }
    if(!isset($codice)) {$codice="n.a.";}
    if(isset($error)) return array($error,$codice); else return true;
}




public function get_youtube_views($video_ID) {
//get_youtube_views('5WEK6HgXBsQ');
        $JSON = file_get_contents("https://gdata.youtube.com/feeds/api/videos?q={$video_ID}&alt=json");
        $JSON_Data = json_decode($JSON);
        $views = $JSON_Data->{'feed'}->{'entry'}[0]->{'yt$statistics'}->{'viewCount'};
        echo $views;
    }
 
 public function week_number( $date = 'today' ) 
{ 
//$week_num = week_number();
//$month = date("J", time());
//echo "Today is the $week_num week of $month";

    return ceil( date( 'j', strtotime( $date ) ) / 7 ); 
 
} 

public function days_in_month($month, $year) {
    if($month!=2) {
        if($month==9||$month==4||$month==6|| $month==11)
            return 30;
        else
            return 31;
    }
    else
        return $year%4==""&&$year%100!="" ? 29 : 28;
}   

public function daysInMonth($year, $month)
    { 
        return date("t", strtotime($year . "-" . $month . "-01")); 
    }


public function htmlmail($to, $subject, $message, $headers = NULL)
{
// Send Hassle Free and Dependable HTML Emails With PHP
    $mime_boundary = md5(time()); 
 
    $headers .= "\nMessage-ID: <" . time() . " TheSystem@{$_SERVER['SERVER_NAME']}>\n";
    $headers .= "X-Mailer: PHP " . phpversion() . "\n";
    $headers .= "MIME-Version: 1.0\n";
    $headers .= "Content-Type: multipart/alternative;boundary={$mime_boundary}\n\n";
 
    $newmessage = "This is a multi-part message in MIME format.";
    $newmessage .= "\n\n--{$mime_boundary}\n";
    $newmessage .= "Content-type: text/plain;charset=utf-8\n\n";
    $newmessage .= strip_tags(str_replace(array('<br>', '<br />'), "\n", $message)) . "\n\n";
 
    $newmessage .= "\n\n--{$mime_boundary}\n";
    $newmessage .= "Content-type: text/html;charset=utf-8\n\n";
 
    // prepended HTML
    $newmessage .= '<body style="margin:0"><table width="100%" height="100%" border="0" cellpadding="0" cellspacing="0"><tr><td bgcolor="#ffffff" valign="top"><table width="750" border="0" cellpadding="0" cellspacing="0" align="center"><tr><td bgcolor="#ffffff" width="750">';
 
    // HTML message that was passed to this function
    $newmessage .= $message;
 
    // appended HTML
    $newmessage .= '</td></tr></table></td></tr></table></body>';
 
    return mail($to, $subject, $newmessage, $headers);
}


public function mail_file( $to, $subject, $messagehtml, $from, $fileatt, $replyto="" ) {
 
        $ext = strrchr( $fileatt , '.');
        $fileatttype = "";
        if ($ext == ".doc") $fileatttype = "application/msword";
        if ($ext == ".jpg") $fileatttype = "image/jpeg";
        if ($ext == ".gif") $fileatttype = "image/gif";
        if ($ext == ".pdf") $fileatttype = "application/x-pdf";
        if ($fileatttype=="") $fileatttype = "application/octet-stream";
 
        $file = fopen($fileatt, "rb");
        $data = fread($file,  filesize( $fileatt ) );
        fclose($file);
 
        $content = chunk_split(base64_encode($data));
        $uid = md5(uniqid(time()));
 
        $headers = "From: $from";
        if ($replyto) $headers .= "Reply-To: ".$replyto."";
        $headers .= "MIME-Version: 1.0";
        $headers .= "Content-Type: multipart/mixed; boundary=\"".$uid."\" ";
        $headers .= "This is a multi-part message in MIME format.";
        $headers .= "--".$uid."";
        $headers .= "Content-type:text/html; charset=iso-8859-1";
        $headers .= "Content-Transfer-Encoding: 7bit";
        $headers .= $messagehtml."";
        $headers .= "--".$uid."";
        $headers .= "Content-Type: ".$fileatttype."; name=\"".basename($fileatt)."\"";
        $headers .= "Content-Transfer-Encoding: base64";
        $headers .= "Content-Disposition: attachment; filename=\"".basename($fileatt)."\"";
        $headers .= $content."";
        $headers .= "--".$uid."--";
        return mail( $to, $subject, strip_tags($messagehtml), str_replace("","\n",$headers) ) ;
 
    }

// How to send push notification to iPhone with PHP and pushme.to

public function pushMeToIphone($widgeturl,$text,$signature) {
    $agent = "Mozilla/5.0 (Windows; U; Windows NT 6.0; en-US; rv:1.9.0.12) Gecko/2009070611 Firefox/3.0.12";
    if (!function_exists("curl_init")) die("pushMeTo needs CURL module, please install CURL on your php.");
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $widgeturl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, $agent);
    $page = curl_exec($ch);
    preg_match("/form action=\"(.*?)\"/", $page, $form_action);
    preg_match("/textarea name=\"(.*?)\"/", $page, $message_field);
    preg_match("/input type=\"text\" name=\"(.*?)\"/", $page, $signature_field);
    $ch = curl_init();
    $strpost = $message_field[1].'=' . urlencode($text) . '&'.$signature_field[1].'=' . urlencode($signature);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $strpost );
    curl_setopt($ch, CURLOPT_URL, $form_action[1]);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, $agent);
    $page = curl_exec($ch);
}

// How to change Twitter's status with php and Curl without oAuth

public function twitterSetStatus($user,$pwd,$status) {
    if (!function_exists("curl_init")) die("twitterSetStatus needs CURL module, please install CURL on your php.");
    $ch = curl_init();
 
    // -------------------------------------------------------
    // get login form and parse it
    curl_setopt($ch, CURLOPT_URL, "https://mobile.twitter.com/session/new");
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_FAILONERROR, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_COOKIEJAR, "my_cookies.txt");
    curl_setopt($ch, CURLOPT_COOKIEFILE, "my_cookies.txt");
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (iPhone; U; CPU like Mac OS X; en) AppleWebKit/420+ (KHTML, like Gecko) Version/3.0 Mobile/1A543a Safari/419.3 ");
    $page = curl_exec($ch);
    $page = stristr($page, "<div class='signup-body'>");
    preg_match("/form action=\"(.*?)\"/", $page, $action);
    preg_match("/input name=\"authenticity_token\" type=\"hidden\" value=\"(.*?)\"/", $page, $authenticity_token);
 
    // -------------------------------------------------------
    // make login and get home page
    $strpost = "authenticity_token=".urlencode($authenticity_token[1])."&username=".urlencode($user)."&password=".urlencode($pwd);
    curl_setopt($ch, CURLOPT_URL, $action[1]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $strpost);
    $page = curl_exec($ch);
    // check if login was ok
    preg_match("/\<div class=\"warning\"\>(.*?)\<\/div\>/", $page, $warning);
    if (isset($warning[1])) return $warning[1];
    $page = stristr($page,"<div class='tweetbox'>");
    preg_match("/form action=\"(.*?)\"/", $page, $action);
    preg_match("/input name=\"authenticity_token\" type=\"hidden\" value=\"(.*?)\"/", $page, $authenticity_token);
 
    // -------------------------------------------------------
    // send status update
    $strpost = "authenticity_token=".urlencode($authenticity_token[1]);
    $tweet['display_coordinates']='';
    $tweet['in_reply_to_status_id']='';
    $tweet['lat']='';
    $tweet['long']='';
    $tweet['place_id']='';
    $tweet['text']=$status;
    $ar = array("authenticity_token" => $authenticity_token[1], "tweet"=>$tweet);
    $data = http_build_query($ar);
    curl_setopt($ch, CURLOPT_URL, $action[1]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    $page = curl_exec($ch);
 
    return true;
}





public function is_mobile(){
 
    // returns true if one of the specified mobile browsers is detected
 
    $regex_match="/(nokia|iphone|android|motorola|^mot\-|softbank|foma|docomo|kddi|up\.browser|up\.link|";
    $regex_match.="htc|dopod|blazer|netfront|helio|hosin|huawei|novarra|CoolPad|webos|techfaith|palmsource|";
    $regex_match.="blackberry|alcatel|amoi|ktouch|nexian|samsung|^sam\-|s[cg]h|^lge|ericsson|philips|sagem|wellcom|bunjalloo|maui|";    
    $regex_match.="symbian|smartphone|midp|wap|phone|windows ce|iemobile|^spice|^bird|^zte\-|longcos|pantech|gionee|^sie\-|portalmmm|";
    $regex_match.="jig\s browser|hiptop|^ucweb|^benq|haier|^lct|opera\s*mobi|opera\*mini|320x320|240x320|176x220";
    $regex_match.=")/i";        
    return isset($_SERVER['HTTP_X_WAP_PROFILE']) or isset($_SERVER['HTTP_PROFILE']) or preg_match($regex_match, strtolower($_SERVER['HTTP_USER_AGENT']));
}
 
/*
allow the user a way to force either the full or mobile versions of the site - use a GET parameter on requests:
 
include links to both versions of the site w/ the special force mode parameters, 'mobile' and 'full':
 Always check for 'mobile' or 'full' parameters before accounting for any User-Agent conditions:
*/
 
//if ($_GET['mobile']) {
 //$is_mobile = true;
//}
 
///if ($_GET['full']) {
// $is_mobile = false;
//}
//if($is_mobile) {
    //it's a mobile browser, do something
    //header("Location: http://www.yoursite.com/mobile");
//} else {
    //it's not a mobile browser, do something else
    //header("Location: http://www.yoursite.com/desktop");
    // or instead of a redirect, simply build html below
 
 
//}


public function array_find($needle, array $haystack)
{
    foreach ($haystack as $key => $value) {
        if (false !== stripos($needle, $value)) {
            return $key;
            //break;
        }
    }
    return false;
}




public function attr($s,$attrname) { 
    // return html attribute
    preg_match_all('#\s*('.$attrname.')\s*=\s*["|\']([^"\']*)["|\']\s*#i', $s, $x);
    if (count($x)>=3) return $x[2][0]; else return "";
}
 
 
public function getFlickrFeed($id,$n) {
// Example Request with ID and how many photos required
//echo getFlickrFeed("33785122@N08",9);
 
    $urlenc = urlencode("http://api.flickr.com/services/feeds/photos_public.gne?id=$id&lang=en-us&format=rss_200");
    $url = "http://pipes.yahoo.com/pipes/pipe.run?_id=HHvHTP7h2xGPZ1tDdbq02Q&_render=rss&urlinput2=$urlenc";
    $s = file_get_contents($url);
    preg_match_all('#<item>(.*)</item>#Us', $s, $items);
    $out = "";
    for($i=0;$i<count($items[1]);$i++) {
        if($i>=$n) return $out;
        $item = $items[1][$i];
        preg_match_all('#<link>(.*)</link>#Us', $item, $temp);
        $link = $temp[1][0];
        preg_match_all('#<title>(.*)</title>#Us', $item, $temp);
        $title = $temp[1][0];
        preg_match_all('#<media:thumbnail([^>]*)>#Us', $item, $temp);
        $thumb = $this->attr($temp[0][0],"url");
 
        $thumb2 = explode("_",$thumb);
        $img =  $thumb2[0]."_".$thumb2[1].".jpg";
        //print $img;
        $out.="<a href='$link' target='_blank' title=\"".str_replace('"','',$title)."\"><img src='$img'/></a>";
    }
    return $out;
}
 

public function getRealIpAddr()
{
    if (!empty($_SERVER['HTTP_CLIENT_IP']))   //check ip from share internet
    {
      $ip=$_SERVER['HTTP_CLIENT_IP'];
    }
    elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))   //to check ip is pass from proxy
    {
      $ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    else
    {
      $ip=$_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}


// Distance between two UK postcodes

public function getDistance($postcode1, $postcode2) {  
 //echo getDistance('W1T 1AL', 'WD6 1JG');

    $coordinates1 = file_get_contents('http://maps.googleapis.com/maps/api/geocode/json?address=' . urlencode($postcode1) . '&sensor=true');
    $coordinates1 = json_decode($coordinates1);
 
    $coordinates2 = file_get_contents('http://maps.googleapis.com/maps/api/geocode/json?address=' . urlencode($postcode2) . '&sensor=true');
    $coordinates2 = json_decode($coordinates2);
 
    $earth_radius = 6371;  
 
    $dLat = deg2rad($coordinates2->results[0]->geometry->location->lat - $coordinates1->results[0]->geometry->location->lat);  
    $dLon = deg2rad($coordinates2->results[0]->geometry->location->lng - $coordinates1->results[0]->geometry->location->lng);  
 
    $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($latitude1)) * cos(deg2rad($latitude2)) * sin($dLon/2) * sin($dLon/2);  
    $c = 2 * asin(sqrt($a));  
    $d = $earth_radius * $c;
 
    $d = $d * 0.621;
 
    return $d;  
} 
 
// Distance Between Two Points
public function lineDistance($point1, $point2)
{
    $xs = 0;
    $ys = 0;

    // Correct property access and parentheses around the math operations
    $xs = ($point2->x - $point1->x);
    $xs = $xs * $xs;

    $ys = ($point2->y - $point1->y);
    $ys = $ys * $ys;

    return sqrt($xs + $ys); // No need for Math.sqrt in PHP, use sqrt() directly
}


public function doShortURL($url) {
    $short_url= file_get_contents('http://tinyurl.com/api-create.php?url=' . urlencode( $url ) );
    return $short_url;
}
 
 
public function doShortURLDecode($url) {
    $ch = @curl_init($url);
    @curl_setopt($ch, CURLOPT_HEADER, TRUE);
    @curl_setopt($ch, CURLOPT_NOBODY, TRUE);
    @curl_setopt($ch, CURLOPT_FOLLOWLOCATION, FALSE);
    @curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    $response = @curl_exec($ch);
    preg_match('/Location: (.*)\n/', $response, $a);
    if (!isset($a[1])) return $url;
    return $a[1];
}


public function get_data($url)
{
//$url='http://twitter.com/statuses/user_timeline/16387631.json'; //rss link for the twitter timeline
//print_r(get_data($url)); //dumps the content, you can manipulate as you wish to
$ch = curl_init();
$timeout = 5;
curl_setopt($ch,CURLOPT_URL,$url);
curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,$timeout);
$data = curl_exec($ch);
curl_close($ch);
return $data;
}

// Fetch & Save / Cache an image from a remote server using PHP

public function cache_image($image_url){
    //replace with your cache directory
    $image_path = 'path/to/cache/dir/';
    //get the name of the file
    $exploded_image_url = explode("/",$image_url);
    $image_filename = end($exploded_image_url);
    $exploded_image_filename = explode(".",$image_filename);
    $extension = end($exploded_image_filename);
    //make sure its an image
    if($extension=="gif"||$extension=="jpg"||$extension=="png"){
        //get the remote image
        $image_to_fetch = file_get_contents($image_url);
        //save it
        $local_image_file  = fopen($image_path.$image_filename, 'w+');
        chmod($image_path.$image_filename,0755);
        fwrite($local_image_file, $image_to_fetch);
        fclose($local_image_file);  
    }
}
 
//usage
//cache_image("http://www.flickr.com/someimage.jpg");

public function GeneratePassword($length=8, $strength=0){
    $vowels = 'aeuy';
    $consonants = 'bdghjmnpqrstvz';
    if($strength >= 1) $consonants .= 'BDGHJLMNPQRSTVWXZ';
    if($strength >= 2) $vowels .= 'AEUY';
    if($strength >= 3) $consonants .= '12345';
    if($strength >= 4) $consonants .= '67890';
    if($strength >= 5) $vowels .= '@#$%';
 
    $password = '';
    $alt = time() % 2;
    for($i = 0; $i < $length; $i++){
        if($alt == 1){
            $password .= $consonants[(rand() % strlen($consonants))];
            $alt = 0;
        }else{
            $password .= $vowels[(rand() % strlen($vowels))];
            $alt = 1;
        }
    }
    return $password;
}

public function search_in_array($value, $array) {
     if(in_array($value, $array)) {
          return true;
     }
     foreach($array as $item) {
          if(is_array($item) && search_in_array($value, $item))
               return true;
     }
   return false;
}
 
//$so = (search_in_array('orange',$array)) ? 'nothing' : 'daa';
//echo $so;

public function GenerateRandomString($length) {
    $characters = array('2', '3', '4', '5', '6', '7', '8', '9',
            'a', 'c', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'm', 'p',
            'q', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z');
    $randomString = "";
    while(strlen($randomString) < $length) {
        $randomCharacterIndex = rand(0, count($characters));
        $randomString .= $characters[$randomCharacterIndex];
    }
    return $randomString;
}

public function makeMyUrlFriendly($url){
    $output = preg_replace("/\s+/" , "_" , trim($url));
    $output = preg_replace("/\W+/" , "" , $output);
    $output = preg_replace("/_/" , "-" , $output);
    return strtolower($output);
}


// Activate Links in String PHP


public function activateLinks($string)
{  
 /*
    // This regular expression looks for <strong>http:// </strong>type url
    $string = preg_replace('(((f|ht){1}tp://)[-a-zA-Z0-9@:%_+.~#?&//=]+)',
    '<a href="1" target=_blank>$0$1$2$3</a>', $string);
     
    // This regular expression looks for <strong>www. </strong>type url
    $string = preg_replace('([[:space:]()[{}])(www.[-a-zA-Z0-9@:%_+.~#?&//=]+)',
    '1<a href="http://2" target=_blank>$0$1$2$3</a>', $string);
     
    // This regular expression looks for <strong>email@email.com</strong>
    $string = preg_replace('([_.0-9a-z-]+@([0-9a-z][0-9a-z-]+.)+[a-z]{2,3})',
    '<a href="mailto:1" target=_blank>$0$1$2$3</a>', $string);
     */
     
     $m = preg_match_all('/http:\/\/[a-z0-9A-Z.]+(?(?=[\/])(.*))/', $string, $match); 

     if ($m) { 
         $links=$match[0]; 
         for ($j=0;$j<$m;$j++) { 
             $string=str_replace($links[$j],'<a href="'.$links[$j].'">'.$links[$j].'</a>',$string); 
         } 
     }
     
     $m = preg_match_all('(www.[-a-zA-Z0-9@:%_+.~#?&//=]+)', $string, $match); 

     if ($m) { 
         $links=$match[0]; 
         for ($j=0;$j<$m;$j++) { 
             $string=str_replace($links[$j],'<a href="'.$links[$j].'">'.$links[$j].'</a>',$string); 
         } 
     }
     
    return $string;
     
}

//PHP URI From String

public function getURI($url) {
  $cleanURL =  str_replace(" ",'-',ucwords(preg_replace('/[^a-zA-Z0-9 ]/', "", str_replace(array('&','@'),array('and','at'),trim($url)))));
  while (strpos($cleanURL,'--') > 0) { $cleanURL = str_replace('--','-',$cleanURL); } // get rid of any excess -'s
  return $cleanURL;
}

public function redirect($url)
{
    header("Location: $url");
    exit();
}




public function  validateCC($ccnum,  $type  =  'unknown'){ 


        //Clean  up  input 

        $type  =  strtolower($type); 
        $ccnum  =  ereg_replace( '[-[:space:]]',  '',$ccnum);   


        //Do  type  specific  checks 

        if  ($type  ==  'unknown')  { 

                //Skip  type  specific  checks 

        } 
        elseif  ($type  ==  'mastercard'){ 
                if  (strlen($ccnum)  !=  16  ||  !ereg( '^5[1-5]',  $ccnum))   
return  0; 
        } 
        elseif  ($type  ==  'visa'){ 
                if  ((strlen($ccnum)  !=  13  &&  strlen($ccnum)  !=  16)  ||   
substr($ccnum,  0,  1)  !=  '4')  return  0; 
        } 
        elseif  ($type  ==  'amex'){ 
                if  (strlen($ccnum)  !=  15  ||  !ereg( '^3[47]',  $ccnum))   
return  a; 
        } 
        elseif  ($type  ==  'discover'){ 
                if  (strlen($ccnum)  !=  16  ||  substr($ccnum,  0,  4)  !=   
'6011')  return  0; 
        } 
        else  { 
                //invalid  type  entered 
                return  -1; 
        } 


        //  Start  MOD  10  checks 

        $dig  =  $this->toCharArray($ccnum); 
        $numdig  =  sizeof  ($dig); 
        $j  =  0; 
        for  ($i=($numdig-2);  $i>=0;  $i-=2){ 
                $dbl[$j]  =  $dig[$i]  *  2; 
                $j++; 
        }         
        $dblsz  =  sizeof($dbl); 
        $validate  =0; 
        for  ($i=0;$i<$dblsz;$i++){ 
                $add  =  $this->toCharArray($dbl[$i]); 
                for  ($j=0;$j<sizeof($add);$j++){ 
                        $validate  +=  $add[$j]; 
                } 
        $add  =  ''; 
        } 
        for  ($i=($numdig-1);  $i>=0;  $i-=2){ 
                $validate  +=  $dig[$i];   
        } 
        if  (substr($validate,  -1,  1)  ==  '0')  return  1; 
        else  return  0; 
} 


//  takes  a  string  and  returns  an  array  of  characters 

public function  toCharArray($input){ 
        $len  =  strlen($input); 
        for  ($j=0;$j<$len;$j++){ 
                $char[$j]  =  substr($input,  $j,  1);         
        } 
        return  ($char); 
} 

public function get_lat_long($address){

    $address = str_replace(" ", "+", $address);

    $json = file_get_contents("http://maps.google.com/maps/api/geocode/json?address=$address&sensor=false&region=$region");
    $json = json_decode($json);
    //$lat = $json->{'results'}[0]->{'geometry'}->{'location'}->{'lat'};
    //$long = $json->{'results'}[0]->{'geometry'}->{'location'}->{'lng'};
    return $json;
}






public function date_getFullTimeDifference( $start, $end )
{
$uts['start']      =    strtotime( $start );
        $uts['end']        =    strtotime( $end );
        if( $uts['start']!==-1 && $uts['end']!==-1 )
        {
            if( $uts['end'] >= $uts['start'] )
            {
                $diff    =    $uts['end'] - $uts['start'];
                if( $years=intval((floor($diff/31104000))) )
                    $diff = $diff % 31104000;
                if( $months=intval((floor($diff/2592000))) )
                    $diff = $diff % 2592000;
                if( $days=intval((floor($diff/86400))) )
                    $diff = $diff % 86400;
                if( $hours=intval((floor($diff/3600))) )
                    $diff = $diff % 3600;
                if( $minutes=intval((floor($diff/60))) )
                    $diff = $diff % 60;
                $diff    =    intval( $diff );
                return( array('years'=>$years,'months'=>$months,'days'=>$days, 'hours'=>$hours, 'minutes'=>$minutes, 'seconds'=>$diff) );
            }
            else
            {
                //echo "Ending date/time is earlier than the start date/time";
            }
        }
        else
        {
            //echo "Invalid date/time data detected";
        }
}

public function smcom_format_date($date) {
    return date('g:i:s A', strtotime($date));
}



public function getLatLongFromPostcode($postcode, $country, $gmapApiKey)
{
    /* remove spaces from postcode */
    $postcode = urlencode(trim($postcode));
 
    /* connect to the google geocode service */
    $file = "http://maps.google.com/maps/geo?q=$postcode,$country&amp;amp;output=csv&amp;amp;key=$gmapApiKey";
 
    $contents = file_get_contents($file);
    return explode(',', $contents);
}
//$geocode = getLatLongFromPostcode ('W1B 2EL', 'uk', 'ABQIAAAA2xzlSb6lvuy1qNdW6D87dBSX4wCNHpcq6aPi4BCCV9JuIkX6UhQgj9ZmrEF5FYpCKxFE5wNmrj');

public function create_time_range($start, $end, $by='30 mins') { 

   $start_time = strtotime($start); 
   $end_time   = strtotime($end); 

   $current    = time(); 
   $add_time   = strtotime('+'.$by, $current); 
   $diff       = $add_time-$current; 

   $times = array(); 
   while ($start_time < $end_time) { 
       $times[] = $start_time; 
       $start_time += $diff; 
    } 
  $times[] = $start_time; 
  return $times; 
 } 

public function dateRange( $first, $last, $step = '+1 day', $format = 'Y/m/d' ) {

    $dates = array();
    $current = strtotime( $first );
    $last = strtotime( $last );

    while( $current <= $last ) {

        $dates[] = date( $format, $current );
        $current = strtotime( $step, $current );
    }

    return $dates;
}


public function getLnt($zip)
{
$url = "http://maps.googleapis.com/maps/api/geocode/json?address=".urlencode($zip)."&sensor=false";
$result_string = file_get_contents($url);
$result = json_decode($result_string, true);
return $result;
}


public function words_limit( $str, $num, $append_str='' ){
$words = preg_split( '/[\s]+/', $str, -1, PREG_SPLIT_OFFSET_CAPTURE );
 if( isset($words[$num][1]) ){
   $str = substr( $str, 0, $words[$num][1] ).$append_str;
 }
unset( $words, $num );
return trim( $str );
//echo words_limit($yourString, 50,'...'); or echo words_limit($yourString, 50);
}


public function video_image($url){
   $image_url = parse_url($url);
     if($image_url['host'] == 'www.youtube.com' || 
        $image_url['host'] == 'youtube.com'){
         $array = explode("&", $image_url['query']);
         return "http://img.youtube.com/vi/".substr($array[0], 2)."/0.jpg";
     }else if($image_url['host'] == 'www.youtu.be' || 
              $image_url['host'] == 'youtu.be'){
         $array = explode("/", $image_url['path']);
         return "http://img.youtube.com/vi/".$array[1]."/0.jpg";
     }else if($image_url['host'] == 'www.vimeo.com' || 
         $image_url['host'] == 'vimeo.com'){
         $hash = unserialize(file_get_contents("http://vimeo.com/api/v2/video/".
         substr($image_url['path'], 1).".php"));
         return $hash[0]["thumbnail_medium"];
     }
}


public function age_from_dob($dob){
$dob = strtotime($dob);
$y = date('Y', $dob);
 if (($m = (date('m') - date('m', $dob))) < 0) {
  $y++;
 } elseif ($m == 0 && date('d') - date('d', $dob) < 0) {
  $y++;
 }
return date('Y') - $y;
}

public function dt_differ($start, $end){
  $start = date("G:i:s:m:d:Y", strtotime($start));
  $date1=explode(":", $start);

  $end  = date("G:i:s:m:d:Y", strtotime($end));
  $date2=explode(":", $end);
    
  $starttime = mktime(date($date1[0]),date($date1[1]),date($date1[2]),
  date($date1[3]),date($date1[4]),date($date1[5]));
  $endtime   = mktime(date($date2[0]),date($date2[1]),date($date2[2]),
  date($date2[3]),date($date2[4]),date($date2[5]));

  $seconds_dif = $starttime-$endtime;

  return $seconds_dif;
}
// Convert seconds to days hour and minutes in php

public function seconds2days($mysec) {
    $mysec = (int)$mysec;
    if ( $mysec === 0 ) {
        return '0 second';
    }

    $mins  = 0;
    $hours = 0;
    $days  = 0;


    if ( $mysec >= 60 ) {
        $mins = (int)($mysec / 60);
        $mysec = $mysec % 60;
    }
    if ( $mins >= 60 ) {
        $hours = (int)($mins / 60);
        $mins = $mins % 60;
    }
    if ( $hours >= 24 ) {
        $days = (int)($hours / 24);
        $hours = $hours % 60;
    }

    $output = '';

    if ($days){
        $output .= $days." days ";
    }
    if ($hours) {
        $output .= $hours." hours ";
    }
    if ( $mins ) {
        $output .= $mins." minutes ";
    }
    if ( $mysec ) {
        $output .= $mysec." seconds ";
    }
    $output = rtrim($output);
    return $output;
}

// Convert any date format to Mysql Date format using PHP

public function convertToMysqlDate($mydate, $dtformat) {

//$dtformat = 'Y/d/m';
//$dte='2012/25/12';
// Or you can also convert any date to above date format
//$dte = date(strtotime('25/12/2013'),$dtformat);
//$newdt = convertToMysqlDate($dte, $dtformat);
//echo "Converted Date:" . $newdt;
    $dt = new DateTime();
    $date = $dt->createFromFormat($dtformat, $mydate);
    $convertdt = $date->format('Y-m-d');
    return $convertdt;
}

// Convert  Rupees to Dollar In Real Time in PHP

public function rupees_to_dollar($Amount, $currencyfrom, $currencyto) {
$buffer = file_get_contents('http://finance.yahoo.com/currency-converter');
preg_match_all('/name=(\"|\')conversion-date(\"|\') 
value=(\"|\')(.*)(\"|\')>/i',$buffer, $match);
$date = preg_replace('/name=(\"|\')conversion-date(\"|\') 
value=(\"|\')(.*)(\"|\')>/i','$4', $match[0][0]);
unset($buffer);
unset($match);
$buffer = file_get_contents('http://finance.yahoo.com/currency/
converter-results/'.$date.'/'.$Amount.'-'.strtolower($currencyfrom).'-to-'.
strtolower($currencyto).'html');
preg_match_all('/<span class=\"converted-result\">(.*)<
\/span>/i', $buffer, $match);
$match[0] = preg_replace('/<span class=\"converted-result\">
(.*)<\/span>/i', '$1',$match[0]);
unset($buffer);
return $match[0][0];
}


public function createCaptcha()
{
session_start();
$text = rand(10000,99999);
$_SESSION["vercode"] = $text;
$height = 22; //CAPTCHA image height
$width = 60; //CAPTCHA image width
$image_p = imagecreate($width, $height);
$black = imagecolorallocate($image_p, 0, 0, 0);
$white = imagecolorallocate($image_p, 255, 255, 255);
$font_size = 12; 
imagestring($image_p, $font_size, 5, 3, $text, $white);
imagejpeg($image_p, null, 80);
return $_SESSION["vercode"];
}


public function exchangeRate( $amount, $from_Currency, $to_Currency)
{
//echo $pp=exchangeRate('100','GBP','USD');
  $amount = urlencode($amount);
  $from_Currency = urlencode($from_Currency);
  $to_Currency = urlencode($to_Currency);
   $get = file_get_contents("https://www.google.com/finance/converter?a=$amount&from=" . $from_Currency . "&to=" . $to_Currency);
 //print_r($get);
  $get = explode("<span class=bld>",$get);
  $get = explode("</span>",$get[1]);
  $converted_amount = preg_replace("/[^0-9\.]/", null, $get[0]);
  return round($converted_amount,1);
}


public function compileHours($times, $timestamp) {
    $times = $times[strtolower(date('D',$timestamp))];
    if(!strpos($times, '-')) return array();
    $hours = explode(",", $times);
    $hours = array_map('explode', array_pad(array(),count($hours),'-'), $hours);
    $hours = array_map('array_map', array_pad(array(),count($hours),'strtotime'), $hours, array_pad(array(),count($hours),array_pad(array(),2,$timestamp)));
    end($hours);
    if ($hours[key($hours)][0] > $hours[key($hours)][1]) $hours[key($hours)][1] = strtotime('+1 day', $hours[key($hours)][1]);
    return $hours;
}

public function isOpen($now, $times) {
    $open = 0; // time until closing in seconds or 0 if closed
    // merge opening hours of today and the day before
    $hours = array_merge($this->compileHours($times, strtotime('yesterday',$now)),$this->compileHours($times, $now)); 

    foreach ($hours as $h) {
        if ($now >= $h[0] and $now < $h[1]) {
            $open = $h[1] - $now;
            return $open;
        } 
    }
    return $open;
}




 
public function encode_email($email='info@domain.com', $linkText='Contact Us', $attrs ='class="emailencoder"' )
{
    // remplazar aroba y puntos
    $email = str_replace('@', '&#64;', $email);
    $email = str_replace('.', '&#46;', $email);
    $email = str_split($email, 5);

    $linkText = str_replace('@', '&#64;', $linkText);
    $linkText = str_replace('.', '&#46;', $linkText);
    $linkText = str_split($linkText, 5);
    
    $part1 = '<a href="ma';
    $part2 = 'ilto&#58;';
    $part3 = '" '. $attrs .' >';
    $part4 = '</a>';

    $encoded = '<script type="text/javascript">';
    $encoded .= "document.write('$part1');";
    $encoded .= "document.write('$part2');";
    foreach($email as $e)
    {
            $encoded .= "document.write('$e');";
    }
    $encoded .= "document.write('$part3');";
    foreach($linkText as $l)
    {
            $encoded .= "document.write('$l');";
    }
    $encoded .= "document.write('$part4');";
    $encoded .= '</script>';

    return $encoded;
}


public function is_valid_email1($email, $test_mx = false)
{
    if(eregi("^([_a-z0-9-]+)(\.[_a-z0-9-]+)*@([a-z0-9-]+)(\.[a-z0-9-]+)*(\.[a-z]{2,4})$", $email))
        if($test_mx)
        {
            list($username, $domain) = split("@", $email);
            return getmxrr($domain, $mxrecords);
        }
        else
            return true;
    else
        return false;
}





/*****
*@dir - Directory to destroy
*@virtual[optional]- whether a virtual directory
*/
public function destroyDir($dir, $virtual = false)
{
    $ds = DIRECTORY_SEPARATOR;
    $dir = $virtual ? realpath($dir) : $dir;
    $dir = substr($dir, -1) == $ds ? substr($dir, 0, -1) : $dir;
    if (is_dir($dir) && $handle = opendir($dir))
    {
        while ($file = readdir($handle))
        {
            if ($file == '.' || $file == '..')
            {
                continue;
            }
            elseif (is_dir($dir.$ds.$file))
            {
                destroyDir($dir.$ds.$file);
            }
            else
            {
                unlink($dir.$ds.$file);
            }
        }
        closedir($handle);
        rmdir($dir);
        return true;
    }
    else
    {
        return false;
    }
}



public function create_slug($string){
    $slug=preg_replace('/[^A-Za-z0-9-]+/', '-', $string);
    return $slug;
}



/********************
*@file - path to file
*/
public function force_download_file($file)
{
    if ((isset($file))&&(file_exists($file))) {
       header("Content-length: ".filesize($file));
       header('Content-Type: application/octet-stream');
       header('Content-Disposition: attachment; filename="' . $file . '"');
       readfile("$file");
    } else {
       echo "No file selected";
    }
}



public function getCloud( $data = array(), $minFontSize = 12, $maxFontSize = 30 )
{
//Creating a Tag Cloud $arr = Array('Actionscript' => 35, 'Adobe' => 22, 'Array' => 44, 'Background' => 43,'Blur' => 18, 'Canvas' => 33, 'Class' => 15, 'Color Palette' => 11, 'Crop' => 42, 'Delimiter' => 13, 'Depth' => 34, 'Design' => 8, 'Encode' => 12, 'Encryption' => 30, 'Extract' => 28, 'Filters' => 42);
//echo getCloud($arr, 12, 36);
    $minimumCount = min($data);
    $maximumCount = max($data);
    $spread       = $maximumCount - $minimumCount;
    $cloudHTML    = '';
    $cloudTags    = array();

    $spread == 0 && $spread = 1;

    foreach( $data as $tag => $count )
    {
        $size = $minFontSize + ( $count - $minimumCount ) 
            * ( $maxFontSize - $minFontSize ) / $spread;
        $cloudTags[] = '<a style="font-size: ' . floor( $size ) . 'px' 
        . '" class="tag_cloud" href="#" title="\'' . $tag  .
        '\' returned a count of ' . $count . '">' 
        . htmlspecialchars( stripslashes( $tag ) ) . '</a>';
    }
    
    return join( "\n", $cloudTags ) . "\n";
}


/******************
*@email - Email address to show gravatar for
*@size - size of gravatar
*@default - URL of default gravatar to use
*@rating - rating of Gravatar(G, PG, R, X)
*/
public function show_gravatar($email, $size, $default, $rating)
{
    echo '<img src="http://www.gravatar.com/avatar.php?gravatar_id='.md5($email).
        '&default='.$default.'&size='.$size.'&rating='.$rating.'" width="'.$size.'px" 
        height="'.$size.'px" />';
}


// Original PHP code by Chirp Internet: www.chirp.com.au 
// Please acknowledge use of this code by including this header. 
public function myTruncate($string, $limit, $break=".", $pad="...") { 
    // return with no change if string is shorter than $limit  $short_string=myTruncate($long_string, 100, ' '); 
    if(strlen($string) <= $limit) 
        return $string; 
    
    // is $break present between $limit and the end of the string?  
    if(false !== ($breakpoint = strpos($string, $break, $limit))) {
        if($breakpoint < strlen($string) - 1) { 
            $string = substr($string, 0, $breakpoint) . $pad; 
        } 
    }
    return $string; 
}



/* creates a compressed zip file */
public function create_zip($files = array(),$destination = '',$overwrite = false) {
    //if the zip file already exists and overwrite is false, return false $files=array('file1.jpg', 'file2.jpg', 'file3.gif');
//create_zip($files, 'myzipfile.zip', true);

    if(file_exists($destination) && !$overwrite) { return false; }
    //vars
    $valid_files = array();
    //if files were passed in...
    if(is_array($files)) {
        //cycle through each file
        foreach($files as $file) {
            //make sure the file exists
            if(file_exists($file)) {
                $valid_files[] = $file;
            }
        }
    }
    //if we have good files...
    if(count($valid_files)) {
        //create the archive
        $zip = new ZipArchive();
        if($zip->open($destination,$overwrite ? ZIPARCHIVE::OVERWRITE : ZIPARCHIVE::CREATE) !== true) {
            return false;
        }
        //add the files
        foreach($valid_files as $file) {
            $zip->addFile($file,$file);
        }
        //debug
        //echo 'The zip archive contains ',$zip->numFiles,' files with a status of ',$zip->status;
        
        //close the zip -- done!
        $zip->close();
        
        //check to make sure the file exists
        return file_exists($destination);
    }
    else
    {
        return false;
    }
}
/***** Example Usage ***/




/**********************
*@file - path to zip file
*@destination - destination directory for unzipped files
*/
public function unzip_file($file, $destination){
    // create object
    $zip = new ZipArchive() ;
    // open archive
    if ($zip->open($file) !== TRUE) {
        die ("Could not open archive");
    }
    // extract contents to destination directory
    $zip->extractTo($destination);
    // close archive
    $zip->close();
    echo 'Archive extracted to directory';
}


// Resize Images on the fly


/**********************
*@filename - path to the image
*@tmpname - temporary path to thumbnail
*@xmax - max width
*@ymax - max height
*/
public function resize_image($filename, $tmpname, $xmax, $ymax)
{
    $ext = explode(".", $filename);
    $ext = $ext[count($ext)-1];

    if($ext == "jpg" || $ext == "jpeg")
        $im = imagecreatefromjpeg($tmpname);
    elseif($ext == "png")
        $im = imagecreatefrompng($tmpname);
    elseif($ext == "gif")
        $im = imagecreatefromgif($tmpname);
    
    $x = imagesx($im);
    $y = imagesy($im);
    
    if($x <= $xmax && $y <= $ymax)
        return $im;

    if($x >= $y) {
        $newx = $xmax;
        $newy = $newx * $y / $x;
    }
    else {
        $newy = $ymax;
        $newx = $x / $y * $newy;
    }
    
    $im2 = imagecreatetruecolor($newx, $newy);
    imagecopyresized($im2, $im, 0, 0, 0, 0, floor($newx), floor($newy), $x, $y);
    return $im2; 
}


public function send_sms($mobile,$msg)
{

//$message = "Hello World";
//$mobile = "918112998787";
//send_sms($mobile,$message)

    $authKey = "XXXXXXXXXXX";
date_default_timezone_set("Asia/Kolkata");
    $date = strftime("%Y-%m-%d %H:%M:%S");
//Multiple mobiles numbers separated by comma
    $mobileNumber = $mobile;

//Sender ID,While using route4 sender id should be 6 characters long.
    $senderId = "IKOONK";

//Your message to send, Add URL encoding here.
    $message = urlencode($msg);

//Define route 
    $route = "template";
//Prepare you post parameters
    $postData = array(
    'authkey' => $authKey,
    'mobiles' => $mobileNumber,
    'message' => $message,
    'sender' => $senderId,
    'route' => $route
);

//API URL
    $url="https://control.msg91.com/sendhttp.php";

// init the resource
    $ch = curl_init();
    curl_setopt_array($ch, array(
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $postData
    //,CURLOPT_FOLLOWLOCATION => true
));


//Ignore SSL certificate verification
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);


//get response
    $output = curl_exec($ch);
    
    //Print error if any
    if(curl_errno($ch))
    {
        echo 'error:' . curl_error($ch);
    }

    curl_close($ch);
}


// DETECT LOCATION OF THE USER
public function detect_city($ip) {
 
 //$ip = $_SERVER['REMOTE_ADDR'];
//$city = detect_city($ip);
//echo $city;
        
        $default = 'UNKNOWN';

        $curlopt_useragent = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.2) Gecko/20100115 Firefox/3.6 (.NET CLR 3.5.30729)';
        
        $url = 'http://ipinfodb.com/ip_locator.php?ip=' . urlencode($ip);
        $ch = curl_init();
        
        $curl_opt = array(
            CURLOPT_FOLLOWLOCATION  => 1,
            CURLOPT_HEADER      => 0,
            CURLOPT_RETURNTRANSFER  => 1,
            CURLOPT_USERAGENT   => $curlopt_useragent,
            CURLOPT_URL       => $url,
            CURLOPT_TIMEOUT         => 1,
            CURLOPT_REFERER         => 'http://' . $_SERVER['HTTP_HOST'],
        );
        
        curl_setopt_array($ch, $curl_opt);
        
        $content = curl_exec($ch);
        
        if (!is_null($curl_info)) {
            $curl_info = curl_getinfo($ch);
        }
        
        curl_close($ch);
        
        if ( preg_match('{<li>City : ([^<]*)</li>}i', $content, $regs) )  {
            $city = $regs[1];
        }
        if ( preg_match('{<li>State/Province : ([^<]*)</li>}i', $content, $regs) )  {
            $state = $regs[1];
        }

        if( $city!='' && $state!='' ){
          $location = $city . ', ' . $state;
          return $location;
        }else{
          return $default; 
        }
        
    }

// GET THE SOURCE CODE OF A WEBPAGE
public function display_sourcecode($url)
{
//$url = "http://blog.koonk.com";
//$source = display_sourcecode($url);
//echo $source;

$lines = file($url);
$output = "";
foreach ($lines as $line_num => $line) { 
    // loop thru each line and prepend line numbers
    $output.= "Line #<b>{$line_num}</b> : " . htmlspecialchars($line) . "<br>\n";
}

}

// SHOW NUMBER OF PEOPLE WHO HAVE LIKED YOUR FACEBOOK PAGE

public function fb_fan_count($facebook_name)
{
//$page = "koonktechnologies";
//$count = fb_fan_count($page);
//echo $count;
    $data = json_decode(file_get_contents("https://graph.facebook.com/".$facebook_name));
    $likes = $data->likes;
    return $likes;
}



// WHOIS QUERY Using the below function you would be able to get complete details regarding the owner of any domain.

public function whois_query($domain) {
 
//$domain = "http://www.blog.koonk.com";
//$result = whois_query($domain);
//print_r($result);
    // fix the domain name:
    $domain = strtolower(trim($domain));
    $domain = preg_replace('/^http:\/\//i', '', $domain);
    $domain = preg_replace('/^www\./i', '', $domain);
    $domain = explode('/', $domain);
    $domain = trim($domain[0]);
 
    // split the TLD from domain name
    $_domain = explode('.', $domain);
    $lst = count($_domain)-1;
    $ext = $_domain[$lst];
 
    // You find resources and lists 
    // like these on wikipedia: 
    //
    // http://de.wikipedia.org/wiki/Whois
    //
    $servers = array(
        "biz" => "whois.neulevel.biz",
        "com" => "whois.internic.net",
        "us" => "whois.nic.us",
        "coop" => "whois.nic.coop",
        "info" => "whois.nic.info",
        "name" => "whois.nic.name",
        "net" => "whois.internic.net",
        "gov" => "whois.nic.gov",
        "edu" => "whois.internic.net",
        "mil" => "rs.internic.net",
        "int" => "whois.iana.org",
        "ac" => "whois.nic.ac",
        "ae" => "whois.uaenic.ae",
        "at" => "whois.ripe.net",
        "au" => "whois.aunic.net",
        "be" => "whois.dns.be",
        "bg" => "whois.ripe.net",
        "br" => "whois.registro.br",
        "bz" => "whois.belizenic.bz",
        "ca" => "whois.cira.ca",
        "cc" => "whois.nic.cc",
        "ch" => "whois.nic.ch",
        "cl" => "whois.nic.cl",
        "cn" => "whois.cnnic.net.cn",
        "cz" => "whois.nic.cz",
        "de" => "whois.nic.de",
        "fr" => "whois.nic.fr",
        "hu" => "whois.nic.hu",
        "ie" => "whois.domainregistry.ie",
        "il" => "whois.isoc.org.il",
        "in" => "whois.ncst.ernet.in",
        "ir" => "whois.nic.ir",
        "mc" => "whois.ripe.net",
        "to" => "whois.tonic.to",
        "tv" => "whois.tv",
        "ru" => "whois.ripn.net",
        "org" => "whois.pir.org",
        "aero" => "whois.information.aero",
        "nl" => "whois.domain-registry.nl"
    );
 
    if (!isset($servers[$ext])){
        die('Error: No matching nic server found!');
    }
 
    $nic_server = $servers[$ext];
 
    $output = '';
 
    // connect to whois server:
    if ($conn = fsockopen ($nic_server, 43)) {
        fputs($conn, $domain."\r\n");
        while(!feof($conn)) {
            $output .= fgets($conn,128);
        }
        fclose($conn);
    }
    else { die('Error: Could not connect to ' . $nic_server . '!'); }
 
    return $output;
} 
// VALIDATE EMAIL ADDRESS

public function is_validemail($email)
{
//$email = "blog@koonk.com";
//$check = is_validemail($email);
//echo $check;
$check = 0;
if(filter_var($email,FILTER_VALIDATE_EMAIL))
{
$check = 1;
}
return $check;
}

// CONVERT URL IN A STRING TO HYPERLINKS

public function makeClickableLinks($text) 
{  

//$text = "This is my first post on http://blog.koonk.com";
//$text = makeClickableLinks($text);
//echo $text;
 $text = eregi_replace('(((f|ht){1}tp://)[-a-zA-Z0-9@:%_+.~#?&//=]+)',  
 '<a href="\1">\1</a>', $text);  
 $text = eregi_replace('([[:space:]()[{}])(www.[-a-zA-Z0-9@:%_+.~#?&//=]+)',  
 '\1<a href="http://\2">\2</a>', $text);  
 $text = eregi_replace('([_.0-9a-z-]+@([0-9a-z][0-9a-z-]+.)+[a-z]{2,3})',  
 '<a href="mailto:\1">\1</a>', $text);  
  
return $text;  
}

// BLOCK MULTIPLE IP FROM ACCESSING YOUR WEBSITE This snippet can come in handy if you want to restrict your website from a certain location or from certain users.

public function multipleIPblock() 
{ 
if ( !file_exists('blocked_ips.txt') ) {
 $deny_ips = array(
  '127.0.0.1',
  '192.168.1.1',
  '83.76.27.9',
  '192.168.1.163'
 );
} else {
 $deny_ips = file('blocked_ips.txt');
}
// read user ip adress:
$ip = isset($_SERVER['REMOTE_ADDR']) ? trim($_SERVER['REMOTE_ADDR']) : '';
 
// search current IP in $deny_ips array
if ( (array_search($ip, $deny_ips))!== FALSE ) {
 // address is blocked:
 echo 'Your IP adress ('.$ip.') was blocked!';
}
}

// FORCEFUL DOWNLOADING OF FILE - If you want certain files to download instead of opening in new tab, you can use the below snippet

public function force_download($file,$dir) 
{ 
    if ((isset($file))&&(file_exists($dir.$file))) { 
       header("Content-type: application/force-download"); 
       header('Content-Disposition: inline; filename="' . $dir.$file . '"'); 
       header("Content-Transfer-Encoding: Binary"); 
       header("Content-length: ".filesize($dir.$file)); 
       header('Content-Type: application/octet-stream'); 
       header('Content-Disposition: attachment; filename="' . $file . '"'); 
       readfile("$dir$file"); 
    } else { 
       echo "No file selected"; 
    } 

}

// CONVERT SECONDS TO DAYS, HOURS AND MINUTES

public function secsToStr($secs) 
{
//$seconds = "56789";
//$output = secsToStr($seconds);
//echo $output;

    if($secs>=86400){$days=floor($secs/86400);$secs=$secs%86400;$r=$days.' day';if($days<>1){$r.='s';}if($secs>0){$r.=', ';}}
    if($secs>=3600){$hours=floor($secs/3600);$secs=$secs%3600;$r.=$hours.' hour';if($hours<>1){$r.='s';}if($secs>0){$r.=', ';}}
    if($secs>=60){$minutes=floor($secs/60);$secs=$secs%60;$r.=$minutes.' minute';if($minutes<>1){$r.='s';}if($secs>0){$r.=', ';}}
    $r.=$secs.' second';if($secs<>1){$r.='s';}
    return $r;
}

// PHP SNIPPET FOR DIRECTORY LISTING -  Using the below PHP code snippet you would be able to list all files and folders in a directory - list_files("images/");

public function list_files($dir)
{
    if(is_dir($dir))
    {
        if($handle = opendir($dir))
        {
            while(($file = readdir($handle)) !== false)
            {
                if($file != "." && $file != ".." && $file != "Thumbs.db");
                {
                    echo '<a target="_blank" href="'.$dir.$file.'">'.$file.'</a><br>'."\n";
                }
            }
            closedir($handle);
        }
    }
}


// DETECT USER LANGUAGE -  Using the below PHP snippet you would be able to detect the language of your user�s browser.

public function get_client_language($availableLanguages, $default='en'){
    if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
        $langs=explode(',',$_SERVER['HTTP_ACCEPT_LANGUAGE']);

        foreach ($langs as $value){
            $choice=substr($value,0,2);
            if(in_array($choice, $availableLanguages)){
                return $choice;
            }
        }
    } 
    return $default;
}

// READING A CSV FILE

public function readCSV($csvFile){

//$csvFile = "test.csv";
//$csv = readCSV($csvFile);
//$a = csv[0][0]; // This will get value of Column 1 & Row 1

    $file_handle = fopen($csvFile, 'r');
    while (!feof($file_handle) ) {
        $line_of_text[] = fgetcsv($file_handle, 1024);
    }
    fclose($file_handle);
    return $line_of_text;
}


/// CREATING A CSV FILE FROM PHP ARRAY

public function generateCsv($data, $delimiter = ',', $enclosure = '"') {
// $data[0] = "apple";
//$data[1] = "oranges";
//generateCsv($data, $delimiter = ',', $enclosure = '"');

   $handle = fopen('php://temp', 'r+');
   foreach ($data as $line) {
           fputcsv($handle, $line, $delimiter, $enclosure);
   }
   rewind($handle);
   while (!feof($handle)) {
           $contents .= fread($handle, 8192);
   }
   fclose($handle);
   return $contents;
}

// GET CURRENT PAGE URL - This PHP snippet comes handy when post login you have to redirect user to the same page which he was browsing earlier.

public function current_url()
{
// echo "Currently you are on: ".current_url();

$url = "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
$validURL = str_replace("&", "&amp;", $url);
return validURL;
}

// GET LATEST TWEET FROM ANY TWITTER ACCOUNT

public function my_twitter($username) 
{
//$handle = "koonktech";
//my_twitter($handle);
    $no_of_tweets = 1;
    $feed = "http://search.twitter.com/search.atom?q=from:" . $username . "&rpp=" . $no_of_tweets;
    $xml = simplexml_load_file($feed);
    foreach($xml->children() as $child) {
        foreach ($child as $value) {
            if($value->getName() == "link") $link = $value['href'];
            if($value->getName() == "content") {
                $content = $value . "";
        echo '<p class="twit">'.$content.' <a class="twt" href="'.$link.'" title="">&nbsp; </a></p>';
            }   
        }
    }   
}

// NUMBER OF RETWEETS - Using this PHP snippet you can check the number of times your page URL was retweeted.

public function tweetCount($url) {

//$url = "http://blog.koonk.com";
//$count = tweetCount($url);
//return $count;

    $content = file_get_contents("http://api.tweetmeme.com/url_info?url=".$url);
    $element = new SimpleXmlElement($content);
    $retweets = $element->story->url_count;
    if($retweets){
        return $retweets;
    } else {
        return 0;
    }
}



public function _date_range_limit($start, $end, $adj, $a, $b, $result)
{
    if ($result[$a] < $start) {
        $result[$b] -= intval(($start - $result[$a] - 1) / $adj) + 1;
        $result[$a] += $adj * intval(($start - $result[$a] - 1) / $adj + 1);
    }

    if ($result[$a] >= $end) {
        $result[$b] += intval($result[$a] / $adj);
        $result[$a] -= $adj * intval($result[$a] / $adj);
    }

    return $result;
}

public function _date_range_limit_days($base, $result)
{
    $days_in_month_leap = array(31, 31, 29, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
    $days_in_month = array(31, 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);

    $this->_date_range_limit(1, 13, 12, "m", "y",$base);

    $year = $base["y"];
    $month = $base["m"];

    if (!$result["invert"]) {
        while ($result["d"] < 0) {
            $month--;
            if ($month < 1) {
                $month += 12;
                $year--;
            }

            $leapyear = $year % 400 == 0 || ($year % 100 != 0 && $year % 4 == 0);
            $days = $leapyear ? $days_in_month_leap[$month] : $days_in_month[$month];

            $result["d"] += $days;
            $result["m"]--;
        }
    } else {
        while ($result["d"] < 0) {
            $leapyear = $year % 400 == 0 || ($year % 100 != 0 && $year % 4 == 0);
            $days = $leapyear ? $days_in_month_leap[$month] : $days_in_month[$month];

            $result["d"] += $days;
            $result["m"]--;

            $month++;
            if ($month > 12) {
                $month -= 12;
                $year++;
            }
        }
    }

    return $result;
}

public function _date_normalize($base, $result)
{
    $result = $this->_date_range_limit(0, 60, 60, "s", "i", $result);
    $result = $this->_date_range_limit(0, 60, 60, "i", "h", $result);
    $result = $this->_date_range_limit(0, 24, 24, "h", "d", $result);
    $result = $this->_date_range_limit(0, 12, 12, "m", "y", $result);

    $result = $this->_date_range_limit_days($base,$result);

    $result = $this->_date_range_limit(0, 12, 12, "m", "y", $result);

    return $result;
}

/**
 * Accepts two unix timestamps.
 */
public function _date_diff($one, $two)
{
    $invert = false;
    if ($one > $two) {
        list($one, $two) = array($two, $one);
        $invert = true;
    }

    $key = array("y", "m", "d", "h", "i", "s");
    $a = array_combine($key, array_map("intval", explode(" ", date("Y m d H i s", $one))));
    $b = array_combine($key, array_map("intval", explode(" ", date("Y m d H i s", $two))));

    $result = array();
    $result["y"] = $b["y"] - $a["y"];
    $result["m"] = $b["m"] - $a["m"];
    $result["d"] = $b["d"] - $a["d"];
    $result["h"] = $b["h"] - $a["h"];
    $result["i"] = $b["i"] - $a["i"];
    $result["s"] = $b["s"] - $a["s"];
    $result["invert"] = $invert ? 1 : 0;
    $result["days"] = intval(abs(($one - $two)/86400));

    if ($invert) {
        $this->_date_normalize($a,$result);
    } else {
        $this->_date_normalize($b,$result);
    }

    return $result;
}



// SEARCH AND HIGHLIGHT KEYWORDS IN A STRING

public function highlighter_text($text, $words)
{
    $split_words = explode( " " , $words );
    foreach($split_words as $word)
    {
        $color = "#4285F4";
        $text = preg_replace("|($word)|Ui" ,
            "<span style=\"color:".$color.";\"><b>$1</b></span>" , $text );
    }
    return $text;
}


// DOWNLOAD IMAGE FROM URL

public function imagefromURL($image,$rename)
{

//$url = "http://koonk.com/images/logo.png";
//$rename = "koonk.png";
//imagefromURL($url,$rename);

$ch = curl_init($image);
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_BINARYTRANSFER,1);
$rawdata=curl_exec ($ch);
curl_close ($ch);

$fp = fopen("$rename",'w');
fwrite($fp, $rawdata); 
fclose($fp);
}

// CHECK IF A URL IS VALID

public function isvalidURL($url)
{
$check = 0;
if (filter_var($url, FILTER_VALIDATE_URL) !== false) {
  $check = 1;
}
return $check;
}

// GENERATE QR CODE

public function qr_code($data, $type = "TXT", $size ='150', $ec='L', $margin='0')  
{

// header("Content-type: image/png"); echo qr_code("http://phpexpertgroup.com", "URL");

     $types = array("URL" => "http://", "TEL" => "TEL:", "TXT"=>"", "EMAIL" => "MAILTO:");
    if(!in_array($type,array("URL", "TEL", "TXT", "EMAIL")))
    {
        $type = "TXT";
    }
    if (!preg_match('/^'.$types[$type].'/', $data))
    {
        $data = str_replace("\\", "", $types[$type]).$data;
    }
    $ch = curl_init();
    $data = urlencode($data);
    curl_setopt($ch, CURLOPT_URL, 'http://chart.apis.google.com/chart');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, 'chs='.$size.'x'.$size.'&cht=qr&chld='.$ec.'|'.$margin.'&chl='.$data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);

    curl_close($ch);
    return $response;
}


public function getDistanceBetweenPointsNew($latitude1, $longitude1, $latitude2, $longitude2) {
    $theta = $longitude1 - $longitude2;
    $miles = (sin(deg2rad($latitude1)) * sin(deg2rad($latitude2))) + (cos(deg2rad($latitude1)) * cos(deg2rad($latitude2)) * cos(deg2rad($theta)));
    $miles = acos($miles);
    $miles = rad2deg($miles);
    $miles = $miles * 60 * 1.1515;
    $feet = $miles * 5280;
    $yards = $feet / 3;
    $kilometers = $miles * 1.609344;
    $meters = $kilometers * 1000;
    return compact('miles','feet','yards','kilometers','meters'); 
    
//$point1 = array('lat' => 40.770623, 'long' => -73.964367);
//$point2 = array('lat' => 40.758224, 'long' => -73.917404);
//$distance = getDistanceBetweenPointsNew($point1['lat'], $point1['long'], $point2['lat'], $point2['long']);
//foreach ($distance as $unit => $value) {
    //echo $unit.': '.number_format($value,4).'<br />';}
}

// ADD TH,ST,ND OR RD TO THE END OF A NUMBER

public function ordinal($cdnl){ 
//$number = 10; echo ordinal($number); //output is 10th
    $test_c = abs($cdnl) % 10; 
    $ext = ((abs($cdnl) %100 < 21 && abs($cdnl) %100 > 4) ? 'th' 
            : (($test_c < 4) ? ($test_c < 3) ? ($test_c < 2) ? ($test_c < 1) 
            ? 'th' : 'st' : 'nd' : 'rd' : 'th')); 
    return $cdnl.$ext; 
}

// GET REMOTE FILE SIZE

public function remote_filesize($url, $user = "", $pw = "")
{
//$file = "http://koonk.com/images/logo.png";
//$size = remote_filesize($url);
//echo $size;
    ob_start();
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_NOBODY, 1);

    if(!empty($user) && !empty($pw))
    {
        $headers = array('Authorization: Basic ' .  base64_encode("$user:$pw"));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }

    $ok = curl_exec($ch);
    curl_close($ch);
    $head = ob_get_contents();
    ob_end_clean();

    $regex = '/Content-Length:\s([0-9].+?)\s/';
    $count = preg_match($regex, $head, $matches);

    return isset($matches[1]) ? $matches[1] : "unknown";
}

// YOUTUBE DOWNLOAD LINK GENERATOR -  Using the below PHP snippet you can offer your users the ability to download youtube videos.

public function str_between($string, $start, $end)
{ 
$string = " ".$string; $ini = strpos($string,$start); if ($ini == 0) return ""; $ini += strlen($start); $len = strpos($string,$end,$ini) - $ini; return substr($string,$ini,$len); }
function get_youtube_download_link(){
    $youtube_link = $_GET['youtube'];
    $youtube_page = file_get_contents($youtube_link);
    $v_id = str_between($youtube_page, "&video_id=", "&");
    $t_id = str_between($youtube_page, "&t=", "&");
    $flv_link = "http://www.youtube.com/get_video?video_id=$v_id&t=$t_id";
    $hq_flv_link = "http://www.youtube.com/get_video?video_id=$v_id&t=$t_id&fmt=6";
    $mp4_link = "http://www.youtube.com/get_video?video_id=$v_id&t=$t_id&fmt=18";
    $threegp_link = "http://www.youtube.com/get_video?video_id=$v_id&t=$t_id&fmt=17";
    echo "\t\tDownload (right-click &gt; save as)&#58;\n\t\t";
    echo "<a href=\"$flv_link\">FLV</a>\n\t\t";
    echo "<a href=\"$hq_flv_link\">HQ FLV (if available)</a>\n\t\t";
    echo "<a href=\"$mp4_link\">MP4</a>\n\t\t";
    echo "<a href=\"$threegp_link\">3GP</a><br><br>\n";
}

// FACEBOOK STYLE TIMESTAMP - Ever noticed how your post/comment time is shown on Facebook (x mins age, y hours ago etc). Below is a simple PHP snippet which you can use that will do the same thing.

public function nicetime($date)
{
//$date = "2015-07-05 03:45";
//$result = nicetime($date); // 2 days ago

    if(empty($date)) {
        return "No date provided";
    }
    
    $periods         = array("second", "minute", "hour", "day", "week", "month", "year", "decade");
    $lengths         = array("60","60","24","7","4.35","12","10");
    
    $now             = time();
    $unix_date         = strtotime($date);
    
       // check validity of date
    if(empty($unix_date)) {    
        return "Bad date";
    }

    // is it future date or past date
    if($now > $unix_date) {    
        $difference     = $now - $unix_date;
        $tense         = "ago";
        
    } else {
        $difference     = $unix_date - $now;
        $tense         = "from now";
    }
    
    for($j = 0; $difference >= $lengths[$j] && $j < count($lengths)-1; $j++) {
        $difference /= $lengths[$j];
    }
    
    $difference = round($difference);
    
    if($difference != 1) {
        $periods[$j].= "s";
    }
    
    return "$difference $periods[$j] {$tense}";
}


// Display source code of any webpage


public function scodeofanywPage($url)
{
$lines = file($url);
foreach ($lines as $line_num => $line) { 
    // loop thru each line and prepend line numbers
    echo "Line #<b>{$line_num}</b> : " . htmlspecialchars($line) . "<br>\n";
}
}

// Base64 Encode and Decode String in PHP
public function base64url_encode($plainText) {
    $base64 = base64_encode($plainText);
    $base64url = strtr($base64, '+/=', '-_,');
    return $base64url;
}

public function base64url_decode($plainText) {
    $base64url = strtr($plainText, '-_,', '+/=');
    $base64 = base64_decode($base64url);
    return $base64;
} 

// Date format validation in PHP

public function checkDateFormat($date)
{
    //match the format of the date
    if (preg_match ("/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/", $date, $parts))
    {
        //check weather the date is valid of not
        if(checkdate($parts[2],$parts[3],$parts[1]))
            return true;
        else
        return false;
    }
    else
        return false;
}


public  function paypalPayment($paypaylurl,$paypal_id,$productData,$amount,$currency,$success_return,$cancel_return,$product_id) 
{
if($paypaylurl!='' && $paypal_id!='')
{
echo '<div style="margin-left: 38%"><img src="ajax-loader.gif"/><img src="processing_animation.gif"/></div>
<form name="myform" action="'.$paypaylurl.'" method="post" target="_top">
<input type="hidden" name="cmd" value="_xclick">
<input type="hidden" name="cancel_return" value="'.$cancel_return.'">
<input type="hidden" name="return" value="'.$success_return.'">
<input type="hidden" name="business" value="'.$paypal_id.'">
<input type="hidden" name="lc" value="C2">
<input type="hidden" name="item_name" value="'.$productData.'">
<input type="hidden" name="item_number" value="'.$product_id.'">
<input type="hidden" name="amount" value="'.$amount.'">
<input type="hidden" name="currency_code" value="'.$currency.'">
<input type="hidden" name="button_subtype" value="services">
<input type="hidden" name="no_note" value="0">
</form><script type="text/javascript">document.myform.submit();</script>';
}
else
{
echo "Paypal URL & Paypal email is empty";
}
}

public function logout()
   {
        session_destroy();
        unset($_SESSION['phpexpert_session']);
        return true;
   }
  
 public function is_loggedin()
   {
      if(isset($_SESSION['phpexpert_session']))
      {
         return true;
      }
   }  
   
 public function cleanInput($input) {

  $search = array(
    '@<script[^>]*?>.*?</script>@si',   // Strip out javascript
    '@<[\/\!]*?[^<>]*?>@si',            // Strip out HTML tags
    '@<style[^>]*?>.*?</style>@siU',    // Strip style tags properly
    '@<![\s\S]*?--[ \t\n\r]*>@'         // Strip multi-line comments
  );

    $output = preg_replace($search, '', $input);
    return $output;
  }  


 public function auto_copyright($year = 'auto'){
if(intval($year) == 'auto'){ $year = date('Y'); }
if(intval($year) == date('Y')){ echo intval($year); } 
if(intval($year) < date('Y')){ echo intval($year) . ' - ' . date('Y'); }
if(intval($year) > date('Y')){ echo date('Y'); } 
 }
 
 
 
 public function br2newline( $input ) {
     $out = str_replace( "<br>", "\n", $input );
     $out = str_replace( "<br/>", "\n", $out );
     $out = str_replace( "<br />", "\n", $out );
     $out = str_replace( "<BR>", "\n", $out );
     $out = str_replace( "<BR/>", "\n", $out );
     $out = str_replace( "<BR />", "\n", $out );
     return $out;
}

public function br2nl( $input ) {
 return preg_replace('/<br(\s+)?\/?>/i', "\n", $input);
} 


public function word_wrapper($text,$minWords = 3) {
       $return = $text;
       $arr = explode(' ',$text);
       if(count($arr) >= $minWords) {
               $arr[count($arr) - 2].= '&nbsp;'.$arr[count($arr) - 1];
               array_pop($arr);
               $return = implode(' ',$arr);
       }
       return $return;
}


public function hex2rgb( $colour ) {
        if ( $colour[0] == '#' ) {
                $colour = substr( $colour, 1 );
        }
        if ( strlen( $colour ) == 6 ) {
                list( $r, $g, $b ) = array( $colour[0] . $colour[1], $colour[2] . $colour[3], $colour[4] . $colour[5] );
        } elseif ( strlen( $colour ) == 3 ) {
                list( $r, $g, $b ) = array( $colour[0] . $colour[0], $colour[1] . $colour[1], $colour[2] . $colour[2] );
        } else {
                return false;
        }
        $r = hexdec( $r );
        $g = hexdec( $g );
        $b = hexdec( $b );
        return array( 'red' => $r, 'green' => $g, 'blue' => $b );
}
//Get Tomorrow Date

public function get_tomorrow_date() {
 $tomorrow = mktime(0,0,0,date("m"),date("d")+1,date("Y"));
 return date("Y-m-d", $tomorrow);
}

// Get Today�s Date
public function get_today_date() {
 $today=date("Y-m-d");
 return $today;
}

// Get Last Day Date
public function get_last_date() {
 $tomorrow = mktime(0,0,0,date("m"),date("d")-1,date("Y"));
 return date("Y-m-d", $tomorrow);
}

// Detect Local Host
public function is_localhost() {
 $localhost_ids = array('localhost', '127.0.0.1');
 if(in_array($_SERVER['HTTP_HOST'],$localhost_ids)){
    // not valid
    return 1;
 }
 }
// Get Day Name By Date
public function get_day_name_by_date($day,$month, $year) {

 $day_name = date("l", mktime(0,0,0,$month,$day,$year));
 return $day_name;

}
// Get Month Name By Month Value

public function Month($NumMonth) {

   switch($NumMonth) {
      Case "01":
      return "January";
      break;

      Case "02":
      return "February";
      break;

      Case "03":
      return "March";
      break;

      Case "04":
      return "April";
      break;

      Case "05":
      return "May";
      break;

      Case "06":
      return "June";
      break;

      Case "07":
      return "July";
      break;

      Case "08":
      return "August";
      break;

      Case "09":
      return "September";
      break;

      Case "10":
      return "October";
      break;

      Case "11":
      return "November";
      break;

      Case "12":
      return "December";
      break;
    }

}

// Get Extension of a file/image

public function getExtension($str) {
    $i = strrpos($str,".");
    if (!$i) {
       return "";
    }
    $l = strlen($str) - $i;
    $ext = substr($str,$i+1,$l);
    return $ext;
}

// Remove Spacial Characters from a string

public function removeSpacialCharacters($string="") {

   if (preg_match('/[^\w\d_ -]/si', $string)) {
     return preg_replace('/[^a-zA-Z0-9_ -]/s', '', $string);
   } else {
     return preg_replace('/\s/', ' ', $string);
     }
   }


 public function alertBox($msg, $icon = "", $type = "") {
        return "<div class=\"alertMsg $type\"> <div class=\"msgIcon pull-left\">$icon</div>     $msg
                    <a class=\"msgClose\" title=\"Close\" href=\"#\"><i class=\"fa fa-times\"></i></a>
                </div>";
    }
    
    
public function array_splice_assoc(&$input, $offset, $length=0, $replacement=array()) {
  $count = count($input);

  if (is_string($offset)) {
    $offset = array_search($offset, array_keys($input));
    if ($offset === FALSE) {
      $offset = $count;
    }
  }
  elseif ($offset < 0) {
    $offset = max($count + $offset, 0);
  }

  $pre = array_slice($input, 0, $offset, TRUE);
  $post = array_slice($input, $offset + $length, NULL, TRUE);
  $removed = array_slice($input, $offset, $offset + $length, TRUE);
  $input = $pre + $replacement + $post;
  return $removed;
}

public function count_capitals($str) {
  return strlen(preg_replace('![^A-Z]+!', '', $str));
}   



}

// END class
?>