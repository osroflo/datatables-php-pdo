<?php
    require_once('TableData.php');


    // Query declaration
    $query = array(
        "from" => " FROM cc.user"
        // "where" => " WHERE last_activity between  :start_date AND  :end_date ",
        // "params" => array(
        //         'start_date'=>'2015-03-18',
        //         'end_date'=>'2016-03-18'
        // )
    );

    // Instantiation
    $dataTable = new TableData($query);
    $dataTable->get();

    // Show the raw query
    error_log($dataTable->getRawSql());
