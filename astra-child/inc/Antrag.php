<?php

defined('ABSPATH') || exit;

class Antrag{
	public $addaccess=[];
	public $variable=[];
	public $data_sample=[];
	public $dat;
	
	public $name;
	public $root;
	
	private $fdata;

	public function __construct($antrag){
		$this->name = $antrag;
		$this->root = explode('_', $antrag)[0];
		$this->data_sample = [$this->name => []];
	}
	
	public function __get($name){ # addaccess, variable or dat
		if (in_array($name, ['addaccess', 'variable', 'dat', 'data_sample'])) {
            return $this->$name;
        }
	}
	
	public function __set($name, $value){ # addaccess, variable or dat
		if (in_array($name, ['addaccess', 'variable', 'dat', 'data_sample'])) {
            $this->$name = $value;
            return;
        }
	}


	
	private static function customSort($array) {
		$keys = array_keys($array);
		
		usort($keys, function($a, $b) {
			// Получаем базовые строки (без индексов)
			$baseA = preg_replace('/_\d+$/', '', $a);
			$baseB = preg_replace('/_\d+$/', '', $b);
			
			// Если базовые строки разные, сортируем по длине
			if ($baseA !== $baseB) {
				return strlen($baseA) - strlen($baseB);
			}
			
			// Если одна строка с индексом, а другая без - строка без индекса идет первой
			$hasIndexA = preg_match('/_\d+$/', $a);
			$hasIndexB = preg_match('/_\d+$/', $b);
			if ($hasIndexA && !$hasIndexB) return 1;
			if (!$hasIndexA && $hasIndexB) return -1;
			
			// Если обе строки с индексами, сортируем по индексу
			if ($hasIndexA && $hasIndexB) {
				preg_match('/_(\d+)$/', $a, $matchesA);
				preg_match('/_(\d+)$/', $b, $matchesB);
				return intval($matchesA[1]) - intval($matchesB[1]);
			}
			
			// Если строки идентичны
			return 0;
		});
		
		$sorted = [];
		foreach ($keys as $key) {
			$sorted[$key] = $array[$key];
		}
		
		return $sorted;
	}
	
	public function transformaccess() {
		$result = [];
		$counts = [];
		$baseCounts = [];
		
		// Count all base elements across all subarrays
		foreach ($this->addaccess as $subArray) {
			foreach ($subArray as $item) {
				$base = preg_replace('/_\d+$/', '', $item);
				$baseCounts[$base] = ($baseCounts[$base] ?? 0) + 1;
			}
		}

		// Process each subarray and transform items
		foreach ($this->addaccess as $subArray) {
			$localCounts = []; // Reset counts per subarray to track local numbering
			foreach ($subArray as $item) {
				$base = preg_replace('/_\d+$/', '', $item);
				$localCounts[$base] = ($localCounts[$base] ?? 0) + 1;
				$globalCount = ($counts[$base] ?? 0) + 1;
				$counts[$base] = $globalCount;

				// Determine if the original item has a numeric suffix
				$hasSuffix = preg_match('/_(\d+)$/', $item, $matches);
				if ($hasSuffix) {
					$suffix = $matches[1];
					// Use the existing suffix if it's part of the original item
					$newItem = $base . '_' . $suffix;
				} else {
					if ($baseCounts[$base] > 1) {
						$newItem = $base . '_' . $localCounts[$base];
					} else {
						$newItem = $base;
					}
				}

				if (!in_array($newItem, $result)) {
					$result[] = $newItem;
				}
			}
		}

		// Add keys from the main array
		foreach (array_keys($this->addaccess) as $key) {
			if (!in_array($key, $result)) {
				$result[] = $key;
			}
		}

		natsort($result);
		return array_values($result);
	}

	public function filtervar() {
		foreach($this->variable as $ant => $elems){
			$isempty = true;
			foreach($elems as $elem){
				if (!in_array($elem, ["Выберите значение*", "", 0])) {
					$isempty = false;
					break;
				}
			}
			if ($isempty){
				unset($this->variable[$ant]);
			}
		}
		return $this->variable;
	}

