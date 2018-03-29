# Datatables PHP PDO (Server Side Script - Sample)

This is a brief sample using the [DataTables plugin](https://datatables.net) in a server side scripting with PHP-PDO.

I decided to start with this code sample because all similar samples code that I found were mixing the SQL query with the server side logic to return results. My main goal was to produce some kind of widget that passing a query can be capable to return results, paginate, sort and filter. Also, the most important thing: easy to integrate. This code needs a lot of improvement but is a good starting.

Hopefully this can be useful for someone.





## How to use


1 - Setup front-end

HTML
```
<table id="example" class="display" cellspacing="0" width="100%">
        <thead>
            <tr>
                <th>username</th>
                <th>last_activity</th>
                <th>user_id</th>
            </tr>
        </thead>
    </table>
```





JS
```
<script>
        $(document).ready(function() {
            var table = $('#example').dataTable( {
                processing: true,
                serverSide: true,

                columns: [
                    {
                        "name": "username",
                        "searchable":true,
                        "orderable":true
                    },
                    {
                        "name": "last_activity",
                        "searchable":false,
                        "orderable":true
                    },
                    {
                        "name": "user_id",
                        "searchable":false,
                        "orderable":false
                    }
                ],

                "language": {
                    "emptyTable": "No matching records found for "
                },

                ajax: {
                    url:'data.php',
                    type:'GET',

                    /* Error handling */
                    error: function(xhr, error, thrown){
                        $(".example-grid-error").html("");
                        $("#example").append('<tbody class="example-grid-error"><tr><th colspan="3">No data found in the server. Error: ' + xhr.responseText + ' </th></tr></tbody>');
                        $("#example-grid_processing").css("display","none");
                    }
                }


            } );


        } );

```


2 - Setup the backend
```
<?php
    require_once('TableData.php');


    // query declaration
    $query = array(
        "from" => " FROM table_name",
         "where" => " WHERE last_activity between  :start_date AND  :end_date ",
         "params" => array(
                 'start_date'=>'2015-03-18',
                 'end_date'=>'2016-03-18'
         )
    );

    // instantiation
    $dataTable = new TableData($query);
    $dataTable->get();

    // show the raw query
    error_log($dataTable->getRawSql());
```


## For more information
Please check demo.html

## To Do
Include composer to be able to require it as a dependency.


## License
Free
