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
    
    function hwh_at_flow_minus_outside($id_power,$id_flow,$id_outside,$start,$end,$histogram_div,$interval,$temperature_min,$temperature_max) {
        $histogram_div = (float) $histogram_div;
        $interval = (int) $interval;
        $temperature_min = (float) $temperature_min;
        $temperature_max = (float) $temperature_max;
        
        $start = floor($start / $interval) * $interval;
        $end = floor($end / $interval) * $interval;
        $power_to_kwh = $interval / 3600000;
        
        $power_data = $this->feed->get_data($id_power,$start,$end,$interval,1,"UTC","notime");
        $flow_data = $this->feed->get_data($id_flow,$start,$end,$interval,1,"UTC","notime");
        $outside_data = $this->feed->get_data($id_outside,$start,$end,$interval,1,"UTC","notime");
        $npoints = count($power_data);
        
        $histogram = array();
        
        $timer = microtime(true);
        for ($i=0; $i<$npoints; $i++) {
            $power = $power_data[$i];
            $flow = $flow_data[$i];
            $outside = $outside_data[$i];
            
            $temperature = $flow - $outside;
            
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

    function hwh_at_ideal_carnot($id_power,$id_flow,$id_outside,$start,$end,$histogram_div,$interval,$temperature_min,$temperature_max) {
        $histogram_div = (float) $histogram_div;
        $interval = (int) $interval;
        $min = (float) $temperature_min;
        $max = (float) $temperature_max;
        
        $start = floor($start / $interval) * $interval;
        $end = floor($end / $interval) * $interval;
        $power_to_kwh = $interval / 3600000;
        
        $power_data = $this->feed->get_data($id_power,$start,$end,$interval,1,"UTC","notime");
        $flow_data = $this->feed->get_data($id_flow,$start,$end,$interval,1,"UTC","notime");
        $outside_data = $this->feed->get_data($id_outside,$start,$end,$interval,1,"UTC","notime");
        $npoints = count($power_data);
        
        $histogram = array();
        
        $timer = microtime(true);
        for ($i=0; $i<$npoints; $i++) {
            $power = $power_data[$i];
            if ($power<0) {
                $power = 0;
            }   
            $flow = $flow_data[$i];
            $outside = $outside_data[$i];
            
            $condensor = $flow + 2 + 273.15;
            $evaporator = $outside - 6 + 273.15;
            $ideal_carnot = $condensor / ($condensor - $evaporator);
            
            // calculate histogram allocation
            if ($ideal_carnot>=$min && $ideal_carnot<=$max) {
                $div = "".(floor($ideal_carnot / $histogram_div) * $histogram_div);
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

    public function flow_temp_curve($outsideT,$flowT,$heat,$start,$end,$div,$interval,$x_min,$x_max) {
        $div = (float) $div;
        $interval = (int) $interval;
        $x_min = (float) $x_min;
        $x_max = (float) $x_max;

        $start = floor($start / $interval) * $interval;
        $end = floor($end / $interval) * $interval;
        $power_to_kwh = $interval / 3600000;

        $outsideT_data = $this->feed->get_data($outsideT,$start,$end,$interval,1,"UTC","notime");
        $flowT_data = $this->feed->get_data($flowT,$start,$end,$interval,1,"UTC","notime");
        $heat_data = $this->feed->get_data($heat,$start,$end,$interval,1,"UTC","notime");

        $npoints = count($outsideT_data);

        $histogram = array();

        $timer = microtime(true);
        for ($i=0; $i<$npoints; $i++) {
            $outsideT = $outsideT_data[$i];
            $flowT = $flowT_data[$i];
            $heat = $heat_data[$i];

            // calculate histogram allocation
            // if ($outsideT>=$x_min && $outsideT<=$x_max) {
                $outside_div = "".(floor($outsideT / $div) * $div);
                $flow_div = "".(floor($flowT / $div) * $div);

                // add to histogram
                if (!isset($histogram[$outside_div])) {
                    $histogram[$outside_div] = array();
                }
                if (!isset($histogram[$outside_div][$flow_div])) {
                    $histogram[$outside_div][$flow_div] = 0;
                }

                $kwh_inc = $heat * $power_to_kwh;
                $histogram[$outside_div][$flow_div] += $kwh_inc;
            // }
        }

        header('Content-Type: text/plain');
        $out = "";
        $data = array();
        // convert to [[x,y,z]] format
        foreach ($histogram as $outside_div => $row) {
            foreach ($row as $flow_div => $kwh) {
                $kwh = number_format($kwh,3,".","")*1;
                $data[] = array($outside_div*1,$flow_div*1,$kwh);
                $out .= "$outside_div\t$flow_div\t$kwh\n";
            }
        }

        print $out;
        die;

        /*
        // sort by outside temperature
        ksort($histogram);

        // sort by flow temperature
        foreach ($histogram as $outside_div => $data) {
            ksort($histogram[$outside_div]);
        }*/
        
        return $out;
    }
    
    public function format($histogram,$histogram_div,$timer) {
        // find largest key
        $keys = array_keys($histogram);
        
        if (count($keys)) {
            $min = min($keys);
            $max = max($keys);
        } else {
            $min = 0;
            $max = 0;
        }
    
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