	public function loadevery() {
	$ant = ($this->name == 'PREMIUM') ? 'PREMIUM' : $this->root;
    $every = [];
    $theme_dir = get_stylesheet_directory();
    $dirs = ["data/static/json/fields/{$ant}"];

    foreach ($dirs as $dir) {
        $full_dir_path = $theme_dir . '/' . $dir;
        if (is_dir($full_dir_path)) {
            $files = scandir($full_dir_path);
            foreach ($files as $filename) {
                if ($filename === '.' || $filename === '..') {
                    continue;
                }
                $filePath = "$full_dir_path/fields.json";
                if (file_exists($filePath)) {
                    $jsonContent = file_get_contents($filePath);
					$typ = basename($dir);
                    $every["fields{$typ}"] = json_decode($jsonContent, true);
                }
            }
        }
    }
    return $every;
}
	
	public function create_buff() {
		//$alls = [];
		//foreach ($this->addaccess as $vals) {
		//	$alls = array_merge($alls, $vals);
		$alls = array_merge([], ...array_values($this->addaccess));
		//}
		$alls = array_count_values($alls);
		$buff = [];
		foreach($alls as $var => $sum) {
			if (str_contains($var, "_") || $sum == 1) {
				//array_push($buff, $var);
				$buff[] = $var;
				continue;
			}
			for($i = 1; $i <= $sum; $i++) {
				array_push($buff, "{$var}_{$i}");
			}
		}
		return $buff;
	}

	private function add_int_links(&$toadd, $varant, $link, $val, &$variable, $tulpa=false) {
		$buff = $this->create_buff();
		$base_links = array_unique(array_map(function($str) {
			return preg_replace('/_\d+$/', '', $str);
		}, $buff));

		if ($val <= 1 && !in_array($link, $base_links)) {
        $toadd[$varant][] = $link;
		$toadd[$link]= [];
		
		} else {
            $i = 1;
            foreach ($toadd as $antkey => $ants) {
                foreach ($ants as $ant) {
                    if (explode('_', $ant)[0] == $link) {
						printt([$variable, $ant]);
                        $toadd[$antkey] = array_values(array_diff($toadd[$antkey], [$ant]));
						$deadbody = $toadd[$ant];
						unset($toadd[$ant]);
						
                        array_push($toadd[$antkey], "{$link}_{$i}");
						if ($deadbody and $deadbody!=[]){
							$toadd["{$link}_{$i}"]= $deadbody;
						} else{
							if(!isset($toadd["{$link}_{$i}"])){
								$toadd["{$link}_{$i}"]= [];
							}
						}
                        /*if (isset($variable["{$link}_{$i}"])) {
                            $variable["{$link}_{$i}"] = $variable["{$link}_{$i}"];
                        }*/if (!isset($variable["{$link}_{$i}"])) {
							$variable["{$link}_{$i}"] = isset($variable[$link]) ? $variable[$link] : [];
						} elseif(isset($variable[$link])) {
							$variable["{$link}_{$i}"] = $variable[$link];
						} else {
							$variable["{$link}_{$i}"] = array();
						}
                        $i += 1;
                    }
                }
            }
			if (!$tulpa){
				for($n=$i; $n<=$i+$val-1; $n++){
						$toadd[$varant][] = "{$link}_{$n}";
						if(!isset($toadd["{$link}_{$i}"])){
							$toadd["{$link}_{$n}"]= [];
							$variable["{$link}_{$n}"] = array();
						}
				}
			}
		}
	}

