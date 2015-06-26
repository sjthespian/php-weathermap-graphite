<?php
// Datasource for Graphite (http://graphite.wikidot.com/)

// TARGET graphite:graphite_url/metric:metric
//      e.g. graphite:system.example.com:8081/devices.servers.XXXXX.snmp.rx:devices.servers.XXXXX.snmp.tx

class WeatherMapDataSource_graphite extends WeatherMapDataSource {

	private $regex_pattern = "/^graphite:([\w.]+(:\d+)?)\/([,()*\w.-]+):([,()*\w.-]+)$/";

        function Init(&$map)
        {
                if(function_exists('curl_init')) { return(TRUE); }
                wm_debug("GRAPHITE DS: curl_init() not found. Do you have the PHP CURL module?\n");

                return(FALSE);
        }

	function Recognise($targetstring)
	{
		if(preg_match($this->regex_pattern, $targetstring, $matches))
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}

	function ReadData($targetstring, &$map, &$item)
	{
		$data_in = NULL;
		$data_out = NULL;
		$data_time = 0;

		if(preg_match($this->regex_pattern, $targetstring, $matches)) {
			$host = $matches[1];
			$keyin = $matches[3];
			$keyout = $matches[4];

			// make HTTP request
			$url = "http://$host/render/?rawData&from=-5minutes";
			// if key is -, return NULL for this datasource
			if($keyin != '-') {
				$url .= "&target=$keyin";
			}
			if($keyout != '-') {
				$url .= "&target=$keyout";
			}
			wm_debug("GRAPHITE DS: Connecting to $url\n");
			$ch = curl_init($url);
			curl_setopt_array($ch, array(
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_TIMEOUT => 3,
			));
			$data = curl_exec($ch);
			$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

			if ($status != 200) {
				wm_debug("GRAPHITE DS: Got HTTP code $status from Graphite\n");
				return;
			}

			# Data in form: target,starttime,endtime,step|datapoints
			# one line for each target

			foreach (explode("\n", $data) as $line) {
				if (!strpos($line, '|')) {
					continue;
				}
				list($meta, $values) = explode('|', $line, 2);
				$values = explode(',', trim($values));
				list($target, $timestart, $timeend, $timestep) = explode(',', trim($meta));
			
				# get most recent value that is not 'None'
				$data_time = $timeend;
				foreach (array_reverse($values) as $value) {
					if ($value !== 'None') {
						break;
					}
					$data_time -= $timestep;
				}

				if ($value === 'None') {
					// no value found
					wm_debug("GRAPHITE DS: No valid data points found for $target\n");
				} else {
					if ($target == $keyin) {
						$data_in = floatval($value);
					} elseif ($target == $keyout) {
						$data_out = floatval($value);
					}
				}
			}
			
			/*return array($value, $value, time());*/
			wm_debug("GRAPHITE DS: returning ". ($data_in===NULL?'NULL':$data_in) ." , ". ($data_out===NULL?'NULL':$data_out) ."\n");
			return array($data_in, $data_out, $data_time);
		}

		return false;
	}
}

// vim:ts=4:sw=4:
?>
