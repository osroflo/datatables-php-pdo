<?php

/**
 * Script:    TableData server-side script for PHP PDO
 * Copyright: 2016 - Oscar Romero.
 *
 * The code samples from these developers were very useful to learn and build my own class:
 *  - 2016 - CoderExample https://github.com/coderexample/datatable_example/blob/master/demo1/employee-grid-data.php
 *  - 2012 - John Becker, Beckersoft, Inc.
 *  - 2010 - Allan Jardine
 */

class TableData {

    private $conn;
    private $raw_query = array();

    private $select = "";
    private $from = "";
    private $where = "";
    private $paging = "";
    private $sorting = "";
    private $params = array();
    private $raw_sql = null;

    private $search_value = null;


    private $columns = array();
    private $searchable = array();
    private $total_rows;
    private $limit = 0;
    private $offset = 0;

    /**
     * Constructor
     *
     * @param Array $query The sql query definition in the following way:
     *                     $query["from"] = " FROM cc.user ";
     *                     $query["where"] = " WHERE last_activity between  :start_date AND  :end_date ";
     *                     $query["params"] = array(
     *                                         'start_date'=>'2015-03-18',
     *                                          'end_date'=>'2016-03-18'
     *                                         );
     *
     *                     NOTE: The select statement is build automatically taking the columns from the
     *                           the Datatables client side.
     *
     *                           $('#example').dataTable( {
     *                                 processing: true,
     *                                 serverSide: true,
     *
     *                                 columns: [
     *                                     {
     *                                         "name": "username",
     *                                         "searchable":true,
     *                                         "orderable":true
     *                                     },
     *                                     {
     *                                         "name": "last_activity",
     *                                         "searchable":false,
     *                                         "orderable":true
     *                                     },
     *                                     {
     *                                         "name": "user_id",
     *                                         "searchable":false,
     *                                         "orderable":false
     *                                     }
     *                                  ]
     */
    public function __construct(Array $query) {

        try {
            $host       = 'localhost';
            $database   = 'database';
            $user       = 'postgres';
            $passwd     = '123';

            // Conn
            $this->conn = new PDO('pgsql:host='.$host.';dbname='.$database, $user, $passwd);

            $this->raw_query = $query;

            // Set columns
            $this->setColumns();

            // Query
            $this->setQueryParams();
            $this->setQuerySelect();
            $this->setQueryFrom();
            $this->setQueryWhere();
            $this->setQueryPaging();
            $this->setQuerySorting();

            // Set total rows
            $this->setTotalRows();


        } catch (PDOException $e) {
            error_log("Failed to connect to database: ".$e->getMessage());
        }

    }

    private function setQuerySelect()
    {
        if(!isset($this->query['select']) && count($this->getColumns()) <= 0 )
            throw new Exception(basename(__FILE__, '.php') . " error: " . "The SELECT statement was not found! Be sure to pass the columns array or set the select value in the query array");

        $this->select = isset($this->query['select']) ? $this->query["select"] : " SELECT " . implode(" , ", $this->getColumns());
    }

    private function setQueryFrom()
    {
        $this->from = self::required($this->raw_query, "from");
    }

    private function setQueryWhere()
    {
        // Get where condition passed during instantiation
        $where = self::optional($this->raw_query, "where");

        // Check if a search value is passed
        $this->search_value = self::optional($_REQUEST['search'], "value");

        if( $this->search_value ) {

            $searchable_columns_statements = array();

            // Get searchable fields from data tables plugin
            foreach ($this->searchable as $column) {
                $searchable_columns_statements[] = " $column ilike :$column ";
                $this->params[$column] = trim("%" . $this->search_value . "%");
            }

            if( count($searchable_columns_statements) > 0 ){
                $searchable_where = implode(" OR ", $searchable_columns_statements);
                $this->where = is_null($where) ? " WHERE $searchable_where " :  " $where AND (" . implode(" OR ", $searchable_columns_statements) . ")";
            }

        }
        else {
            $this->where = $where;
        }

    }

