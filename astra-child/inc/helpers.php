<?php

defined('ABSPATH') || exit;

////////////////////////////// STATIC ////////////////////////////////////////////////

function validateEmail($email) {
    $email = trim($email);
    
    if (strlen($email) > 254) {
        return false;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    
    $parts = explode('@', $email);
    if (count($parts) != 2) {
        return false;
    }
    
    $domain = $parts[1];
    if (substr($domain, 0, 1) == '-' || substr($domain, -1) == '-') {
        return false;
    }
    
    if (strpos($domain, '.') === false || substr($domain, 0, 1) == '.' || substr($domain, -1) == '.') {
        return false;
    }
    
    return true;
}

/*function process_numbers($inputArray) {
	    $elementCounts = [];
    foreach ($inputArray as $key => $values) {
        foreach ($values as $value) {
            $elementCounts[$value] = ($elementCounts[$value] ?? 0) + 1;
        }
    }

    $renamedKeysMap = [];
    foreach ($elementCounts as $element => $count) {
        if ($count > 1) {
            for ($i = 1; $i <= $count; $i++) {
                $renamedKeysMap[$element][] = $element . "_" . $i;
            }
        } else {
            $renamedKeysMap[$element][] = $element;
        }
    }

    // Add missing keys
    foreach ($elementCounts as $element => $count) {
        if (!isset($inputArray[$element])) {
            if ($count > 1) {
                foreach ($renamedKeysMap[$element] as $newKey) {
                    $inputArray[$newKey] = [];
                }
            } else {
                $inputArray[$element] = [];
            }
        }
    }

    // Rename elements in the original arrays
    foreach ($inputArray as $key => &$values) {
        foreach ($values as &$value) {
            if (isset($renamedKeysMap[$value])) {
                $index = 0;
                foreach ($inputArray as $searchKey => $searchValues) {
                    foreach ($searchValues as $searchVal) {
                        if ($searchVal === $value) {
                            if ($searchKey === $key && $searchVal === $value) {
                                $value = $renamedKeysMap[$value][$index];
                                break 2;
                            }
                            $index++;
                        }
                    }
                }
            }
        }
    }
    unset($values); // Break the reference
	return $inputArray;
}*/
function process_numbers($inputArray) {
    $elementCounts = [];
    
    // First pass: count occurrences of each base name in values
    foreach ($inputArray as $key => $values) {
        foreach ($values as $value) {
            if (preg_match('/^([A-Z]+)(?:_\d+)?$/', $value, $matches)) {
                $base = $matches[1];
            } else {
                $base = $value;
            }
            $elementCounts[$base] = ($elementCounts[$base] ?? 0) + 1;
        }
    }
    
    // Build renamed keys map
    $renamedKeysMap = [];
    foreach ($elementCounts as $base => $count) {
        if ($count > 1) {
            for ($i = 1; $i <= $count; $i++) {
                $renamedKeysMap[$base][] = $base . "_" . $i;
            }
        } else {
            $renamedKeysMap[$base][] = $base;
        }
    }
    
    // Update input array keys based on counts
    foreach ($elementCounts as $base => $count) {
        if ($count > 1) {
            if (isset($inputArray[$base])) {
                unset($inputArray[$base]);
            }
            foreach ($renamedKeysMap[$base] as $newKey) {
                if (!isset($inputArray[$newKey])) {
                    $inputArray[$newKey] = [];
                }
            }
        } else {
            if (!isset($inputArray[$base])) {
                $inputArray[$base] = [];
            }
        }
    }
    
    // Rename elements in the values using a non-static counter
    $counter = [];
    foreach ($inputArray as $key => &$values) {
        foreach ($values as &$value) {
            if (preg_match('/^([A-Z]+)(?:_\d+)?$/', $value, $matches)) {
                $base = $matches[1];
            } else {
                $base = $value;
            }
            if (isset($renamedKeysMap[$base])) {
                if (!isset($counter[$base])) {
                    $counter[$base] = 0;
                }
                if ($counter[$base] < count($renamedKeysMap[$base])) {
                    $value = $renamedKeysMap[$base][$counter[$base]];
                }
                $counter[$base]++;
            }
        }
    }
    unset($values); // Break the reference
    
    return $inputArray;
}


function custom_array_merge($array1, $array2) { #приоритет на array2
    $result = $array2;

    foreach ($array1 as $ant => $values) {
		foreach($values as $key => $value){
			if (!isset($result[$ant])){
				$result[$ant] = array();
			}
			$is_f_key = substr($key, -2) === '_f';
			$non_f_key = $is_f_key ? substr($key, 0, -2) : $key;

			if ($is_f_key || isset($result[$ant][$key . '_f'])) {
				$result[$ant][$key] = $value;
			} elseif (!isset($result[$ant][$key]) || $result[$ant][$key] === '' || $result[$ant][$key] === null) {
				$result[$ant][$key] = $value;
			}
		}
    }

    return $result;
}

function output_table_listener_script($fileFields, $antrag) {
    if (!empty($fileFields) && !empty($antrag)) {
        $jsonAfFields = json_encode($fileFields['fields' . $antrag]['tree'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
        $chooseEncoded = json_encode($fileFields['fields' . $antrag]['appear']['select'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

        echo '<script>';
        echo 'var wpvars = ' . json_encode(array(
            'curstate' => $antrag,
            'afields' => $jsonAfFields,
            'choose' => $chooseEncoded,
        )) . ';';
        echo '</script>';
        echo '<script src="' . get_stylesheet_directory_uri() . '/js/table_listener.js' . '"></script>';
    }
}

function mb_ucfirst($string, $encoding = 'UTF-8') {
    $firstChar = mb_substr($string, 0, 1, $encoding);
    $rest = mb_substr($string, 1, mb_strlen($string, $encoding), $encoding);
    return mb_strtoupper($firstChar, $encoding) . $rest;
}

function get_parent_id($id) {
    $parts = explode('.', $id);
    
    if (count($parts) < 2) {
        return $id;
    }
    
    $parentParts = array_slice($parts, 0, 2);
    
    return implode('.', $parentParts);
}

function filterArray($array) {
  return array_filter($array, function ($value) {
    $allFalseOrEmpty = true;
    foreach ($value as $subValue) {
      if ($subValue !== false && $subValue !== '') {
        $allFalseOrEmpty = false;
        break;
      }
    }
    return !$allFalseOrEmpty;
  });
}

function convertDateFormat($date) {
	if (empty($date)) return '';
	$date = trim($date);
	$dateObj = DateTime::createFromFormat('d.m.Y', $date);
	return $dateObj ? $dateObj->format('Y-m-d') : $date;
}
//////////////////////////////////////////////////////////////////////////////////////