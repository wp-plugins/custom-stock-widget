<?php

define('STOCK_PLUGIN_CACHE', true, false); //flag for whether this file was already included anywhere
//NOTE: using the common files between the 2 plugins can lead to errors refrencing whichever plugin was initialized first
//Notice: Undefined index: XXXXXXX in /home/rele17lemurs/fashion.17lemurs.com/wp-content/plugins/custom-stock-ticker/stock_plugin_cache.php


//NOTE: based on comments from here: http://php.net/manual/en/function.str-getcsv.php

if (!function_exists('str_getcsv')) { //Added for compatability with php versions less than 5.3
    function str_getcsv($input, $delimiter = ',', $enclosure = '"', $escape = '\\') { 
        if (is_string($input) && !empty($input)) { 
            $output = array(); 
            if (preg_match("/{$escape}{$enclosure}/", $line)) { 
                while ($strlen = strlen($line)) {
                    $pos_delimiter       = strpos($line, $delimiter); 
                    $pos_enclosure_start = strpos($line, $enclosure);
                    
                    //when the next enclosure is before the next delimiter, means this next substr will be in an enclosure
                    if ( is_int($pos_delimiter) && is_int($pos_enclosure_start) && ($pos_enclosure_start < $pos_delimiter) ) { 
                                                
                        $pos_enclosure_end = strpos($line, $enclosure, 1); //find the second occurrance of the enclosure char, and take the char before that as end of substr
                        $output[]          = substr($line, 1, $pos_enclosure_end - 1); //take substring between the enclosure characters
                        $offset            = $pos_enclosure_end + 3; //NOTE: this assumes delimiter and enclosure are 1 char each
                    } 
                    else { //next is not in an enclosure
                        if (empty($pos_delimiter) && empty($pos_enclosure_start)) { //if we found no delimiters and no enclosures
                            $output[] = $line;
                            $offset   = strlen($line); 
                        } 
                        //NOTE: if no delimiters but there is an enclosure, we grab the enclosure chars too
                        else { 
                            $output[] = substr($line, 0, $pos_delimiter); //this assumes pos_delimiter exists but pos_enclosure does not
                            $offset = ( !empty($pos_enclosure_start) && ($pos_enclosure_start < $pos_delimiter) )  ? $pos_enclosure_start : $pos_delimiter + 1; 
                        } 
                    } 
                    $line = substr($line, $offset); //cut off the first part of the line and continue
                }
            } 
            else { 
                $line = preg_split("/{$delimiter}/", $line); 
                //Validating against pesky extra line breaks creating false rows. 
                if (is_array($line) && !empty($line[0])) { 
                    $output = $line; 
                }  
            } 
            return $output; 
        } 
        else { 
            return false; 
        } 
    } //end function def
} //end function exists



//The cache returns a string of the data elements separated by commas. This funciton
//Splits the string by the commas, removes any single quote marks('), and returns
//an array of the elements with the appropriate keys. 
function stock_plugin_proccess_data($data_string) {
    $key_list = array(
        'stock_sym','stock_name','last_val','change_val','change_percent',
        'market_cap','fifty_week_range','pe_ratio','earning_per_share','revenue'
        );
    $data      = str_getcsv($data_string, ",", "'"); //TODO: there may be an error case here from wordpress.org error reporting
    $data_list = array_combine($key_list, $data);
    return $data_list;
}

function stock_plugin_url_get_json($Url) {
    if (!function_exists('curl_init')){
        die('CURL is not installed!');
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $Url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TRANSFERTEXT, false);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1); //setting some timeouts to make sure any problems here don't hang the client.
    curl_setopt($ch, CURLOPT_TIMEOUT, 2);
    $output = curl_exec($ch);
    curl_close($ch);
    if ($output === FALSE) $output = '{"_cache_status":"curl failed"}'; //probably timeout
    return json_decode($output, true); //2nd parameter is to return this as an assoc array instead of a stdObject
}


//Grabs the data for the list of stocks and returns an array containing the required data as well as a
//list of invalid stocks.
//Returns:
//array(
//  'invalid_stocks'=> array('YHAAO', 'AAQ#'), 
//  'valid_data' => array('YHOO'=>array('stock_sym'=>'YHOO'), 'GOOG'=>array()))

function stock_plugin_get_data($stock_list) {
    
    $valid_stock_data   = array();
    $invalid_stock_data = array();
    if (!empty($stock_list)) {
    
        //loops three times to ensure that the request is proccessed through a lock.
        for ($i=0; $i<3; $i++) {
            //creates the url for the stocks list. If a single stock is given, avoids the implode function.
            if (is_array($stock_list)) {
                $stock_list_string = implode('|',$stock_list);
            } else {
                $stock_list_string = $stock_list;
                $stock_list        = explode('|', $stock_list); //for later we need it to be an array
            }
            $stock_list_string = str_replace(' ', '', $stock_list_string);
            $url = "http://websking.com/ticker?cmd=get&ticker={$stock_list_string}&type=stock&api=v2";
            $response_list = stock_plugin_url_get_json($url);
            
            //if the cache is locked, start the loop again after half a second.
            $tmp = $response_list['_cache_status'];
            if ($tmp == "Cache Locked" || $tmp == "Feed Error" || $tmp == "All Failed" || $tmp == "curl failed") {
                sleep(.5);
                continue;
            }
            //This loop extracts the data from the response and determines what to do with that data
            //Possible cases are: 
            //  The data is okay and is added to the valid stocks list.
            //  The stock is invalid and is added to the invalid stocks list.
            //  The stock is valid but could not be obtained. Added to the retry_stocks list.
            $retry_stocks = array();
            foreach ($stock_list as $key) {
                if ($key == '') {
                    continue;
                }
                $data = $response_list[$key];
                if ($data == 'tickerDNE' || $data == 'tickerInvalid' || is_null($data) || $data == '') {
                    $invalid_stock_data[] = $key;
                } elseif ($data == 'Cache Error') {
                    $retry_stocks[] = $key;
                } else {
                    //if the data exists and didnt result in a cache error, it is added to the 
                    //valid stock list.
                    $data_list = stock_plugin_proccess_data($data);
                    $valid_stock_data[$key] = $data_list;
                }
            }
            //if none of the stocks needed to be retried, the loop breaks and the function returns.
            //otherwise, the stock_list is changed to the list of stocks that need to be fetched again
            //and the loop restarts.
            if (empty($retry_stocks)) {
                break;
            } else {
                $stock_list = $retry_stocks;
            }
        }
    }
    return array('invalid_stocks' => $invalid_stock_data, 'valid_stocks' => $valid_stock_data );
}


?>