	public function check_unfilled($items, $number, $lang = 'ru') {
		if (!$this->fdata) {
			$this->fdata = json_decode(file_get_contents(get_stylesheet_directory() . "/data/static/json/data/antrags.json"), true);
		}
		$toadd = array_fill_keys(array_keys($this->addaccess), []);
		$this->update_start_addaccess($items, $number);
		$toadd = $this->addaccess + $toadd;
		printt(['variable', $this->variable]);
		printt(['toadd', $toadd ]);

			foreach ($this->variable as $varant => $antval) {
				$varant_root = $varant;
				if (str_contains($varant, '_')){
					$varant_root = explode('_', $varant)[0];
				}
				if (!isset($toadd[$varant])) continue;
				foreach ($antval as $id => $val) {
					if (!str_starts_with($id, 'F') || $val == '' || $val == 'false' || (is_numeric($val) && ((int)$val <= 0))) continue; #val is number > 0 or true

					$fid = str_replace(['F', '_'], ['', '.'], $id);
					if (!isset($this->fdata[$varant_root][$fid]['link'])) continue;

					$link = $this->fdata[$varant_root][$fid]['link'];

					if (!$link || str_contains($link, 'spec')) continue;

					if (str_contains($link, '+')) {
						$link = rtrim($link, '+');
						if ($this->fdata[$varant_root][$fid]["data-$lang"] == 'int') {
							printt(['add int', $link, (int)$val]);
								$this->add_int_links($toadd, $varant, $link, (int)$val, $this->variable);
						} elseif ($val != '') {
							printt(['add one', $link]);
								$this->add_int_links($toadd, $varant, $link, 1, $this->variable);
						}
					} else {
						$buff =[];
						foreach ($toadd as $key => $ants){
							foreach ($ants as $ant){
								array_push($buff, explode('_', $ant)[0]);
							}
						}
							if (!in_array($link, $buff)) {
								$toadd[$varant][] = $link;
								$toadd[$link] = [];
							}
					}
				}
			}
			printt(['toadd after',$toadd]);
			/*for($i=0; $i<=5;$i++){
				$buff = $this->create_buff();
				foreach($toadd as $key=>$ants){
					if (!in_array($key, $buff)){
						unset($toadd[$key]);
					}
				}
			}*/
		
		$toadd = $this->customSort($toadd);
		return $toadd;
	}

	public function update_start_addaccess($items, $id) {
		$all_tag_slugs = array();
		$item = array_values($items)[$id];
			$product = $item->get_product();
			$product_tag_slugs = array();

			// Проверяем, является ли продукт вариацией
			if ($product->is_type('variation')) {
				$parent_id = get_parent_id();
				$parent_product = wc_get_product($parent_id);
				$tags = get_the_terms($parent_id, 'product_tag');
			} else {
				$tags = get_the_terms($product->get_id(), 'product_tag');
			}

			if ($tags && !is_wp_error($tags)) {
				foreach ($tags as $tag) {
					$tag_slug = strtoupper($tag->slug);
					$product_tag_slugs[] = $tag_slug;
					if (!in_array($tag_slug, $all_tag_slugs)) {
						$all_tag_slugs[] = $tag_slug;
					}
				}
			}

		// Обработка меток, заканчивающихся на '0'
		$priority_tags = array();
		foreach ($all_tag_slugs as $key => $value) {
			if (substr($value, -1) === '0') {
				$priority_tag = substr($value, 0, -1);
				$priority_tags[] = $priority_tag;
				unset($all_tag_slugs[$key]);
				if (!in_array($priority_tag, $all_tag_slugs)) {
					$all_tag_slugs[] = $priority_tag;
				}
			}
		}

		// Сортировка: сначала приоритетные метки, затем остальные
		$sorted_tags = array_merge($priority_tags, array_diff($all_tag_slugs, $priority_tags));

		// Создаем массив $addaccess
		if (!empty($sorted_tags)) {
			$this->addaccess = array();
			$first_tag = reset($sorted_tags);
			$this->addaccess[$first_tag] = $sorted_tags;

			foreach ($sorted_tags as $slug) {
				if ($slug !== $first_tag) {
					$this->addaccess[$slug] = array();
					return;
				}
			}
		}
		$this->addaccess = array();
		return;
	}
}