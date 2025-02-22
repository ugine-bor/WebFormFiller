import copy
import json
import csv
import os

from os import makedirs
from dotenv import load_dotenv

load_dotenv()

fields = {'tree': {}, 'appear': {'text': [], 'select': {}, 'table': {}}, 'links': {'comm': {}, 'spec': {}}}


def find_key(d, key):
    if key in d:
        return d[key]
    for k, v in d.items():
        if isinstance(v, dict):
            result = find_key(v, key)
            if result is not None:
                return result
    return None


def appearing(fields: dict, file_path: str, lang='') -> None:
    with open(file_path, 'r', encoding='utf-8') as file:
        lang = f"-{lang}" if lang else ''
        csv_reader = csv.DictReader(file, delimiter=';')
        tablepos = 0
        tableparent = None

        for row in csv_reader:
            dat = row[f'data{lang}']
            f_id = row['id'].strip()

            if dat:
                dat = dat.strip()
                if dat.startswith("td_"):
                    dat = dat.split('_')[1]

                if ';' in dat:
                    if ',' in dat:
                        dat = ' '.join(dat.split()).split(';')
                        types = [i.split(',')[0] for i in dat]
                        fields['appear']['select'][f_id] = {
                            dat[i].split(',')[1].strip(): ['s_' + x for x in types[i].split('+')] if types[i] else []
                            for i in range(len(types))}
                    else:
                        dat = dat.split(';')
                        fields['appear']['select'][f_id] = {i.strip(): [] for i in dat}

                elif dat.startswith("table_"):
                    if tablepos and f_id.startswith(tableparent):
                        fields['appear']['table']["innertables_id"].append(f_id)

                        find_key(fields['appear']['table'], tableparent).setdefault(f_id, {})
                        tableparent = f_id
                        tablepos += int(dat.split('_')[1])
                    else:
                        fields['appear']['table']["tables_id"].append(f_id)

                        tableparent = f_id
                        tablepos = int(dat.split('_')[1])
                        fields['appear']['table'][f_id] = {}

                elif dat.startswith("tr_"):
                    if ',' in dat:
                        # print('hee', dat, f_id)
                        # fields['appear'][dat.split(',')[1]].append(f_id)
                        fields['appear'].setdefault(dat.split(',')[1], []).append(f_id)
                        dat = dat.split(',')[0]
                    tableparent = f_id.rsplit('.', 1)[0]
                    # tableparent = tableparent.rsplit('.', 1)[0] if f_id.count('.') - 1 != tableparent.count(
                    #    '.') else tableparent
                    tablepos += int(dat.split('_')[1])
                    find_key(fields['appear']['table'], tableparent).setdefault(f_id, {})
                    tableparent = f_id
                    tablepos -= 1
                else:
                    if tablepos:
                        find_key(fields['appear']['table'], tableparent).setdefault(f_id, {})
                        tablepos -= 1
                    fields['appear'].setdefault(dat, []).append(f_id)
            else:
                if tablepos:
                    fields['appear']['table'][tableparent.split('.')[0]][tableparent].setdefault(f_id, {})
                    tablepos -= 1
                fields['appear']['text'].append(f_id)


def read_csv(fields: dict, file_path: str) -> None:
    def endpoint(branch: dict, point: str) -> dict:
        if not branch:
            return branch
        for i in branch:
            ic = i.count('.')
            pc = point.count('.')
            prev = '.'.join(point.split('.')[:-1])
            if ic == pc:
                if prev == '.'.join(i.split('.')[:-1]):
                    lastb = branch
                    return lastb
            elif ic < pc and prev == i:
                lastb = branch[i]
        if branch[i] and i in point:
            return endpoint(branch[i], point)
        else:
            pass
        return lastb

    with open(file_path, 'r', encoding='utf-8') as file:
        csv_reader = csv.DictReader(file, delimiter=';')
        cache = []
        for row in csv_reader:
            field: list = row['id'].split('.')
            root = field[0]

            for i in range(1, len(field) + 1):
                current: str = '.'.join(field[:i])
                before: str = '.'.join(field[:i - 1])
                if current not in cache:
                    if not before:
                        fields['tree'][current] = {}
                    else:
                        endpoint(fields['tree'][root], current)[current] = {}
                    cache.append(current)


def update_tree(tree, path, new_branch):
    current = tree
    for field in path[:-1]:
        current = current[field]
    current[path[-1]] = new_branch


def tree_select(selects, tree):
    tree_copy = copy.deepcopy(tree)

    def rec(branch, path=[]):
        for field in list(branch.keys()):  # Создаем список ключей *перед* итерацией
            if field in tochange:
                branch['s_' + field] = {field: branch.pop(field)}
                if isinstance(branch['s_' + field][field], dict):
                    rec(branch['s_' + field][field], path + ['s_' + field, field])
            elif isinstance(branch[field], dict):
                rec(branch[field], path + [field])

    tochange = {i[2:] for field in selects.values() for v in field.values() if v for i in v}

    rec(tree_copy)
    print(tree_copy)
    return tree_copy


# ------------------------------------APPEAR AND TREE-----------------------------------------------------------------

# appearing(fields, 'static/AntragCSV/HA.csv', 'ru')

# read_csv(fields, 'static/AntragCSV/HA.csv')

# tree_select(fields['appear']['select'], fields['tree'])

# -----------------------------------------LINKS----------------------------------------------------------------------

def addlinks(fields: dict, dir: str, filename: str):
    print(os.getenv('ROOT') + fr'\trans\csv\{dir}\{filename}')
    with open(os.getenv('ROOT') + fr'\trans\csv\{dir}\{filename}', 'r', encoding='utf-8') as fin:
        read = csv.DictReader(fin, delimiter=';')
        for dct in read:
            field = dct['id']
            link = dct['link']
            if link:
                if link.startswith('spec'):
                    fields['links']['spec'][field] = link.split('spec ')[1].split(',')
                else:
                    fields['links']['comm'][field] = link.split(',')
    makedirs(f"static/json/{dir}/{filename.split('.')[0]}", exist_ok=True)
    with open(f"static/json/{dir}/{filename.split('.')[0]}/fields.json", 'w', encoding='utf-8') as fout:
        json.dump(fields, fout, ensure_ascii=False)

# addlinks(fields)
