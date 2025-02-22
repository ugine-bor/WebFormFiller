import openai
import os
from openai import OpenAI
import json
import time
from dotenv import load_dotenv

load_dotenv()


def show_json(obj):
    print(json.loads(obj.model_dump_json()))
    print('\n----------------------------------------------\n')


def wait_on_run(run, thread):
    while run.status == "queued" or run.status == "in_progress":
        run = client.beta.threads.runs.retrieve(
            thread_id=thread.id,
            run_id=run.id,
        )
        time.sleep(0.5)
    return run


client = OpenAI(api_key=os.getenv("OPENAI"))

try:
    assistant = client.beta.assistants.create(
        name="Lead generator",
        instructions="You are a personal website assistant. Answer questions briefly, in a sentence or less. Answer in russian language",
        model="gpt-3.5-turbo",
    )
    show_json(assistant)

    thread = client.beta.threads.create()
    show_json(thread)

    message = client.beta.threads.messages.create(
        thread_id=thread.id,
        role="user",
        content="Как мне зарегистрироваться на этом сайте?",
    )
    show_json(message)

    run = client.beta.threads.runs.create(
        thread_id=thread.id,
        assistant_id=assistant.id,
    )
    show_json(run)

    run = wait_on_run(run, thread)
    show_json(run)

    messages = client.beta.threads.messages.list(thread_id=thread.id)
    show_json(messages)
except openai.APIError as e:
    print(f"OpenAI API returned an API Error: {e}")
    pass
except openai.APIConnectionError as e:
    print(f"Failed to connect to OpenAI API: {e}")
    pass
except openai.RateLimitError as e:
    print(f"OpenAI API request exceeded rate limit: {e}")
    pass
