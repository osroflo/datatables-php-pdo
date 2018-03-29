<?php
    require_once('TableData.php');

    /**
     * This is a sample to show how to use the TableData class
     */

    // query declaration
    $query = array(
        "from" => " FROM table_name",
        "where" => " WHERE date between  :start_date AND  :end_date ",
        "params" => array(
            'start_date'=>'2015-03-18',
            'end_date'=>'2016-03-18'
        )
    );

    // instantiation
    $dataTable = new TableData($query);
    $dataTable->get();

    // show the raw query for debug purposes
    error_log($dataTable->getRawSql());
