<?php /* Template Name: zapolnenie-formi */ ?>
<?php get_header(); ?>
<form id="myForm" method="post" action="">
  <div style="text-align:right;margin-right:200px">
    <button type="submit" id="saveButton">Save</button>
    <input type="hidden" name="submitted" value="true" />
  </div>
  <?php

  $antrag = 'HA';

function loadevery() {
    $every = [];
    $theme_dir = get_stylesheet_directory();
    $dirs = ['data/static/json/fields', 'data/static/json/after'];

    foreach ($dirs as $dir) {
        $full_dir_path = $theme_dir . '/' . $dir;
        if (is_dir($full_dir_path)) {
            $files = scandir($full_dir_path);
            foreach ($files as $name) {
                if ($name === '.' || $name === '..') {
                    continue;
                }
                $filePath = "$full_dir_path/$name/fields.json";
                if (file_exists($filePath)) {
                    $jsonContent = file_get_contents($filePath);
                    $every["fields{$name}"] = json_decode($jsonContent, true);
                }
            }
        }
    }

    return $every;
}

$fileFields = loadevery();
$fileData = json_decode(file_get_contents(get_stylesheet_directory() . "/data/static/json/data/antrags.json"), true); // true для преобразования в массив

$my_lang = 'ru';

echo '<div class="page-table"><h1>Haupt antrag (HA) tst</h1></div>';

function type_check($fields, $id, $info, $antrag, &$processed_ids) {
    if (isset($fields['fields' . $antrag]['appear']['Y/N']) && in_array($id, $fields['fields' . $antrag]['appear']['Y/N'])) {
        echo '<input class="form-check-input mt-0" type="checkbox" name="' . $id . '" value="' . $info['data'] . '"><br>';
    } elseif (isset($fields['fields' . $antrag]['appear']['dd.mm.yyyy']) && in_array($id, $fields['fields' . $antrag]['appear']['dd.mm.yyyy'])) {
        echo '<input type="date" class="form-control" name="' . $id . '" value="' . $info['data'] . '"><br>';
    	} elseif (array_key_exists($id, $fields['fields' . $antrag]['appear']['select'])) {
    echo '<select name="' . $id . '" class="form-select">';
    echo '<option value="">Выберите значение*</option>';
    $values = $fields['fields' . $antrag]['appear']['select'][$id];
    if (is_array($values)) {
        foreach ($values as $value) {
            if (is_array($value)) {
                foreach ($value as $val) {
                    echo '<option value="' . htmlspecialchars(strval($val)) . '">' . htmlspecialchars(strval($val)) . '</option>';
                }
            } else {
                echo '<option value="' . htmlspecialchars(strval($value)) . '">' . htmlspecialchars(strval($value)) . '</option>';
            }
        }
    }
    } elseif (isset($fields['fields' . $antrag]['appear']['int']) && in_array($id, $fields['fields' . $antrag]['appear']['int'])) {
        echo '<input class="form-check-input mt-0" type="number" name="' . $id . '" value="' . $info['data'] . '"><br>';
    } elseif (isset($fields['fields' . $antrag]['appear']['NO']) && in_array($id, $fields['fields' . $antrag]['appear']['NO'])) {
        echo '<input type="hidden" name="' . $id . '" value="">';
    } elseif (isset($fields['fields' . $antrag]['appear']['textarea']) && in_array($id, $fields['fields' . $antrag]['appear']['textarea'])) {
        echo '<textarea class="form-control" name="' . $id . '">' . $info['data'] . '</textarea><br>';
    } elseif (isset($fields['fields' . $antrag]['appear']['float']) && in_array($id, $fields['fields' . $antrag]['appear']['float'])) {
        echo '<input class="form-check-input mt-0" type="number" step="any" name="' . $id . '">';
    } elseif (isset($fields['fields' . $antrag]['appear']['dd.mm.yyyy-dd.mm.yyyy']) && in_array($id, $fields['fields' . $antrag]['appear']['dd.mm.yyyy-dd.mm.yyyy'])) {
        echo 'От:<input type="date" class="form-control" name="' . $id . '" value="' . $info['data'] . '">
              До:<input type="date" class="form-control" name="' . $id . '" value="' . $info['data'] . '"><br>';
    } elseif (isset($fields['fields' . $antrag]['appear']['table']) && in_array($id, $fields['fields' . $antrag]['appear']['table'])) {
        echo '</table><table class="immunity-table"><tbody>';
        foreach ($fields['fields' . $antrag]['appear']['table'][$id] as $tr => $tds) {
            echo '<tr>';
            if (strpos($data[$antrag][$tr]['data'], ',') !== false) {
                echo '<th>' . $data[$antrag][$tr]['info-ru'] . ' ';
                type_check($fields, $tr, ['data' => explode(',', $data[$antrag][$tr]['data'])[1]], $antrag, $processed_ids);
                echo '</th>';
            } else {
                echo '<th>' . $data[$antrag][$tr]['info-ru'] . '</th>';
            }
            foreach ($tds as $td => $dat) {
                echo '<td>' . $data[$antrag][$td]['info-ru'] . ' ';
                type_check($fields, $td, ['data' => $dat], $antrag, $processed_ids);
                echo '</td>';
            }
            echo '</tr>';
        }
        echo '</tbody></table><table>';
    } else {
        echo '<input class="input-group-text" type="text" name="' . $id . '" value=""><br>';
    }
    if (!in_array($id, $processed_ids)) {
        $processed_ids[] = $id;
    }
}

