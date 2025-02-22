import os
import shutil

from instruments.xlsx_to_csv import xlsx_to_csv as xlsx2csv
from instruments.xlsx_csv_to_json import xlsx_to_json as xlsx2json
from instruments.xlsx_csv_to_json import csv_to_json as csv2json

from instruments.data_process import appearing, read_csv, tree_select, addlinks

from os import listdir
from dotenv import load_dotenv

load_dotenv()


def convertxlsx(only2csv=False, only2json=False, fromcsv=False):
    # Convert initial xlsx Antrags to csv
    if not only2json:
        for file in listdir('trans'):
            pass
            xlsx2csv(f"trans/{file}", f"static/AntragCSV/csv/{file.split('.')[0]}.csv")
    if only2csv:
        return

    # Merge data from all xlsx Antrags to one static/AntragCSV/antrags.json file
    if fromcsv:
        csv2json()
    else:
        xlsx2json()
    print("Created static/AntragCSV/antrags.json")


def convertcsv():
    fields = {'tree': {}, 'appear': {'text': [], 'select': {}, 'table': {}}, 'links': {'comm': {}, 'spec': {}}}
    for dir in listdir(os.getenv('ROOT') + r"\trans\csv"):
        for file in listdir(os.getenv('ROOT') + fr'\trans\csv\{dir}'):
            pth = os.getenv('ROOT') + fr'\trans\csv\{dir}\{file}'
            if file.endswith('-after.csv'):
                isafter = True
            else:
                isafter = False
            if isafter:
                print(file, ' after!!!')

                appearing(fields, pth, 'ru')

                read_csv(fields, pth)

                fields['tree'] = tree_select(fields['appear']['select'], fields['tree'])

                addlinks(fields, dir, file)
            else:
                print(file, ' csv!!!')

                appearing(fields, pth, 'ru')

                read_csv(fields, pth)

                fields['tree'] = tree_select(fields['appear']['select'], fields['tree'])

                addlinks(fields, dir, file)

            fields = {'tree': {},
                      'appear': {'text': [], 'select': {}, 'table': {'tables_id': [], 'innertables_id': []}},
                      'links': {'comm': {}, 'spec': {}}}


convertxlsx(only2csv=True, only2json=True, fromcsv=False)
convertcsv()


def remove_subdirectories(folder_path):
    for root, dirs, files in os.walk(folder_path):
        for dir_name in dirs:
            current_dir = os.path.join(root, dir_name)

            # Проходим по вложенным папкам и перемещаем файлы в текущую директорию
            for subdir_root, subdirs, subfiles in os.walk(current_dir, topdown=False):
                for file_name in subfiles:
                    file_path = os.path.join(subdir_root, file_name)
                    # Перемещаем файл на один уровень вверх
                    shutil.move(file_path, current_dir)

                # Удаляем пустые подкаталоги
                if subdir_root != current_dir:
                    os.rmdir(subdir_root)


folder_path = os.getenv('ROOT') + r'\static\json'
remove_subdirectories(folder_path)