    private function setQueryPaging()
    {
        $this->limit = self::optional($_REQUEST, 'length', 10);
        $this->offset = self::optional($_REQUEST, 'start', 0);
        $this->paging = " LIMIT {$this->limit} OFFSET {$this->offset} ";
    }

    private function setQuerySorting()
    {
        // Get column and direction (order)
        $items = self::optional($_REQUEST, 'order', array());

        if( count($items) > 0 ) {
            $fields = array();

            foreach ($items as $item) {
                $column_index = $item['column'];
                $field_name = $this->columns[$column_index];
                $fields[] = $field_name . " " . $item['dir'];
            }
            $str = implode(',', $fields);
            $this->sorting = " ORDER BY $str";
        }

    }

    // Params are useful to be used in the where statement to filter search base in some values
    private function setQueryParams()
    {
        $this->params = self::optional($this->raw_query, 'params', array());
    }

    // Set columns property value from the request array passed by data table client api
    private function setColumns()
    {
        $cols = self::optional($_REQUEST, 'columns', array());

        foreach ($cols as $column) {
            $columns[] = $column['name'];

            if( filter_var($column['searchable'],  FILTER_VALIDATE_BOOLEAN) ) {
                $searchable[] = $column['name'];
            }
        }

        $this->columns = $columns;
        $this->searchable = $searchable;
    }

    private function getColumns()
    {
        return $this->columns;
    }


    private function setTotalRows()
    {

        // Get the raw variables passed
        $where = self::optional($this->raw_query, "where");
        $params = self::optional($this->raw_query, "params", array());

        $sql = "SELECT COUNT(*) {$this->from} $where";

        $stmt  = $this->conn->prepare($sql);
        $stmt->execute($params);
        $this->total_rows = $stmt->fetchColumn();
    }

    private function getTotalRowsFiltered()
    {
        $sql = "SELECT COUNT(*) {$this->from} {$this->where}";
        $stmt  = $this->conn->prepare($sql);
        $stmt->execute($this->params);

        return $stmt->fetchColumn();
    }

    public function getTotalRows()
    {

        return $this->total_rows;
    }


    public function get() {

        // Data processing
        $results = $this->search();
        $data = array();

        //  Get rows
        foreach ($results as $row) {
            $nestedData = array();

            // Get columns
            foreach ($this->columns as $column) {
                $nestedData[] = $row[$column];
            }

            $data[] = $nestedData;
        }

        // Total rows filtered
        $total_rows_filtered = ($this->search_value) ? $this->getTotalRowsFiltered() : null;

        // Data
        $json_data = array(
            /**
             * For every request/draw by clientside , they send a number as a parameter, when they recieve
             * a response/data they first check the draw number, so we are sending same number in draw.
             */
            "draw" => intval( self::optional($_REQUEST, 'draw', 0)),
            "recordsTotal" => intval( $this->getTotalRows() ), //total number of records before filtering
            "recordsFiltered" => intval( ($total_rows_filtered) ? $total_rows_filtered : $this->getTotalRows() ), //total number of records after searching
            "data" => $data
            );

        echo json_encode($json_data);  //send data as json format


    }

    public function search()
    {
        $sql =  $this->select . $this->from . $this->where . $this->sorting . $this->paging;

        $stmt  = $this->conn->prepare($sql);
        $stmt->execute($this->params);
        $this->raw_sql = $sql;

        return $stmt->fetchAll();
    }

    public function getRawSql()
    {

        $sql = $this->raw_sql;
        foreach ($this->params as $key => $value) {
            $sql = str_replace(":$key", $value, $sql);
        }

        return $sql;
    }

    public static function required ($data, $key = null)
    {
        try {

            if (!isset($data[$key]))
                throw new Exception("Missing required parameter '{$key}' in request");
            else
                return $data[$key];

        } catch (Exception $e) {
            header("Status: 500 Server Error");
            echo $e->getMessage();
        }
    }

    public static function optional ($data, $key, $default_value = null)
    {
        if (!isset($data[$key]))
            return $default_value;
        else
            return $data[$key];
    }

}
