from flask import Flask, url_for, render_template, redirect, request, send_from_directory

import csv
import json
import os
import time

import requests

import openai
from openai import OpenAI

app = Flask(__name__)


@app.route('/favicon.ico')
def favicon():
    return send_from_directory(os.path.join(app.root_path, 'static'), 'favicon.ico',
                               mimetype='image/vnd.microsoft.icon')


def add_field_prefix(dictionary, prefix):
    new_dict = {}
    for key, value in dictionary.items():
        new_key = f"{prefix}{key}"
        if isinstance(value, dict):
            new_value = add_field_prefix(value, prefix)
        else:
            new_value = value
        new_dict[new_key] = new_value
    return new_dict


def read_csv(file_path):
    data = []
    with open(file_path, 'r', encoding='utf-8') as file:
        csv_reader = csv.DictReader(file, delimiter=';')
        for row in csv_reader:
            data.append(row)
    return data


def read_json(file_path):
    with open(file_path, 'r', encoding='utf-8') as file:
        json_dict = json.load(file)
    return json_dict


def update_csv(file_path, data):
    smpl = read_csv(file_path)

    with open(file_path, 'w', newline='', encoding='utf-8') as file:
        fieldnames = smpl[0].keys()
        for key in data:
            for dct in smpl:
                if dct['id'] == key:
                    dct['data'] = data[key]
                    break
        csv_writer = csv.DictWriter(file, fieldnames=fieldnames)
        csv_writer.writeheader()
        csv_writer.writerows(smpl)


@app.route("/AIendpoint", methods=['POST'])
def ai_endpoint():
    data = request.get_json()
    print(111, data['content'])

    message = client.beta.threads.messages.create(
        thread_id=thread.id,
        role='user',
        content=data['content']
    )
    show_json('message', message)

    run = client.beta.threads.runs.create(
        thread_id=thread.id,
        assistant_id='asst_xBbd9j68qgrVfS0JxJJlM8ke',
    )
    show_json('run', run)

    run = wait_on_run(run, thread)
    show_json('start run', run)

    messages = client.beta.threads.messages.list(thread_id=thread.id)
    show_json('get answer', messages)
    return json.loads(messages.model_dump_json())['data'][0]['content'][0]['text']['value']


@app.route('/payment', methods=['GET', 'POST'])
def payment():
    print('редирект произошел')

    url = "https://3dsec.berekebank.kz/payment/rest/register.do"

    headers = {
        'content-type': 'application/x-www-form-urlencoded'
    }

    data = {
        'amount': '2000',
        'currency': '398',
        'userName': 'uginebor69-api',
        'password': 'rsutb-O1',
        'returnUrl': 'http:/127.0.0.1:5000/wordpress1',  # заменить
        'description': 'my_first_order',
        'language': 'en'
    }

    response = requests.post(url, headers=headers, data=data)
    print(f"результат: {response}")
    print(f"данные: {response.json()}")
    return redirect(response.json()[
                        'formUrl'])


def remove_nested(antrag, json_data):
    cleaned_data = {antrag: {}}
    for dct in json_data:
        key, value = list(dct.items())[0]
        cleaned_data[antrag][key] = value
    return cleaned_data


def sendrequest(data):
    url = "https://alexsoft.kz:44321/DBAntrag/hs/bots/PostJson"
    body = {"mode": 2,
            "lang": "ru",
            "email": "alig_1691@mail.ru",
            "partner": None,
            "message": {
                "message_id": 12345,
                "from": {
                    "id": 1449983348,
                    "is_bot": False,
                    "first_name": "Igor",
                    "last_name": "IT",
                    "language_code": "ru"
                },
                "chat": {
                    "id": 1449983348,
                    "first_name": "Igor",
                    "last_name": "IT",
                    "type": "private"
                },
                "date": 1636507200,
                "data_sample": data
            }
            }
    # json_print
    print(json.dumps(body, ensure_ascii=False, indent=4))
    # here auth
    response = requests.post(url, json=body, auth=('RemoteService', 'kMv943GkXsyRaN'), verify=False)
    response = response
    print("123", response)
    response = 'OK'
    return response


# api endpoints
@app.route('/api/userinput', methods=['GET', 'POST'])
def userinput():
    if request.method == 'POST':
        data = request.data.decode('utf-8')
        json_data = json.loads(data)

        cleaned_json_data = remove_nested(list(json_data.keys())[0], list(json_data.values())[0])
        antrag = list(cleaned_json_data.keys())[0]
        tosend = {antrag: {}}
        parentid = 'F0'
        for key, value in list(cleaned_json_data.items())[0][1].items():
            if not '.' in key:
                tosend[antrag]['F' + key] = {}
                parentid = 'F' + key
                tosend[antrag][parentid]['F' + key.replace('.', '_')] = value
            else:
                tosend[antrag][parentid]['F' + key.replace('.', '_')] = value

        respond = sendrequest(tosend)
        with open('data.json', 'w', encoding='utf-8') as f:
            json.dump(cleaned_json_data, f, ensure_ascii=False, indent=4)
        return respond
    return 'Error'


@app.route('/wordpress1', methods=['GET', 'POST'])
def wp_test():
    orderId = request.args.get("orderId")
    orderIdStatus = 'Не оплачено' if not orderId else f'Оплачено(номер заказа: {orderId})'
    return render_template('wp.html', data="ТЕСТ СТРАНИЦА ОПЛАТЫ", orderId=orderIdStatus)


# test
@app.route('/1', methods=['GET', 'POST'])
def index_test():
    if request.method == 'POST':
        # Получаем данные из формы
        new_data = {}
        for fieldname in request.form:
            if fieldname.startswith('field_'):
                index = fieldname.split('_')[1]
                new_value = request.form[fieldname]
                new_data[index] = new_value
        update_csv('static/AntragCSV/csv/HA.csv', new_data)
        return redirect(url_for('index'))
    else:
        return render_template('main_page_test.html')


@app.context_processor
def utility_functions():
    def print_in_console(msg):
        print(str(msg))
        return ''

    return {'mdebug': print_in_console}


@app.route('/', methods=['GET', 'POST'])
def index():
    def loadevery():
        every = {}
        for dir in ['static/json/csv', 'static/json/after']:
            for name in os.listdir(dir):
                with open(f"static/json/{dir.split('/')[-1]}/{name}/fields.json", 'r', encoding='utf-8') as fin:
                    every[f'fields{name}'] = json.load(fin)
        return every

    if request.method == 'POST':
        pass
    else:
        every = loadevery()
        return render_template('main_page.html', data=read_json(f'static/json/data/antrags.json'), fields=every)


with app.test_request_context():
    print(url_for('index'))


def show_json(theme, obj):
    print(f'\n----------------------- {theme} -----------------------\n')
    print(json.loads(obj.model_dump_json()))


def wait_on_run(run, thread):
    while run.status == "queued" or run.status == "in_progress":
        run = client.beta.threads.runs.retrieve(
            thread_id=thread.id,
            run_id=run.id,
        )
        time.sleep(0.5)
    return run


if __name__ == '__main__':
    client = OpenAI(api_key=os.getenv("OPENAI"))

    try:
        thread = client.beta.threads.create()
        show_json('thread creation', thread)
        print('AI READY')
    except openai.APIError as e:
        print(f"OpenAI API returned an API Error: {e}")
        pass
    except openai.APIConnectionError as e:
        print(f"Failed to connect to OpenAI API: {e}")
        pass
    except openai.RateLimitError as e:
        print(f"OpenAI API request exceeded rate limit: {e}")
        pass

    app.run(host='127.0.0.1', port=5000)
