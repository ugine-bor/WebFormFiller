import pandas as pd
from mtranslate import translate
from os import listdir
import re


def translate_excel(input_file, output_file, column_n_index, column_k_index, flag=True):
    try:
        df = pd.read_excel(input_file)
    except Exception as e:
        print(f"Ошибка при чтении файла: {e}")
        return

    original_column_name = df.columns[column_n_index]
    if '-de' in original_column_name:
        new_column_name = f"{original_column_name[:-3]}-ru"
    else:
        new_column_name = f"{original_column_name}-ru"

    if new_column_name not in df.columns:
        if column_k_index > len(df.columns):
            for i in range(len(df.columns), column_k_index):
                df.insert(i, "--RU--", "", allow_duplicates=False)
        df.insert(column_k_index, new_column_name, "", allow_duplicates=False)

    max_translation_length = 4000

    try:
        for index, row in df.iterrows():
            try:
                cell_n = row.iloc[column_n_index]

                if not isinstance(cell_n, str):
                    cell_n = str(cell_n)

                if '!' in cell_n:
                    cell_n = cell_n.split('!')
                    if len(cell_n) > 1:
                        cell_n = cell_n[0]
                    else:
                        cell_n = ''

                if pd.isna(cell_n) or cell_n == "None" or cell_n == "" or cell_n == "NaN" or cell_n == "nan":
                    print(f'{index + 1}: Пустая строка/NaN, пропускаем перевод')
                    continue

                if len(cell_n) > max_translation_length:
                    translation = translate_large_text(cell_n, "de", "ru")
                else:
                    if column_n_index == 2 and not ';' in cell_n:
                        print("строка data без ; не переводим")
                        translation = cell_n
                    else:
                        if column_n_index == 2:
                            regex = r',\s*([^,;]+)'
                            text_values = re.findall(regex, cell_n)
                            print(text_values)
                            text_values_translated = {}
                            for value in text_values:
                                text_values_translated[value] = translate(value, "ru", "de")
                            translation = re.sub(regex, lambda match: ',' + text_values_translated.get(match.group(1),
                                                                                                       match.group(1)),
                                                 cell_n)
                            print("see ", translation)
                        else:
                            translation = translate(cell_n, "ru", "de")
                            # translation = cell_n # test

                print(f'{index + 1}: {cell_n} -> {translation}')
                df.loc[index, new_column_name] = translation
            except IndexError as e:
                print(f'ошибка: {e}')
                print(f'{index + 1}: Выход за пределы')
                continue

        if flag:
            df.rename(columns={df.columns[column_k_index]: f"{df.columns[column_k_index]}"}, inplace=True)

        df.to_excel(output_file, index=False)
    except Exception as e:
        print(f"ошибка: {e}")


def translate_large_text(text, source_lang, target_lang):
    max_length = 4000
    chunks = [text[i:i + max_length] for i in range(0, len(text), max_length)]
    translations = []
    for chunk in chunks:
        translations.append(translate(chunk, target_lang, source_lang))
    return ''.join(translations)


for file in listdir('../processedxlsx'):
    if file in list(filter(lambda x: x.startswith('_'), listdir('../xlsx'))):
        translate_excel('processedxlsx/' + file, 'trans/' + file, 1, 7, True)
        translate_excel('trans/' + file, 'trans/' + file, 2, 8, True)
        translate_excel('trans/' + file, 'trans/' + file, 4, 9, True)
        translate_excel('trans/' + file, 'trans/' + file, 5, 10, True)
