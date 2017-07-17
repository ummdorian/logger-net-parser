<?php

    class loggerNetParser{

        public $inputString = '';
        public $collumnMapping = array();
        public $parsedDataRows = array();
        public $parsedMetaRows = array();
        public $generatedJson  = array();

        // Initialize
        function __construct($input = ''){
            $this->inputString = $input;
        }


        // Populate data variables from input string
        function parseData($numMetaRows = 4){

            // explode input string on line breaks
            $rows = preg_split("/\\r\\n|\\r|\\n/" , $this->inputString);

            // get just meta rows
            $metaRows = array_slice($rows,0,$numMetaRows);
            // split meta rows
            foreach($metaRows as $metaRow){
                $this->parsedMetaRows[] = str_getcsv($metaRow);
            }

            // get just data rows
            $dataRows = array_slice($rows,$numMetaRows);
            foreach($dataRows as $dataRow){
                // last row is often blank, so don't add that
                if(trim($dataRow) != ''){
                    $this->parsedDataRows[] = str_getcsv($dataRow);
                }
            }

        }


        // Optionally provide the index of a meta row to use as keys for the returned json rows
        function generateJson($metaRowIndexForKeys = ''){
            if($metaRowIndexForKeys != ''){
                $preJsonArray = array();
                foreach($this->parsedDataRows as $parsedDataRow){
                    $preJsonArray[] = array_combine($this->parsedMetaRows[$metaRowIndexForKeys],$parsedDataRow);
                }
                return json_encode($preJsonArray);
            }
            else{
                return json_encode($this->parsedDataRows);
            }
        }


        // Generate json for just one collumn of the data, usefull for javascript charts
        // range is an array with lower and upper limit indexes
        function generateCollumnJson($collumnIndex,$range=''){
            // if range is set and we can use it
            if(
                is_array($range)
                && isset($range[1])
                && count($this->parsedDataRows)-1 >= $range[1]
                && isset($range[0])
                && $range[0] >= 0
                && $range[0] <= $range[1]
                && is_numeric($collumnIndex)
            ){
                return json_encode(array_slice(array_column($this->parsedDataRows,$collumnIndex),$range[0],$range[1]-$range[0]));
            }
            // return the whole collumn if we've got it
            elseif(count($this->parsedDataRows) && is_numeric($collumnIndex)){
                return json_encode(array_column($this->parsedDataRows,$collumnIndex) );
            }
        }

        // Same as generateCollumnJson except just returns array
        function getCollumnArray($collumnIndex,$range=''){
            // if range is set and we can use it
            if(
                is_array($range)
                && isset($range[1])
                && count($this->parsedDataRows)-1 >= $range[1]
                && isset($range[0])
                && $range[0] >= 0
                && $range[0] <= $range[1]
                && is_numeric($collumnIndex)
            ){
                return array_slice(array_column($this->parsedDataRows,$collumnIndex),$range[0],$range[1]-$range[0]);
            }
            // return the whole collumn if we've got it
            elseif(count($this->parsedDataRows) && is_numeric($collumnIndex)){
                return array_column($this->parsedDataRows,$collumnIndex);
            }
        }


        // Generate json for a collumn and format it
        function generateFormattedTimeCollumnJson($collumnIndex=0,$timeFormat='g:ia',$timeInputFormat='Y-m-d H:i:s',$range=''){
            if(count($this->parsedDataRows) && is_numeric($collumnIndex)){
                $thisCollumn = array_column($this->parsedDataRows,$collumnIndex);

                foreach($thisCollumn as $timeIndex => $timeString){
                    if(DateTime::createFromFormat($timeInputFormat, $timeString)){
                        $thisCollumn[$timeIndex] = date($timeFormat,DateTime::createFromFormat($timeInputFormat, $timeString)->getTimestamp());
                    }
                }

                // if a range is set return just that portion
                if(is_array($range)){
                    return json_encode(array_slice($thisCollumn,$range[0],$range[1]-$range[0]));
                }
                // return entire formatted collumn
                else{
                    return json_encode($thisCollumn);
                }
            }
        }


        // Returns max value in collumn
        function getCollumnMax($collumnIndex){
            if(count($this->parsedDataRows) && is_numeric($collumnIndex)){
                return max(array_column($this->parsedDataRows,$collumnIndex));
            }
        }


        // Returns range of row indexes from last decent
        function getIndexRangeOfLastWinchDecent($timeCollumnIndex=0,$timeInputFormat='Y-m-d H:i:s'){

            // if the we're in a position to make this calculation
            if(count($this->parsedDataRows) && is_numeric($timeCollumnIndex)){
                // get time collumn
                $timeCollumn = array_column($this->parsedDataRows,$timeCollumnIndex);

                // figure out the last decent range based on being in the same hour as the last entry
                if(DateTime::createFromFormat($timeInputFormat, $timeCollumn[count($timeCollumn)-1] )){
                    $hourOfLastDecent = date('H',DateTime::createFromFormat($timeInputFormat, $timeCollumn[count($timeCollumn)-1])->getTimestamp());

                    // loop over entries till we find one that's in a different hour
                    for($i = count($timeCollumn)-1; $i >= 0; $i--){
                        if(date('H',DateTime::createFromFormat($timeInputFormat, $timeCollumn[$i])->getTimestamp()) != $hourOfLastDecent){
                            return array($i+1,count($timeCollumn)-1);
                        }
                    }

                    // if all entries were in the same hour then return the range of the whole thing
                    return array($i+1,count($timeCollumn)-1);
                }
            }
        }



    }

?>