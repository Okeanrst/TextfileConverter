<?php

function prepareDataXmlYamlToCsv(array $inputArray) {
	$csvArray = [];
	foreach ($inputArray as $level1) {
		if (is_array($level1)) {
			$titles = [];
			$values = [];
			foreach ($level1 as $key => $prod) {
				if (is_array($prod)) {
					foreach ($prod as $title => $value) {
						if (is_string($title) && !in_array($title, $titles)) {
							$titles[] = $title;
						}
					}
				} else {
					$csvArray[] = [$key => $prod];
				}
				//if (!in_array($title[], haystack))
			}
			if (count($titles) > 0) {
				foreach ($level1 as $key => $prod) {
					$tmp = [];
					foreach ($titles as $title) {
						if (isset($prod[$title]) && !empty($prod[$title])) {
							$value = $prod[$title];
							if (is_array($value)) {
								$val = '';
								foreach ($value as $partValue) {
									if (is_string($partValue) && !empty($partValue)) {
										if (strlen($val) > 0) {
											$val = $val.'|'.$partValue;
										} else {
											$val = $partValue;
										}										
									}									
								}
								$tmp[] = $val;
							} else {
								$tmp[] = $value;
							}							
						} else {
							$tmp[] = '';
						}
					}
					$values[] = $tmp;					
				}

			}
			

			$csvArray[] = $titles;
			foreach ($values as $line) {
				$csvArray[] = $line;
			}			
		} else {
			$csvArray[] = [0 => $level1];
		}
	}
	return $csvArray;
}

function prepareDataJsonToCsv(array $inputArray) {
	$csvArray = [];
	//$csvArray[] = ['name', 'value'];

	foreach ($inputArray as $key => $value) {
		if (is_array($value)) {
			$val = '';
			foreach ($value as $partValue) {
				if (is_string($partValue) && !empty($partValue)) {
					if (strlen($val) > 0) {
						$val = $val.'|'.$partValue;
					} else {
						$val = $partValue;
					}										
				}									
			}
			$csvArray[] = [$key, $val];
		} else {
			$csvArray[] = [$key, $value];
		}
	}
	
	return $csvArray;
}