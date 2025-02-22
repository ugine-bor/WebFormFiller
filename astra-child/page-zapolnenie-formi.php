<?php /* Template Name: zapolnenie-formi */

require_once __DIR__ . '/autoload.php';
require_once __DIR__ . '/inc/helpers.php';

get_header();

if ( ! is_user_logged_in() ) {
    // Если пользователь не авторизован, перенаправляем на страницу входа
    wp_redirect( home_url() ); // Перенаправляем на домашнюю страницу, если доступ запрещен
    exit;
}

// Проверка на роль пользователя (например, только для администраторов и редакторов)
if ( ! current_user_can( 'administrator' ) && ! current_user_can( 'um_custom_role_1' ) ) {
    wp_redirect( home_url() ); // Перенаправляем на домашнюю страницу, если доступ запрещен
    exit;
}

?>
<link rel="stylesheet" href="<?php echo get_stylesheet_directory_uri() . '/zapoln.css'; ?>">

<?php
	ob_start();

	$dynamicMessage = '';
	$product_id = filter_input(INPUT_GET, 'ord', FILTER_SANITIZE_STRING);
	$order_id = explode('-', $product_id)[0];
	
	$order = new Order($order_id);
	$product = new Product($product_id, filter_input(INPUT_GET, 'dem', FILTER_SANITIZE_STRING), $order);
	$user = new User(get_current_user_id());
	$antrag = new Antrag(filter_input(INPUT_GET, 'antr', FILTER_SANITIZE_STRING));
	$session = new Session();
	$api = new Api_manager($order, $user, $product);
	$form = new Form_renderer($product->isdemo);
	$process = new Data_processor();

	$antrag->dat = $api->getter($product->index, $user->get_user_id());

	if ($product->slugs['typ'] == 0){
		$product->isdemo=true;
		$antrag->root = $antrag->name;
		$antrag->name = 'PREMIUM';
	}
	if($product->index!=($session->get('productindex') ?? null)) { # Другой заказ
		$session->set('addaccess', []);
		$session->set('formdata', []);
		
		$session->set('productindex', $product->index);
		printt('RELOADED SESSION');
	}
	printt(['start session data: ', $session->get('formdata')]);
	if([] !== $session->get('formdata')) { # есть контекст
		$antrag->variable = $session->get('formdata');
			printt($antrag->variable);
		if (isset($antrag->dat)){ # есть контекст и сохранения
			$antrag->variable = custom_array_merge($antrag->dat['data_sample'], $antrag->variable);
			$session->set('formdata', $antrag->variable);
			printt($antrag->variable);
		}
	} elseif (null !== $antrag->dat) { # есть только сохранения
		if ($antrag->dat){
			$antrag->variable = $antrag->dat['data_sample'];
			if (isset($antrag->variable['ErrorText'])){
				$antrag->variable = [];
			}
		}
	}
	
	
	$susantrag = false;
	if (str_contains($antrag->name, '_')){
		$susantrag = true;
	}
	$helpbutton = json_decode(file_get_contents(get_stylesheet_directory() . "/data/static/descriptionlinks.json"), true)[$antrag->root];
	$email = um_user('user_email');
	
	printt(['session addsacess: ',$session->get('addaccess')]);
	if (null !== $session->get('addaccess') and [] !== $session->get('addaccess')) {
		$antrag->addaccess = $session->get('addaccess');
	} elseif(!$product->isdemo) {
		$antrag->update_start_addaccess($order->get_order()->get_items(), $product->number);
	}
	
	$antrag->addaccess = process_numbers($antrag->addaccess);
	$session->set('addaccess', $antrag->addaccess);
	printt($session->get('addaccess'));
	
	if ((um_profile_id() != $user->get_user_id())) {
		status_header(403);
		ob_end_clean();
		get_template_part('403');
		exit();
	}
	
	if (!$order){
		ob_end_clean();
		echo "Заказ не найден";
		exit();
	}
	
	$form->create_head($order->get_order_id(), $product, $antrag, $session);

	if ($susantrag){
		$fileFieldsAfter = $antrag->loadevery();
	} else {
		$fileFieldsAfter = $antrag->loadevery();
	}
	
	$fileData = json_decode(file_get_contents(get_stylesheet_directory() . "/data/static/json/data/antrags.json"), true);
	$fileDataAfter = [];
	
	if (isset($fileData[$antrag->root . '-after'])){
		$fileDataAfter = $fileData[$antrag->root . '-after']; // true для преобразования в массив
	}
	
	$fileFields = $antrag->loadevery();
	if ($susantrag){
		$fileData = $fileData[explode('_',$antrag->name)[0]];
	}
	else {
		$fileData = $fileData[$antrag->name];
	}
	
	$fileFields = array_merge($fileFields, $fileFieldsAfter);
	$fileData = $fileData + $fileDataAfter;
	$lang = 'ru';
	$antragname = json_decode(file_get_contents(get_stylesheet_directory() . "/data/static/abkurz.json"), true)[$antrag->root];

	echo "<div class='page-table'><h3>" . htmlspecialchars($antragname['ru'], ENT_QUOTES, 'UTF-8') . "</h3> " . htmlspecialchars($antragname['de'], ENT_QUOTES, 'UTF-8') . " (" . htmlspecialchars($antrag->root, ENT_QUOTES, 'UTF-8') . ")</div>";

	$filedat = json_decode(file_get_contents(get_stylesheet_directory() . "/data/static/json/data/antrags.json"), true);
	$same = [];

		foreach ($antrag->variable as $ant => $antval) {
			foreach ($antval as $id => $val) {
				if ($id[1]=='1' && $val && $val !== '' && $val !== 'false') {
					$cleanId = str_replace(['F', '_'], ['', '.'], $id);
					if ($cleanId !== 'action' && !isset($same[$cleanId])) {
						$valdat = $filedat[$ant][$cleanId]['info-de'] ?? null;
						foreach ($fileData as $id2 => $val2) {
							if (!is_int($id2) and $id2[0]!='1'){
								break;
							}
							if ($valdat === $val2['info-de']) {
								$same[$ant][htmlspecialchars($id2, ENT_QUOTES, 'UTF-8')] = htmlspecialchars($val, ENT_QUOTES, 'UTF-8');
							}
						}
					}
				}
			}
		}

	if ($susantrag){
		$antragname = explode('_', $antrag->name)[0];
		add_action('wp_footer', function() use ($fileFields, $antragname) {
			output_table_listener_script($fileFields, $antragname);
		});
	} else {
		$antragname = $antrag->name;
		add_action('wp_footer', function() use ($fileFields, $antragname) {
		output_table_listener_script($fileFields, $antragname);
		});
	}

