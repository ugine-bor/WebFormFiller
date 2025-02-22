<?php

defined('ABSPATH') || exit;

class Form_renderer{
	private $antrags_to_show;
	
	public $isdemo;
	
	public function __construct($isdemo){
		$this->isdemo = $isdemo;
	}
	
	public function __set($name, $value){
		if (in_array($name, ['antrags_to_show'])) {
            $this->$name = $value;
            return;
        }
	}
	
	public function __get($name){
		if (in_array($name, ['antrags_to_show'])) {
            return $this->$name;
        }
	}



	public function type_check($fields, $data, $id, $info, $antrag, &$processed_ids, $checked, $same, $lang='ru') {
		//printt($checked); #TODO: Передавать не все id а только нужные chkd same . Вывод нужных антрагов со старта
		$antrag_exp = $antrag;
		if (str_contains($antrag, '_')){
			$antrag = explode('_', $antrag)[0];
		}
		$fieldsa = $fields['fields' . $antrag];
		
		if ($same and 'F' . explode('.', $id)[0] =='F1' and !in_array($antrag, array_keys($same)) and in_array($id, array_keys($same[array_keys($same)[0]]), true)){
			$to_put = $same[array_keys($same)[0]][$id];
		} else {
			$to_put = '';
		}
		
		$coolid = 'F'. str_replace('.','_', $id);

		if (in_array($antrag_exp, array_keys($checked)) and in_array($coolid, array_keys($checked[$antrag_exp]))){
			$parentid = get_parent_id($id);
			$checkedval = $checked[$antrag_exp][$coolid];
		} else {
			$checkedval='';
		}

		if (isset($fieldsa['appear']['Y/N']) && in_array($id, $fieldsa['appear']['Y/N'], true)) {
			$button = '';
			if (array_key_exists('show', $data[$id]) and $data[$id]["show"]=="" and $this->isintree($fieldsa['tree'], $id)){
				$button = 'checkbox-button';
			}
			echo "<input type=\"hidden\" name=\"$id\" value=\"$to_put\">";
			if ($checkedval == 'true' or $to_put != ''){
				if ($button!=''){
					echo "<input class=\"form-check-input mt-0 $button\" type=\"checkbox\" name=\"$id\" value=\"true\" id=\"$id\" checked>";
					echo "<label for=\"$id\" style=\"user-select: none;\">➜</label>";
				} else {
					echo "<input class=\"form-check-input mt-0 $button\" type=\"checkbox\" name=\"$id\" value=\"true\" checked>";
				}
			} else {
				if ($button!=''){
					echo "<input class=\"form-check-input mt-0 $button\" type=\"checkbox\" name=\"$id\" value=\"true\" id=\"$id\">";
					echo "<label for=\"$id\" style=\"user-select: none;\">➜</label>";
				} else {
					echo "<input class=\"form-check-input mt-0 $button\" type=\"checkbox\" name=\"$id\" value=\"true\">";
				}
			}
		} elseif (isset($fieldsa['appear']['dd.mm.yyyy']) && in_array($id, $fieldsa['appear']['dd.mm.yyyy'], true)) {
			if (!empty($checkedval) || !empty($to_put)) {
				$dateValue = !empty($checkedval) ? $checkedval : $to_put;
				$formattedDate = date('Y-m-d', strtotime(str_replace('.', '-', $dateValue)));
				echo "<input type=\"date\" class=\"form-control\" name=\"$id\" value=\"$formattedDate\">";
			} else {
				echo "<input type=\"date\" class=\"form-control\" name=\"$id\">";
			}
		} elseif (isset($fieldsa) and array_key_exists($id, $fieldsa['appear']['select'])) {
			$selected = '';
			foreach ($this->find_node($id, $fieldsa['tree']) as $s => $sid) {
				$sid = array_keys($sid)[0];
				$coolsid = 'F' . str_replace('.', '_', $sid);
				if (isset($checked[$antrag]) and in_array($coolsid, array_keys($checked[$antrag]))) {
					$parent = implode('.', array_slice(explode('.', $sid), 0, -1));
					$coolparent = 'F' . str_replace('.', '_', $parent);
					if (in_array($coolparent, array_keys($checked[$antrag]))){
						$selected = $checked[$antrag][$coolparent];
					}
				}
			}

			echo "<select name=\"$id\" class=\"form-select\">";
			echo "<option>Выберите значение*</option>";
			$values = $fieldsa['appear']['select'][$id];
			if (is_array($values)) {
				foreach ($values as $keys => $val) {
					if (is_array($keys)) {
						foreach ($keys as $key) {
							$isSelected = ($val === $selected) ? ' selected' : '';
							echo "<option value=\"" . htmlspecialchars(strval($val)) . "\"$isSelected>" . htmlspecialchars(strval($val)) . "</option>";
						}
					} else {
						$isSelected = ($keys === $selected || $checkedval == htmlspecialchars(strval($keys)) || $to_put != '') ? ' selected' : '';
						echo "<option value=\"" . htmlspecialchars(strval($keys)) . "\"$isSelected>" . htmlspecialchars(strval($keys)) . "</option>";
					}
				}
			}
			echo "</select>";
		} elseif (isset($fieldsa['appear']['int']) && in_array($id, $fieldsa['appear']['int'], true)) {
			echo "<input class=\"form-check-input mt-0\" type=\"text\" inputmode=\"numeric\" name=\"$id\" value=\"$checkedval\" oninput=\"this.value = this.value.replace(/[^0-9]/g, '')\">";
		} elseif (isset($fieldsa['appear']['NO']) && in_array($id, $fieldsa['appear']['NO'], true)) {
			echo "<input type=\"hidden\" name=\"$id\">";
		} elseif (isset($fieldsa['appear']['textarea']) && in_array($id, $fieldsa['appear']['textarea'], true)) {
			if ($to_put != ''){
				echo '<textarea class="form-control" name="' . $id . '">' . $to_put . '</textarea>';
			} else {
				echo '<textarea class="form-control" name="' . $id . '">' . $checkedval . '</textarea>';
			}
		} elseif (isset($fieldsa['appear']['float']) && in_array($id, $fieldsa['appear']['float'], true)) {
			echo "<input class=\"form-check-input mt-0\" type=\"number\" step=\"any\" name=\"$id\" value= \"$checkedval\">";
		} elseif (isset($fieldsa['appear']['dd.mm.yyyy-dd.mm.yyyy']) && in_array($id, $fieldsa['appear']['dd.mm.yyyy-dd.mm.yyyy'], true)) {
			if (!$checkedval){
				$checkedval = " - ";
			}
			$checkedval = explode('-', $checkedval);
			if (sizeof($checkedval)==2){
			$checkedval[0] = convertDateFormat($checkedval[0]);
			$checkedval[1] = convertDateFormat($checkedval[1]);
			} else {
				$checkedval[0] = '';
				$checkedval[1] = '';
			}
			echo "<div style=\"height: auto !important;\">
			От:<input type=\"date\" class=\"form-control\" name=\"" . $id . "_f\" value= \"{$checkedval[0]}\">
				  До:<input type=\"date\" class=\"form-control\" name=\"$id\" value= \"{$checkedval[1]}\">
				  <br>
				  </div>";
		} elseif(str_starts_with($info["data-$lang"], 'tr_') or (str_starts_with($info["data-$lang"], 'table_') and !in_array($id, $fieldsa['appear']['table']['innertables_id'], true))){
			if (str_contains($info["data-$lang"], ',')){
				$this->type_check($fields, $data, $id, ["data-$lang" => explode(',', $data[$id]["data-$lang"])[1]], $antrag, $processed_ids, $checked, $same);
			}
			else {
				echo "<input type=\"hidden\" name=\"$id\">";
			}
		} elseif (isset($fieldsa['appear']['table']['innertables_id']) and in_array($id, $fieldsa['appear']['table']['innertables_id'], true)){
			$maxcellcount=1;
			foreach ($this->find_node($id, $fieldsa['tree']) as $tr => $tds) {
				$cells = count($this->find_node($tr, $fieldsa['tree']))+1;
				if ($cells > $maxcellcount){
					$maxcellcount = $cells;
				}
			}
			echo '<table class="immunity-table"><tbody>';
			echo "<th colspan='$maxcellcount'>{$data[$id]["info-$lang"]}</th>"; //ТАБЛИЦА {$id}<br>
			foreach ($this->find_node($id, $fieldsa['tree']) as $tr => $tds) {
				echo '<tr>';
				if (strpos($data[$tr]["data-$lang"], ',') !== false) {
					echo '<th>' . $data[$tr]["info-$lang"] . ' ';
					$this->type_check($fields, $data, $tr, ["data-$lang" => explode(',', $data[$tr]["data-$lang"])[1]], $antrag, $processed_ids, $checked, $same);
					echo '</th>';
				} else {
					echo '<th>' . $data[$tr]["info-$lang"];
					$this->type_check($fields, $data, $tr, ["data-$lang" => $data[$tr]["data-$lang"]], $antrag, $processed_ids, $checked, $same);
					echo '</th>';
				}
				foreach (array_keys($tds) as $td) {
					echo '<td colspan="' . (int)($maxcellcount / count($tds)) . '">' . $data[$td]["info-$lang"] . ' ';
					$this->type_check($fields, $data, $td, ["data-$lang" => dat_by_id($td, $fields['fields' . $antrag]['appear'])], $antrag, $processed_ids, $checked, $same);
					echo '</td>';
				}
				echo '</tr>';
			}
			echo '</tbody></table>';
		} elseif (isset($fieldsa['appear']['text']) && in_array($id, $fieldsa['appear']['text'], true)) {
			if (filter_input(INPUT_GET, 'dem', FILTER_SANITIZE_STRING) and $id=='1.17'){
				echo "<input type=\"email\" id=\"emailInput\" class=\"email-input\" placeholder=\"Введите email\" name=\"$id\" value= \"$to_put\">";
			} else {
				if ($to_put != ''){
					echo "<input class=\"input-group-text\" type=\"text\" name=\"$id\" value= \"$to_put\">";
				} else {
					echo "<input class=\"input-group-text\" type=\"text\" name=\"$id\" value= \"$checkedval\">";
				}
			}
		} elseif (isset($fieldsa['appear']['empty']) && in_array($id, $fieldsa['appear']['empty'], true)) {
			echo "";
		} elseif(isset($fieldsa['appear']['empty.int']) && in_array($id, $fieldsa['appear']['empty.int'], true)){
			echo "";
		} elseif(isset($fieldsa['appear']['empty.int.NO']) && in_array($id, $fieldsa['appear']['empty.int.NO'], true)){
			echo "";
		} elseif(isset($fieldsa['appear']['empty.NO']) && in_array($id, $fieldsa['appear']['empty.NO'], true)){
			echo "";
		} else {
			if (isset($fieldsa)){
				print_r($id);
			}
		}
		if (!in_array($id, $processed_ids, true)) {
			$processed_ids[] = $id;
		}
	}

