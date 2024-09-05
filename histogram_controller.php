<?php

function histogram_controller() {
    global $route,$settings,$mysqli,$redis,$session;

    if ($route->action=="") {
        // return view("Modules/histogram/view.php",array());

    } else if ($route->action=="data") {
        $route->format = "json";
            
        require_once "Modules/feed/feed_model.php";
        $settings['feed']['max_datapoints'] = 1200000;
        $feed = new Feed($mysqli,$redis,$settings["feed"]);

        require "Modules/histogram/histogram.php";
        $histogram = new Histogram($feed);
        
        $start = get("start",true);
        $end = get("end",true);
        $div = get("div",true);
        $interval = get("interval",true);
        $x_min = get("x_min",true);
        $x_max = get("x_max",true);

        if ($route->subaction=="kwh_at_cop") {
            $elec = get("elec",true);
            $heat = get("heat",true);

            if (!$feed->read_access($session['userid'],$elec)) {
                return array("success"=>false, "message"=>"invalid access");
            }
            if (!$feed->read_access($session['userid'],$heat)) {
                return array("success"=>false, "message"=>"invalid access");
            }
            return $histogram->kwh_at_cop($elec,$heat,$start,$end,$div,$interval,$x_min,$x_max);

        } else if ($route->subaction=="kwh_at_temperature") {
            $power = get("power",true);
            $temperature = get("temperature",true);

            if (!$feed->read_access($session['userid'],$power)) {
                return array("success"=>false, "message"=>"invalid access");
            }
            if (!$feed->read_access($session['userid'],$temperature)) {
                return array("success"=>false, "message"=>"invalid access");
            }
            return $histogram->hwh_at_temperature($power,$temperature,$start,$end,$div,$interval,$x_min,$x_max);

        } else if ($route->subaction=="kwh_at_flow_minus_outside") {
            $power = get("power",true);
            $flow = get("flow",true);
            $outside = get("outside",true);

            if (!$feed->read_access($session['userid'],$power)) {
                return array("success"=>false, "message"=>"invalid access");
            }
            if (!$feed->read_access($session['userid'],$flow)) {
                return array("success"=>false, "message"=>"invalid access");
            }
            if (!$feed->read_access($session['userid'],$outside)) {
                return array("success"=>false, "message"=>"invalid access");
            }
            return $histogram->hwh_at_flow_minus_outside($power,$flow,$outside,$start,$end,$div,$interval,$x_min,$x_max);
            
        } else if ($route->subaction=="flow_temp_curve") {
            $outsideT = get("outsideT",true);
            $flowT = get("flowT",true);
            $heat = get("heat",true);

            if (!$feed->read_access($session['userid'],$outsideT)) {
                return array("success"=>false, "message"=>"invalid access");
            }
            if (!$feed->read_access($session['userid'],$flowT)) {
                return array("success"=>false, "message"=>"invalid access");
            }
            if (!$feed->read_access($session['userid'],$heat)) {
                return array("success"=>false, "message"=>"invalid access");
            }

            $route->format = "text";
            return $histogram->flow_temp_curve($outsideT,$flowT,$heat,$start,$end,$div,$interval,$x_min,$x_max);

        } else {
            return array("success"=>false, "message"=>"invalid subaction");
        }
    }
}
