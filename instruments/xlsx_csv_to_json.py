import pandas as pd
import os
from os import listdir
from os.path import isfile, join, splitext
from json import dump
from dotenv import load_dotenv

load_dotenv()


# DOESN'T WORK WITH ON AFTER DIRECTORY
def xlsx_to_json() -> None:
    json_str = {}
    for file in listdir(os.getenv('ROOT') + r'\trans'):
        if isfile(join(os.getenv('ROOT') + r'\trans', file)):
            data = pd.read_excel(os.getenv('ROOT') + fr'\trans\{file}', dtype=str)
            data = data.fillna('')

            data.columns = data.columns.str.replace(' ', '')
            json_str[file.rsplit('.', 1)[0]] = data.set_index('id').T.to_dict()

    with open(os.getenv('ROOT') + r"\static\AntragCSV\antrags.json", 'w',
              encoding="utf-8") as fout:
        dump(json_str, fout, ensure_ascii=False)
    print("JSON файл успешно создан.\n")


def csv_to_json() -> None:
    try:
        json_str = {}
        csv_dir = [os.getenv('ROOT') + r'\trans\csv']

        for dir in csv_dir:
            for file in listdir(dir):
                if isfile(join(dir, file)) and file.endswith('.csv'):
                    try:
                        data = pd.read_csv(join(dir, file), sep=';', on_bad_lines='skip', dtype=str).fillna('')
                        nested_data = {
                            row['id']: {
                                key: value for key, value in row.items()
                                if key != 'id'
                            }
                            for _, row in data.iterrows()
                        }
                        json_str[splitext(file)[0]] = nested_data
                    except Exception as read_error:
                        print(f"Error reading {file}: {read_error}")

        json_path = join(os.getenv('ROOT') + r"\static\json", "antrags.json")
        with open(json_path, 'w', encoding="utf-8") as fout:
            dump(json_str, fout, ensure_ascii=False, indent=4)

            print("JSON file successfully created.\n")

    except Exception as e:
        print(f"An error occurred: {str(e)}")


xlsx_to_json()
# csv_to_json()