	public function isintree($tree, $id){ #priv
		if(empty($tree)){
			return false;
		}
		foreach($tree as $name => $node){
			if ($name==$id){
				return true;
			}
			elseif(!empty($node)) {
				$this->isintree($node, $id);
			}
		}
		return false;
	}

	public function dat_by_id($id, $fields){ #priv
		foreach($fields as $typ => $vars){
			foreach($vars as $idx){
				if ($idx == $id){
					return $typ;
				}
			}
		}
		return "text";
	}

	public function find_node($id, $fields){ #priv
		$buff = explode('.',strval($id))[0];
		if ($id == $buff){
			return $fields[$id];
		}
		$fields=$fields[$buff];
		foreach(array_slice(explode('.', $id), 1) as $n){
			$buff = $buff . '.' . $n;
			$fields = $fields[$buff];
			if ($buff == $id){
				return $fields;
			}
		}
		return null;
	}

	public function text_postprocess($text) {
		$link_pattern = "/\[([^\]]+)\]\(([^)]+\.pdf)\)/";
		
		$text = preg_replace_callback($link_pattern, function ($matches) {
			$link = htmlspecialchars_decode($matches[1]);
			$pdf = htmlspecialchars_decode($matches[2]);
			$file_url = get_stylesheet_directory_uri() . "/data/static/pdf/$pdf";
			return '<a style="text-decoration: underline;" href="' . esc_url($file_url) . '" target="_blank">' . htmlspecialchars($link) . '</a>';
		}, $text);
		
		return $text;
	}