?>

<div id="dynamicLabel"></div>

<form id="myForm" method="post" action="">
<?php if (!$product->isdemo): ?>
<div class="button-container">
  <div class="button-container">
    <div>
      <button type="submit" id="nextButton1" name="action" value="next" class="btn btn-yellow">Далее</button>
    </div>
    <div>
      <button type="submit" id="saveButton1" name="action" value="save" class="btn btn-yellow">Сохранить</button>
    </div>
	<div>
      <button type="submit" id="retrieveButton" name="action" value="retr" class="btn btn-yellow">Восстановить данные</button>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php $form->create_tables($antrag->name, $susantrag, $fileData, $fileFields, 'main', $antrag->variable, $same); ?>

<?php if (!$product->isdemo): ?>
  <div class="button-container">
    <div>
      <button type="submit" id="nextButton2" name="action" value="next" class="btn btn-yellow">Далее</button>
    </div>
    <div>
      <button type="submit" id="saveButton2" name="action" value="save" class="btn btn-yellow">Сохранить</button>
    </div>
  </div>
    <?php endif; ?>
</form>
	<?php if (!$product->isdemo): ?>
   <script>
   
		const retrieveButton = document.getElementById('retrieveButton');
   
        const finishButton = document.getElementById('finishButton');
        const hiddenAction = document.getElementById('hidden_action');
   
        const nextButton1 = document.getElementById('nextButton1');
        const saveButton1 = document.getElementById('saveButton1');
		
		const nextButton2 = document.getElementById('nextButton2');
        const saveButton2 = document.getElementById('saveButton2');
		
		
		retrieveButton.addEventListener('click', function() {
            hiddenAction.value = 'retrieve';
        });
		
		
		document.getElementById('finishButton').addEventListener('click', function() {
		  var hiddenField = document.createElement('input');
		  hiddenField.type = 'hidden';
		  hiddenField.name = 'action';
		  hiddenField.value = 'finished';

		  var form = document.getElementById('myForm');
		  form.appendChild(hiddenField);

		  form.submit();
		});
		
		

        nextButton1.addEventListener('click', function() {
            hiddenAction.value = 'next';
        });

        saveButton1.addEventListener('click', function() {
            hiddenAction.value = 'save';
        });
		
		
		
		nextButton2.addEventListener('click', function() {
            hiddenAction.value = 'next';
        });

        saveButton2.addEventListener('click', function() {
            hiddenAction.value = 'save';
        });
    </script>
	<?php endif; ?>
	<?php if ($product->isdemo): ?>
	<script>
	      const finishButton = document.getElementById('finishButton');
          const hiddenAction = document.getElementById('hidden_action');
		  
		  
		  document.getElementById('finishButton').addEventListener('click', function() {
		  var hiddenField = document.createElement('input');
		  hiddenField.type = 'hidden';
		  hiddenField.name = 'action';
		  hiddenField.value = 'finished';

		  var form = document.getElementById('myForm');
		  form.appendChild(hiddenField);

		  form.submit();
		});
	</script>
	<?php if (!($product->slugs['typ']==0)): ?>
	<script>
        const emailInput = document.getElementById('emailInput');

        emailInput.addEventListener('input', function() {
            const email = this.value;
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (emailRegex.test(email)) {
                this.classList.remove('invalid');
            } else {
                this.classList.add('invalid');
            }
        });
    </script>
	<?php endif; ?>
	<script>
    function updateDynamicLabel(message) {
        var label = document.getElementById('dynamicLabel');
        if (message) {
            label.textContent = message;
            label.style.display = 'block';
        } else {
            label.textContent = '';
            label.style.display = 'none';
        }
    }

    // Инициализация лейбла
    updateDynamicLabel(<?php echo json_encode(htmlspecialchars($dynamicMessage, ENT_QUOTES, 'UTF-8')); ?>);
    </script>
	<?php endif; ?>


