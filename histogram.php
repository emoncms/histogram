<?php

class Histogram {
    
    private $feed; 

    public function __construct($feed) {
        $this->feed = $feed;
    }

    public function kwh_at_cop($id_elec,$id_heat,$start,$end,$histogram_div,$interval,$cop_min,$cop_max) {
        $histogram_div = (float) $histogram_div;
        $interval = (int) $interval;
        $cop_min = (float) $cop_min;
        $cop_max = (float) $cop_max;

        $start = floor($start / $interval) * $interval;
        $end = floor($end / $interval) * $interval;
        $power_to_kwh = $interval / 3600000;
        
        $elec_data = $this->feed->get_data($id_elec,$start,$end,$interval,1,"UTC","notime");
        $heat_data = $this->feed->get_data($id_heat,$start,$end,$interval,1,"UTC","notime");
        $npoints = count($elec_data);
        
        $histogram = array();
        
        $timer = microtime(true);
        for ($i=0; $i<$npoints; $i++) {
            $elec = $elec_data[$i];
            $heat = $heat_data[$i];
            
            if ($elec>0) {
            
                $cop = $heat / $elec;
                if ($cop>=$cop_min && $cop<=$cop_max) {
                    // calculate histogram allocation
                    $div = "".(floor($cop / $histogram_div) * $histogram_div);
                    // add to histogram
                    if (!isset($histogram[$div])) {
                        $histogram[$div] = 0;
                    }
                    $kwh_inc = $heat * $power_to_kwh;
                    $histogram[$div] += $kwh_inc;
                }
            }
        }
        
        return $this->format($histogram,$histogram_div,$timer);
    }
    
    function hwh_at_temperature($id_power,$id_temperature,$start,$end,$histogram_div,$interval,$temperature_min,$temperature_max) {
        $histogram_div = (float) $histogram_div;
        $interval = (int) $interval;
        $temperature_min = (float) $temperature_min;
        $temperature_max = (float) $temperature_max;
        
        $start = floor($start / $interval) * $interval;
        $end = floor($end / $interval) * $interval;
        $power_to_kwh = $interval / 3600000;
        
        $power_data = $this->feed->get_data($id_power,$start,$end,$interval,1,"UTC","notime");
        $temperature_data = $this->feed->get_data($id_temperature,$start,$end,$interval,1,"UTC","notime");
        $npoints = count($power_data);
        
        $histogram = array();
        
        $timer = microtime(true);
        for ($i=0; $i<$npoints; $i++) {
            $power = $power_data[$i];
            $temperature = $temperature_data[$i];
            
            // calculate histogram allocation
            if ($temperature>=$temperature_min && $temperature<=$temperature_max) {
                $div = "".(floor($temperature / $histogram_div) * $histogram_div);
                // add to histogram
                if (!isset($histogram[$div])) {
                    $histogram[$div] = 0;
                }
                $kwh_inc = $power * $power_to_kwh;
                $histogram[$div] += $kwh_inc;
            }
        }
        
        return $this->format($histogram,$histogram_div,$timer);
    }
    
    
    public function format($histogram,$histogram_div,$timer) {
        // find largest key
        $keys = array_keys($histogram);
        $min = min($keys);
        $max = max($keys);
    
        $output = array(
            "min" => $min*1,
            "max" => $max*1,
            "div" => $histogram_div*1,
            "data" => [],
            "time" => number_format(microtime(true)-$timer,3,".","")
        );
    
        for ($i=$min; $i<=$max; $i+=$histogram_div) {
            if (isset($histogram["".$i])) {
                $output["data"][] = number_format($histogram["".$i],3,".","")*1;
            } else {
                $output["data"][] = 0;
            }
        }
        return $output;
    }
}