	public function create_tables($antrag, $susantrag, $data, $fields, $state, $checked, $same, $lang='ru') {
		$antrag_exp = $antrag;
		if ($susantrag){
			$antrag = explode('_', $antrag)[0];
		}
		
		echo '<div id="' . htmlspecialchars($antrag) . '_table" class="table-container">
			  <table class="table">
			  <tr><th style="width: 5%;" scope="row">№</th>
				<th style="width: 40%;" scope="row">Вопрос</th>
				<th style="width: 25%;" scope="row">Выбор ответа</th>
				<th style="width: 30%;" scope="row">Пояснение в вопросу</th>
			  </tr>';
			
		$antrag_data = $data;
		$processed_ids = [];
		$buff = [];
		foreach ($antrag_data as $id => $info) {
			$id = strval($id);
			if ((filter_input(INPUT_GET, 'dem', FILTER_SANITIZE_STRING) and version_compare($id, '3.1.999', '>')) or in_array($id, $buff, true)){
				continue;
			}
			$buff[] = $id;
			if (!in_array($id, $processed_ids, $strict=true)) {
				if ($state !== 'after' && isset($fields['fields' . $antrag]['appear']['table']['tables_id']) && (in_array($id, $fields['fields' . $antrag]['appear']['table']['tables_id']) || in_array(substr('F' . explode('.', $id)[0], 1), $fields['fields' . $antrag]['appear']['table']['tables_id']))) {
					$maxcellcount=1;
					foreach ($this->find_node($id, $fields['fields' . $antrag]['tree']) as $tr => $tds) {
						$cells = count($this->find_node($tr, $fields['fields' . $antrag]['tree']))+1;
						if ($cells > $maxcellcount){
							$maxcellcount = $cells;
						}
					}
					echo '</tbody></table><table class="immunity-table"><tbody>';
					echo "<th colspan='" . htmlspecialchars($maxcellcount) . "'>{$data[$id]["info-$lang"]}";
					$this->type_check($fields, $data, $id, $info, $antrag_exp, $processed_ids, $checked, $same);
					echo '</th>';
					foreach ($this->find_node($id, $fields['fields' . $antrag]['tree']) as $tr => $tds) {
						echo '<tr>';
						if (strpos($data[$tr]["data-$lang"], ',') !== false) {
							echo '<th>' . htmlspecialchars($data[$tr]["info-$lang"]) . ' ';
							$this->type_check($fields, $data, $tr, ["data-$lang" => explode(',', $data[$tr]["data-$lang"])[1]], $antrag_exp, $processed_ids, $checked, $same);
							echo '</th>';
						} else {
							 echo '<th>' . htmlspecialchars($data[$tr]["info-$lang"]);
							$this->type_check($fields, $data, $tr, ["data-$lang" => $data[$tr]["data-$lang"]], $antrag_exp, $processed_ids, $checked, $same);
							echo '</th>';
						}
						foreach (array_keys($tds) as $td) {
						
							if (!((isset($fields['fields' . $antrag_exp]['appear']['empty']) and in_array($td, $fields['fields' . $antrag_exp]['appear']['empty'], true)) or (isset($fields['fields' . $antrag_exp]['appear']['empty.int']) and in_array($td, $fields['fields' . $antrag_exp]['appear']['empty.int'], true)))){
							
								echo '<td colspan="' . (int)($maxcellcount / count($tds)) . '">' . $data[$td]["info-$lang"] . ' ';
								$this->type_check($fields, $data, $td, ["data-$lang" => $this->dat_by_id($td, $fields['fields' . $antrag]['appear'])], $antrag_exp, $processed_ids, $checked, $same);
								echo '</td>';
							} else {
								//echo '<td colspan="' . (int)($maxcellcount / count($tds)) . '">' . $data[$td]["info-$lang"] . ' ';
								$this->type_check($fields, $data, $td, ["data-$lang" => $this->dat_by_id($td, $fields['fields' . $antrag]['appear'])], $antrag_exp, $processed_ids, $checked, $same);
								//echo '</td>';
							}
						}
						echo '</tr>';
					}
					echo '</tbody></table><table class="table"><tbody>';
				} else if ($state !== 'after') {
					$style = '';
					if (array_key_exists('show', $info) and !$info['show']){
						$style = "background-color: #F9F9F9";
					}
					echo '<tr class="row-container" id="' . htmlspecialchars($antrag) . '_row_' . htmlspecialchars($id) . '" style = "' . $style . '">
						  <td>' . htmlspecialchars($id) . '</td>
						  <td>' . $this->text_postprocess(htmlspecialchars(mb_ucfirst($info["info-$lang"]))) . '</td>
						  <td><div class="field-container field-container-large" id="' . htmlspecialchars($antrag) . '_field_' . htmlspecialchars($id) . '_container">';
							$this->type_check($fields, $data, $id, $info, $antrag_exp, $processed_ids, $checked, $same);
					echo '</div></td>
						  <td>' . $this->text_postprocess(htmlspecialchars($info["add-$lang"])) . '</td>';
				}
			}
		}
		echo '</table></div>';
	}