<?php get_footer(); ?>

<?php

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action']) && $_POST['action'] == 'save') { ///////////////////////////////////////////////// СОХРАНИТЬ
        $reqtype = 0;
        $formData = $_POST;
        unset($formData['submitted']);
        unset($formData['action']);

        foreach ($formData as $key => $value) {
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                $date = DateTime::createFromFormat('Y-m-d', $value);
                if ($date) {
                    $value = $date->format('d.m.Y');
                }
            }

            $antrag->data_sample[$antrag->name]['F' . $key] = $value;
        }

		$antrag->data_sample = $process->addempty($antrag->data_sample, $fileFields, $antrag->name);
        $antrag->data_sample = $process->skipper(array_merge($antrag->variable, $antrag->data_sample), $antrag->addaccess);
		$antrag->variable = $antrag->data_sample;
		if ($product->slugs['typ']!= 2){
			printt(['before', $antrag->addaccess]);
			$antrag->addaccess = $antrag->check_unfilled($order->get_order()->get_items(), $product->number);
			printt(['after', $antrag->addaccess]);
			$antrag->addaccess = process_numbers($antrag->addaccess);
			printt(['afterafter', $antrag->addaccess]);
		}
		$antrag->data_sample = $process->skipper(array_merge($antrag->variable, $antrag->data_sample), $antrag->addaccess);
		$session->set('formdata', filterArray($antrag->data_sample));
        $session->set('addaccess', $antrag->addaccess);
        
        foreach($antrag->data_sample as $ant => $antval) {
			$antsus = explode('_', $ant)[0]; # заменить на текущий в классе
            unset($antrag->data_sample[$ant]['Faction']);
            foreach($antval as $id => $idval) {
                $realid = str_replace('_', '.', str_replace("F", '', $id));
				
				if((!str_ends_with($realid, '.f')) and !str_contains($antrag->data_sample[$ant][$id], ' - ') and ($filedat[$antsus][$realid]['data'] == "dd.mm.yyyy-dd.mm.yyyy") and isset($antrag->data_sample[$ant][$id . '_f']) and ($antrag->data_sample[$ant][$id . '_f']!='')){
					$value = $antrag->data_sample[$ant][$id . '_f'] . ' - ' . $antrag->data_sample[$ant][$id];
					$antrag->data_sample[$ant][$id] = $value;
					unset($antrag->data_sample[$ant][$id . '_f']);
				} elseif(str_ends_with($realid, '.f')) {
					if ($antrag->data_sample[$ant][$id]==''){
						unset($antrag->data_sample[$ant][$id]);
					}
					continue;
				}
				
				if (trim($filedat[$antsus][$realid]["data"]) == 'int'){
					$antrag->data_sample[$ant][$id] = (int)$idval;
				} elseif (trim($filedat[$antsus][$realid]["data"]) == 'float'){
					$antrag->data_sample[$ant][$id] = (int)$idval;
				}
				
				if ($realid != 'action') {
                    if (!isset($filedat[$antsus][$realid]['show']) || $filedat[$antsus][$realid]['show'] != '1') {
                        unset($antrag->data_sample[$ant][$id]);
                    }
                }
            }
        }

        $result = $api->sender($antrag->data_sample, $reqtype, $email, $antrag->isdemo, $product->index, $user->get_user_id());
        if ($result) {
            echo "<br><br><h3>Формуляры сохранены.</h3>";
        } else {
            echo "<br><br><h3>Ошибка отправки запроса. Попробуйте ещё раз</h3>";
        }
        header("Refresh:0");
    }
    elseif (isset($_POST['action']) && $_POST['action'] == 'finished') { ///////////////////////////////////////////// ПОЛУЧИТЬ АНТРАГ
        $reqtype = 1;
        $formData = $_POST;
        unset($formData['submitted']);
        unset($formData['action']);

        $antrag->data_sample = [$antrag->name => []];
        foreach ($formData as $key => $value) {
            if ($key === 'submitted') {
                continue;
            }
            
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                $date = DateTime::createFromFormat('Y-m-d', $value);
                if ($date) {
                    $value = $date->format('d.m.Y');
                }
            }
            $antrag->data_sample[$antrag->name]['F' . $key] = $value;
        }
		$antrag->data_sample = $process->addempty($antrag->data_sample, $fileFields, $antrag->name);
        $antrag->data_sample = $process->skipper(array_merge($antrag->variable, $antrag->data_sample), $antrag->addaccess);
		$antrag->variable = $antrag->data_sample;
        if (!$product->isdemo) {
            $antrag->data_sample = $process->skipper(array_merge($antrag->variable, $antrag->data_sample), $antrag->addaccess);
			if ($product->slugs['typ']!= 2){
				$antrag->addaccess = $antrag->check_unfilled($order->get_order()->get_items(), $product->number);
			}
			$antrag->data_sample = $process->skipper(array_merge($antrag->variable, $antrag->data_sample), $antrag->addaccess);
        }
        $antrag->data_sample = $process->addempty($antrag->data_sample, $fileFields, $antrag->name);
        foreach($antrag->data_sample as $ant => $antval) {
			if (!in_array($ant, $antrag->create_buff())){
				unset($antrag->data_sample[$ant]);
				continue;
			}
			$antsus = explode('_', $ant)[0];
            unset($antrag->data_sample[$ant]['Faction']);
            foreach($antval as $id => $idval) {
                $realid = str_replace('_', '.', str_replace("F", '', $id));

				if (str_contains($filedat[$antsus][$realid]["data-$lang"], ';')){

					$options = explode(';', $filedat[$antsus][$realid]["data-$lang"]);

					$selected_option = $idval;

					$last_number = '';
					foreach ($options as $option) {
						$parts = explode(',', $option);
						if (trim($parts[1]) === $selected_option) {
							$id_parts = explode('.', $parts[0]);
							$last_number = end($id_parts);
							break;
						}
					}

					$antrag->data_sample[$ant][$id] = (string)((int)$last_number - 1);
				} elseif (trim($filedat[$antsus][$realid]["data"]) == 'int'){
					$antrag->data_sample[$ant][$id] = (int)$idval;
				} elseif (trim($filedat[$antsus][$realid]["data"]) == 'float'){
					$antrag->data_sample[$ant][$id] = (int)$idval;
				}
				
				if((!str_ends_with($realid, '.f')) and !str_contains($antrag->data_sample[$ant][$id], ' - ') and ($filedat[$antsus][$realid]['data'] == "dd.mm.yyyy-dd.mm.yyyy") and isset($antrag->data_sample[$ant][$id . '_f']) and ($antrag->data_sample[$ant][$id . '_f']!='')){
					$value = $antrag->data_sample[$ant][$id . '_f'] . ' - ' . $antrag->data_sample[$ant][$id];
					$antrag->data_sample[$ant][$id] = $value;
					unset($antrag->data_sample[$ant][$id . '_f']);
				} elseif(str_ends_with($realid, '.f')) {
					if ($antrag->data_sample[$ant][$id]==''){
						unset($antrag->data_sample[$ant][$id]);
					}
					continue;
				}
				if ($realid != 'action') {
                    if (!isset($filedat[$antsus][$realid]['show']) || $filedat[$antsus][$realid]['show'] != '1') {
                        unset($antrag->data_sample[$ant][$id]);
                    }
                }
            }
        }
		if ($antrag=='EKS'){
			$data_sorted = $antrag->data_sample['EKS'];
			uksort($data_sorted, function ($a, $b) {
				// Извлечение чисел из ключей, например, "F1_1" => [1, 1]
				$pattern = '/\d+/';
				preg_match_all($pattern, $a, $aMatches);
				preg_match_all($pattern, $b, $bMatches);

				// Преобразование в массивы чисел
				$aNumbers = array_map('intval', $aMatches[0]);
				$bNumbers = array_map('intval', $bMatches[0]);

				// Пошаговое сравнение чисел
				for ($i = 0; $i < max(count($aNumbers), count($bNumbers)); $i++) {
					$aValue = $aNumbers[$i] ?? 0;
					$bValue = $bNumbers[$i] ?? 0;

					if ($aValue !== $bValue) {
						return $aValue - $bValue;
					}
				}

				return 0;
			});
			$antrag->data_sample['EKS'] = $data_sorted;
		}
        if($product->isdemo and !$product->slugs['typ']==0) {
            $newdata = [];
            foreach ($antrag->data_sample as $key => $value) {
                if ($key === $antrag) {
                    $newdata['HA_Demo'] = $value;
                } else {
                    $newdata[$key] = $value;
                }
            }
            $antrag->data_sample = $newdata;
            $email = $antrag->data_sample['HA_Demo']['F1_17'];
			
        }
        if ($product->isdemo && ! $product->slugs['typ']==0 && !validateEmail($email)) {
            $message = updateMessage('Email адрес в пункте 1.17 введён неверно.');
            echo "<script>updateDynamicLabel(" . json_encode($message) . ");</script>";
            exit;
        }
		if($product->slugs['typ']==0) {
			$antrag->data_sample[$antrag]["F1_5"] = $antragroot;
		}

		ob_end_clean();
        $result = $api->sender($antrag->data_sample, $reqtype, $email, $antrag->isdemo, $product->index, $user->get_user_id());
        if ($result) {
            if ($order->get_order()) {
                $order->get_order()->update_status('completed');
            }
			if($product->slugs['typ']==0) {
				echo "<br><br><h2>" . htmlspecialchars("Запрос от $email отправлен специалисту.") . "</h2>";
			} else {
				echo "<br><br><h2>" . htmlspecialchars("Формуляры отправлены. Заполненные антраги отправлены на $email.") . "</h2>";
			}
            echo "<a href=" . home_url() . " class=\"btn btn-yellow\">Вернуться на главную</a>";
        } else {
            echo "<br><br><h3>Ошибка отправки запроса. Попробуйте ещё раз.</h3>";
        }
        exit;
    } elseif (isset($_POST['action']) && $_POST['action'] == 'retr') {////////////////////////////////////////////////////////////////////////// ВОССТАНОВИТЬ ДАННЫЕ
        header("Refresh:0");
    } else { ///////////////////////////////////////////////////////////////////////////////////////////////////////// ДАЛЕЕ
        $formData = $_POST;
        unset($formData['submitted']);
        unset($formData['action']);

        $antrag->data_sample = [$antrag->name => []];
        foreach ($formData as $key => $value) {
            if ($key === 'submitted') {
                continue;
            }
            
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                $date = DateTime::createFromFormat('Y-m-d', $value);
                if ($date) {
                    $value = $date->format('d.m.Y');
                }
            }

            $antrag->data_sample[$antrag->name]['F' . $key] = $value;
        }
		$antrag->data_sample = $process->addempty($antrag->data_sample, $fileFields, $antrag->name);
        $antrag->data_sample = $process->skipper(array_merge($antrag->variable, $antrag->data_sample), $antrag->addaccess);
		$antrag->variable = $antrag->data_sample;
		if (!$product->isdemo) {
            $antrag->data_sample = $process->skipper(array_merge($antrag->variable, $antrag->data_sample), $antrag->addaccess);
			if ($product->slugs['typ']!= 2){
				$antrag->addaccess = $antrag->check_unfilled($order->get_order()->get_items(), $product->number);
			}
			$antrag->data_sample = $process->skipper(array_merge($antrag->variable, $antrag->data_sample), $antrag->addaccess);
        }
		$antrag->data_sample = $process->skipper(array_merge($antrag->variable, $antrag->data_sample), $antrag->addaccess);
        #$antrag->data_sample = $process->skipper(array_merge($antrag->variable, $antrag->data_sample), $antrag->addaccess);
		#$antrag->addaccess = $antrag->check_unfilled($order->get_order()->get_items(), $product->number);
		#$antrag->data_sample = $process->skipper(array_merge($antrag->variable, $antrag->data_sample), $antrag->addaccess);
		$session->set('formdata', filterArray($antrag->data_sample));
        $session->set('addaccess',$antrag->addaccess);
		
		
        foreach($antrag->data_sample as $ant => $antval) {
			$antsus = explode('_', $ant)[0];
            unset($antrag->data_sample[$ant]['Faction']);
            foreach($antval as $id => $idval) {
                $realid = str_replace('_', '.', str_replace("F", '', $id));
				
				if((!str_ends_with($realid, '.f')) and !str_contains($antrag->data_sample[$ant][$id], ' - ') and ($filedat[$antsus][$realid]['data'] == "dd.mm.yyyy-dd.mm.yyyy") and isset($antrag->data_sample[$ant][$id . '_f']) and ($antrag->data_sample[$ant][$id . '_f']!='')){
					$value = $antrag->data_sample[$ant][$id . '_f'] . ' - ' . $antrag->data_sample[$ant][$id];
					$antrag->data_sample[$ant][$id] = $value;
					unset($antrag->data_sample[$ant][$id . '_f']);
				} elseif(str_ends_with($realid, '.f')) {
					if ($antrag->data_sample[$ant][$id]==''){
						unset($antrag->data_sample[$ant][$id]);
					}
					continue;
				}
				if ($realid != 'action') {
                    if (!isset($filedat[$antsus][$realid]['show']) || $filedat[$antsus][$realid]['show'] != '1') {
                        unset($antrag->data_sample[$ant][$id]);
                    }
                }
            }
        }
		
		$parsedUrl = parse_url($_SERVER['REQUEST_URI']);
        $parsedUrl['scheme'] = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $parsedUrl['host'] = $_SERVER['HTTP_HOST'];
        
        $resultsave = $api->sender($antrag->data_sample, 0, $email, false, $product->index, $user->get_user_id());
                
        $params = explode('&', $_SERVER['QUERY_STRING'], 2);

		$allaccess = $antrag->create_buff();
		
        if (sizeof($allaccess) >= 2 && in_array($antrag->name, $allaccess) && $antrag->name != end($allaccess)) {
            $nextant = 'antr=' . $allaccess[array_search($antrag->name, $allaccess)+1];
        }
        else {
            $nextant = "antr={$allaccess[0]}";
        }

        $newUrl = $parsedUrl['path'] . '?' . $nextant . '&' . $params[1];
        header("Location: $newUrl");
        exit;
    }
}
?>