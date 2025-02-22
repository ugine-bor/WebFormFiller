<?php

defined('ABSPATH') || exit;

class Data_processor{
	public function __contruct(){
		
	}
	
	public static function skipper($data, $addaccess){
		$form = function(string $id) {
			if ($id != '0') {
				return ['F' . str_replace('.', '_', $id) => ""];
			}
		};

		$all = json_decode(file_get_contents(get_stylesheet_directory() . "/data/static/json/data/antrags.json"), true);
		$data_alt = array();
		foreach($addaccess as $addlinks => $ants){
			foreach($ants as $ant){
				if (str_contains($ant, '_')){
					$antclear = explode('_', $ant)[0];
					$lst = array_merge(...array_map($form, array_keys($all[$antclear])));
					$data_alt[$ant] = $lst;
				} else {
					$lst = array_merge(...array_map($form, array_keys($all[$ant])));
					$data_alt[$ant] = $lst;
				}
			}
		}

		foreach ($data_alt as $ant => $vals) {
			if (!in_array($ant, array_keys($data))){
				$data[$ant] = $vals;
				continue;
			}
			foreach($vals as $id => $val){
				if (isset($data[$ant]) && !isset($data[$ant][$id])) {
					$data[$ant][$id] = $val;
				}
			}
		}

		foreach ($data as $ant => &$vals) {
			$updatedVals = [];
			foreach ($vals as $id => $val) {
				if (str_ends_with($id, '_f')) {
					$new_id = str_starts_with($id, 'F') ? $id : 'F' . $id;
					$updatedVals[$new_id] = $val;
				} else {
					$updatedVals[$id] = $val;
				}
			}
			$vals = $updatedVals;
		}

		ksort($data);
		return $data;
	}

	public static function addempty($data, $fields, $antragname) {

        $types = [
            'empty' => '',
            'empty.int' => 0,
            'empty.NO' => '',
            'empty.int.NO' => 0
        ];

        foreach ($types as $key => $defaultValue) {
            if (isset($fields['fields' . $antragname]['appear'][$key])) {
                foreach ($fields['fields' . $antrag]['appear'][$key] as $id) {
                    $fieldKey = 'F' . str_replace('.', '_', $id);
                    $data[$antragname][$fieldKey] = $defaultValue;
                }
            }
        }

		return $data;
	}
}