	public function create_head($order_id, $product, $antrag, $session){
		if (!$this->isdemo){
			if ($product->slugs['typ']== 2){
				$antrag->addaccess = array();
				foreach ($product->slugs['slugs'] as $slug) {
					$antrag->addaccess[htmlspecialchars($slug, ENT_QUOTES, 'UTF-8')] = array($slug);
				}
				$session->set('addaccess', $antrag->addaccess);
				$this->antrags_to_show = array_keys($antrag->addaccess);
			} else {
				$this->antrags_to_show = $antrag->transformaccess();
				foreach($this->antrags_to_show as $ant){
					if (!in_array($ant,array_keys($antrag->addaccess))){
						$antrag->addaccess[$ant]=[];
					}
				}
				$session->set('addaccess',$antrag->addaccess);
				$this->antrags_to_show = array_values(array_unique(array_merge(array_keys($antrag->addaccess), $this->antrags_to_show)));
				$antrag->addaccess = process_numbers($antrag->addaccess);
			}

			$helpbutton = json_decode(file_get_contents(get_stylesheet_directory() . "/data/static/descriptionlinks.json"), true)[$antrag->root];

			echo $this->render_antrag_block($helpbutton, $order_id, $product, $antrag);
		} else {
			if ($product->slugs['typ']==0){
				$antrag->addaccess = array($antrag->name=>[$antrag->name]);
			} else {
				$antrag->addaccess = array('HA'=>['HA']);
			}
			
			echo $this->render_antrag_block($helpbutton, $order_id, $product, $antrag);
		}
	}

