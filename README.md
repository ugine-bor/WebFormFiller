# WordPress Theme website for XLSX-Based Form Service

![GitHub last commit](https://img.shields.io/github/last-commit/ugine-bor/WebFormFiller)

A WordPress child theme built on the Astra theme, designed for a service that generates and manages table forms based on XLSX files. Includes tools for preparing XLSX templates, multilingual support, and an AI-powered chat assistant integration.

## Features
- **XLSX Template Processing**: Convert XLSX files into web-ready formats.
- **Multilingual Support**: Tools for translating form templates.
- **AI Chat Integration**: OpenAI-based assistant for user interaction.
- **Testing Environment**: Flask implementation for local testing.
- **Customizable**: Built with PHP, JavaScript, HTML, and CSS for easy modifications.

## Installation
1. **Prerequisites**:
   - WordPress installed with the [Astra Theme](https://wordpress.org/themes/astra/) activated.
   - PHP 7.4+ .
2. **Install the Child Theme**:
   - Upload the `astra-child` folder to `wp-content/themes/`.
   - Activate the **Astra Child** theme via WordPress Admin > Appearance > Themes.
3. **Dependencies**:
   - Install Python 3.11+ and required libraries (see `requirements.txt` for tools).
   - For AI integration: Obtain an [OpenAI API key](https://platform.openai.com/).

## Usage
### Prepare XLSX Templates
- **Automated Setup**:
  - Run `Prepare_xlsx.py` (requires Python):
    ```bash
    python Prepare_xlsx.py --input /path/to/xlsx_files --output ./processed_templates
    ```

## Instruments Folder
- **Manual XLSX Processing**:
  - `xlsx_to_csv.py`: Converts XLSX to CSV.
  - `xlsx_csv_to_json.py`: Converts XLSX or CSV to JSON(alternative).
  - `translate_xlsx.py`: Add columns for another languages in xlsx.
  - `data_process`: Parser for csv made from xlsx.

### AI Chat Assistant
1. Configure your OpenAI API key in `ai.py`:
   ```python
   OPENAI = "your-api-key-here"  # use a .env file
   ```
2. Run the AI chat module:
   ```bash
   python ai.py
   ```

## Testing with Flask
A lightweight Flask app is included for form rendering tests:
1. Install Flask:
   ```bash
   pip install flask
   ```
2. Run:
   ```bash
   flask --app flask_app.py run
   ```
3. Access forms at `http://localhost:5000`.

## Customization
- **Styles**: Modify `style.css` or import SCSS files.
- **Functionality**: Extend PHP classes in `inc/`.
- **Templates**: Override Astra parent theme files as needed.

## Disclaimer
This project is not affiliated with Astra or OpenAI. Use third-party tools at your own risk.