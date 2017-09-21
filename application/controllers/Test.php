<?php

ini_set("auto_detect_line_endings", true);

Class Test extends CI_Controller {

    public function index() {

        //Get System Date 
        $current_date = Date('Y-m-d', time());
        //Get Previous Date
        $three_days_ago = date('Y-m-d', strtotime('-2 days', time()));

        $query_parameter = 'start_date=' . $three_days_ago . '&end_date=' . $current_date . '&detailed=true&api_key=N7LkblDsc5aen05FJqBQ8wU4qSdmsftwJagVK7UD';

        //echo $query_parameter;die;
        // Set URL
        $url = "https://api.nasa.gov/neo/rest/v1/feed";
        //$response = file_get_contents($url);

        $res = file_get_contents($url . '?' . $query_parameter);

        $result = json_decode($res);

        $getDates = ($this->getDatesFromRange($three_days_ago, $current_date));

        foreach ($getDates as $value) {
            $getDataDateWise = $result->near_earth_objects->$value;
            //echo '<pre>';print_r($getDataDateWise);
            foreach ($getDataDateWise as $val) {
                $checkDateExists = $this->db->query("SELECT * FROM `mcmakler` where date = '" . $value . "' and reference = '" . $val->neo_reference_id . "'")->result_array();
                if (empty($checkDateExists)) {
                    $this->db->query("INSERT INTO `mcmakler` (date , reference , name , speed , is_hazardous) VALUES "
                            . "  ('$value' , '" . $val->neo_reference_id . "' , '" . $val->name . "' , '" . $val->close_approach_data[0]->relative_velocity->kilometers_per_hour . "' , "
                            . " '" . $val->is_potentially_hazardous_asteroid . "') ");
                }
            }
        }

        //

        echo 'Successfully Data Saved';
        die();
    }

    function getDatesFromRange($start, $end, $format = 'Y-m-d') {
        $array = array();
        $interval = new DateInterval('P1D');

        $realEnd = new DateTime($end);
        $realEnd->add($interval);

        $period = new DatePeriod(new DateTime($start), $interval, $realEnd);

        foreach ($period as $date) {
            $array[] = $date->format($format);
        }

        return $array;
    }

    public function hazardous() {

        $current_date = Date('Y-m-d', time());
        //Get Previous Date
        $three_days_ago = date('Y-m-d', strtotime('-2 days', time()));

        $getData = $this->db->query("SELECT * FROM `mcmakler` where is_hazardous = 1 and "
                        . " ( date between '$three_days_ago' and '$current_date')")->result_array();
        //echo '<pre>';print_r($getData);die;
        if (!empty($getData)) {
            $dataInJson = json_encode(array_values($getData));
        } else {
            $dataInJson = null;
        }

        echo $dataInJson;
    }

    public function fastest() {

        $current_date = Date('Y-m-d', time());
        //Get Previous Date
        $three_days_ago = date('Y-m-d', strtotime('-2 days', time()));

        if (isset($_GET['hazardous'])) {

            $hazardous = $_GET['hazardous'];

            
                if ($hazardous) {
                    $hazardous = 1;
                    $query = "select max(cast(speed AS DECIMAL(20,5)))  as speed , case when is_hazardous = '' then false else true END is_hazardous from mcmakler where "
                            . " ( date between '$three_days_ago' and '$current_date') and is_hazardous = '$hazardous'";
                } else {
                    $hazardous = 0;
                    $query = "select max(cast(speed AS DECIMAL(20,5)))  as speed  , case when is_hazardous = '' then false else true END is_hazardous from mcmakler where "
                            . " ( date between '$three_days_ago' and '$current_date')";
                }
            
        } else {
            $query = "select max(cast(speed AS DECIMAL(20,5)))  as speed , case when is_hazardous = '' then false else true END is_hazardous from mcmakler where "
                    . " ( date between '$three_days_ago' and '$current_date')";
        }

        $getData = $this->db->query($query)->result_array();
        //echo '<pre>';print_r($getData);die;
        if (!empty($getData)) {
            $dataInJson = json_encode(array_values($getData));
        } else {
            $dataInJson = null;
        }

        echo $dataInJson;
    }

}
