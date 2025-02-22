import os

import pandas as pd


def xlsx_to_csv(input_file, output_file, columns=None):
    if columns is None:
        columns = ['id', 'info-de', 'data', 'link', 'add', 'addplus', '--RU--', 'info-ru', 'data-ru', 'add-ru',
                   'addplus-ru', 'show']
    try:
        data = pd.read_excel(input_file, dtype=str)
        data = data.fillna('')
        data = data.map(lambda x: str(x).replace('\n', ''))
        data.columns = columns

        data.to_csv(output_file, index=False, encoding='utf-8', sep=';')
        print(f"{output_file} - CSV файл успешно создан.\n")
    except Exception as e:
        print(f"Произошла ошибка: {str(e)}")


# input_file = "../xlsx/HA.xlsx"
# output_file = "../static/AntragCSV/HA.csv"
# xlsx_to_csv(input_file, output_file)


import os
import pandas as pd


def xlsx_to_csv2(input_file, output_folder, columns=None):
    try:
        # Чтение Excel файла в DataFrame
        data = pd.read_excel(input_file, dtype=str)
        data = data.fillna('')

        # Автоматическое определение столбцов, если они не заданы
        if columns is None:
            columns = data.columns

        # Удаление символов новой строки
        data = data.applymap(lambda x: str(x).replace('\n', ''))

        # Переименовываем столбцы, если нужно
        if len(columns) == len(data.columns):
            data.columns = columns

        # Создание папки, если она не существует
        if not os.path.exists(output_folder):
            os.makedirs(output_folder)

        # Формируем путь для сохранения CSV файла
        file_name = os.path.splitext(os.path.basename(input_file))[0] + '.csv'
        output_file = os.path.join(output_folder, file_name)

        # Сохранение в CSV
        data.to_csv(output_file, index=False, encoding='utf-8', sep=';')
        print(f"{output_file} - CSV файл успешно создан.\n")
    except Exception as e:
        print(f"Произошла ошибка при обработке файла {input_file}: {str(e)}")


def convert_all_xlsx_in_folder(folder_path, base_output_folder, columns=None):
    for filename in os.listdir(folder_path):
        if filename.endswith('.xlsx'):
            input_file = os.path.join(folder_path, filename)
            # Название папки для сохранения на основе названия файла
            output_folder = os.path.join(base_output_folder, os.path.splitext(filename)[0])
            xlsx_to_csv2(input_file, output_folder, columns)

    print("Преобразование всех файлов завершено.")


# Пример использования:
folder_path = 'C:\\Users\\ugine\\PycharmProjects\\WebFlaskApp\\trans'
base_output_folder = 'C:\\Users\\ugine\\PycharmProjects\\WebFlaskApp\\trans\\csv'
convert_all_xlsx_in_folder(folder_path, base_output_folder)