	private function render_antrag_block($helpbutton, $order_id, $product, $antrag) {
		$html = "<div class=\"ordercontainer\" style=\"position: relative;\">
			<h2 style=\"margin: 12px; display: flex; justify-content: space-between; align-items: center;\">
				<span>Антраги для заполнения:
					<div class=\"hover-container\">
						<div class=\"question-mark\" style=\"user-select: none;\">?</div>
						<div class=\"hover-text\">Это список антрагов и приложений для заполнения.
						<br><br>Зелёная кнопка означает, что этот формуляр Вы уже заполнили.
						<br><br>Красные кнопки - формуляры, которые Вам необходимо ещё заполнить.
						<br><br>По мере заполнения Вами этих формуляров, их список может меняться: если Вы в некоторых вопросах ответили так, что понадобится заполнить дополнительное приложение. В этом случае в этот список будет добавлена кнопка для заполнения этого дополнительного приложения.
						<br><br> <a href='https://helpantrag.com/description/' target='_blank'>Подробнее</a></div>
					</div>
				</span>
				<a href=\"" . htmlspecialchars($helpbutton, ENT_QUOTES, 'UTF-8') . "\" target=\"_blank\" rel=\"noopener noreferrer\" class=\"btn btn-yellow btn-help\" style=\"margin-left: auto;\">Описание этого антрага / приложения</a>
			</h2>";

		$allantrag = array_values(array_filter(scandir(get_stylesheet_directory() . '/data/static/json/fields'), function($file) {
			return $file !== '.' && $file !== '..';
		}));
		$antraglist=array_fill_keys($allantrag, false);

		$checkmark = '';
		$buttonclass = 'btn-red';
		$antrag->variable = $antrag->filtervar();
		$url = strtok($_SERVER["REQUEST_URI"],'?');

		if (!$this->isdemo) {
			foreach($antrag->create_buff() as $ant){
				if ($ant === '.' || $ant === '..'){
					continue;
				}
				if (in_array($ant, array_keys($antrag->variable))){
					$checkmark = '<i class="fa-solid fa-check"></i> ';
					$buttonclass = 'btn-green';
					$antraglist[$ant] = true;
				}

				$safeAnt = htmlspecialchars($ant, ENT_QUOTES, 'UTF-8');
				$safeUrl = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
				$html .= "<a href=\"$safeUrl/?antr=$safeAnt&ord=$product->index&dem=$product->isdemo\" class=\"btn $buttonclass\" style=\"user-select: none;\">$safeAnt $checkmark</a><span style=\"user-select: none;\"> ➜ </span>";
				$checkmark = '';
				$buttonclass = 'btn-red';
			}
		} else {
			if ($product->slugs['typ']==0){
				$antragroot = htmlspecialchars($antrag->name, ENT_QUOTES, 'UTF-8');
				$demo_antr_url = htmlspecialchars($url . "/?antr=$antragroot&ord=$product->index&dem=$product->isdemo", ENT_QUOTES, 'UTF-8');
				$html .= "<a href=\"$demo_antr_url\" class=\"btn $buttonclass\" style=\"user-select: none;\">$antragroot</a> ➜ ";
			} else {
				$html .= "<a href=\"" . htmlspecialchars($url . "/?antr=HA&ord=4839&dem=1", ENT_QUOTES, 'UTF-8') . "\" class=\"btn $buttonclass\">HA</a><span style=\"user-select: none;\"> ➜ </span>";
			}
		}

		$html .= '<button type="submit" id="finishButton" name="action" value="finish" class="btn btn-yellow">Получить антраг</button>';
		$html .= '<input type="hidden" name="hidden_action" id="hidden_action" value="">';
		$html .= "</div>";
		return $html;
	}
}