function create_tables($data, $fields, $isafter) {
    if ($isafter) {
        foreach ($data as $antrag => $antrag_data) {
            if (substr($antrag, -5) === 'after') {
                echo '<div id="' . $antrag . '_table" class="container">
                      <table class="table">
                      <tr>
                        <th scope="col">id</th>
                        <th scope="row">Поле</th>
                        <th scope="row">Значение</th>
                        <th scope="row">Описание</th>
                      </tr>';
                $processed_ids = [];
                foreach ($antrag_data as $id => $info) {
                    if (!in_array($id, $processed_ids)) {
                        if ($info['addplus'] === '+') {
                            echo '<tr class="row-container" id="' . $antrag . '_row_' . $id . '">
                                  <td>' . $id . '</td>
                                  <td>' . $info['info-ru'] . '</td>
                                  <td><div class="field-container" id="' . $antrag . '_field_' . $id . '_container">';
                            type_check($fields, $id, $info, $antrag, $processed_ids);
                            echo '</div></td>
                                  <td>' . explode('!', $info['add'])[0] . '</td>
                                  </tr>';
                        }
                    }
                }
                echo '</table></div>';
            }
        }
    } else {
        foreach ($data as $antrag => $antrag_data) {
            if (substr($antrag, -5) !== 'after') {
                echo '<div id="' . $antrag . '_table" class="container">
                      <table class="table">
                      <tr>
                        <th scope="col">id</th>
                        <th scope="row">Поле</th>
                        <th scope="row">Значение</th>
                        <th scope="row">Описание</th>
                        <th scope="row">Дополнение</th>
                      </tr>';
                $processed_ids = [];
                foreach ($antrag_data as $id => $info) {
                    if (!in_array($id, $processed_ids)) {
                        echo '<tr class="row-container" id="' . $antrag . '_row_' . $id . '">
                              <td>' . $id . '</td>
                              <td>' . $info['info-ru'] . '</td>
                              <td><div class="field-container" id="' . $antrag . '_field_' . $id . '_container">';
                        type_check($fields, $id, $info, $antrag, $processed_ids);
                        echo '</div></td>
                              <td>' . explode('!', $info['add'])[0] . '</td>
                              <td>' . (isset(explode('!', $info['add'])[1]) ? explode('!', $info['add'])[1] : '') . '</td>
                              </tr>';
                    }
                }
                echo '</table></div>';
            }
        }
    }
}

// Add the call to create_tables
create_tables($fileData, $fileFields, false);
create_tables($fileData, $fileFields, true);
?>
</form>

<?php get_footer(); ?>

<?php
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submitted'])) {
  $formData = $_POST;
  unset($formData['submitted']);

  $data_sample = ['HA' => []];
	foreach ($formData as $key => $value) {
	  if ($key === 'submitted') {
		continue;
	  }

	  $parts = explode('_', $key);
	  $firstLevelKey = $parts[0];

	  if (!isset($data_sample['HA'][$firstLevelKey])) {
		$data_sample['HA'][$firstLevelKey] = [];
	  }

	  if ($value === '1') {
		$value = true;
	  } else if ($value === '0') {
		$value = false;
	  }

	  $data_sample['HA'][$firstLevelKey][$key] = $value;
	}
  $data = array(
    "mode" => 2,
    "lang" => "ru",
    "email" => "alig_1691@mail.ru",
    "partner" => null,
    "message" => array(
      "message_id" => 12345,
      "from" => array(
        "id" => 1449983348,
        "is_bot" => false,
        "first_name" => "Igor",
        "last_name" => "IT",
        "language_code" => "ru"
      ),
      "chat" => array(
        "id" => 1449983348,
        "first_name" => "Igor",
        "last_name" => "IT",
        "type" => "private"
      ),
      "date" => 1636507200,
      "data_sample" => $data_sample
    )
  );

  $jsonData = json_encode($data);
  print_r($jsonData);

  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, 'https://alexsoft.kz:44321/DBAntrag/hs/bots/PostJson');
  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
  curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Authorization: Basic ' . base64_encode('RemoteService:xxx')));
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

  // АХТУНГ. ОТЛЮЧЕНИНЕ ПРОВЕРКИ СЕРТИФИКАТОВ ЭТО ОЧЕНЬ НЕОЧЕНЬ
  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

  $server_output = curl_exec($ch);

  curl_close($ch);

if ($server_output === false) {
    $error = curl_error($ch);
    echo "<script>console.error('Ошибка: " . $error . "');</script>";
  } else {
    echo "<script>console.log('Ответ сервера (код " . $http_code . "): " . json_encode($server_output) . "');</script>";
  }
}
?>