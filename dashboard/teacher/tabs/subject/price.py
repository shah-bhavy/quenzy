import os
import sys
import google.generativeai as genai
from PyPDF2 import PdfReader
import mysql.connector
import datetime

# Replace with your actual API key
os.environ["GOOGLE_API_KEY"] = "AIzaSyCMqnIUe8vHMU2TShOeduh9aFJpSJ1cr14"

genai.configure(api_key=os.environ["GOOGLE_API_KEY"])
model = genai.GenerativeModel("gemini-1.5-flash")


def generate_questions():
    
    prompt = ("What is the current price of INFOSYS stock in NSE? Just give me the price in INR. No need to write anything except the price. Give the price without any salutations or any other text instead of price.")
    response = model.generate_content(prompt)
    generated_output = response.text.strip().split('\n')
    for line in generated_output:
        if line.strip() and not line.startswith("WARNING") and not line.startswith("E0000"):
            questions=line
            break
    return questions

def save_to_db(questions):
    conn = mysql.connector.connect(
        host="localhost",
        user="root",
        password="",
        database="stockzy"
    )
    cursor = conn.cursor()

    # Disable foreign key checks
    cursor.execute("SET FOREIGN_KEY_CHECKS=0;")

    for question_text in questions:
        cursor.execute(
            "INSERT INTO stock_prices (symbol, price) VALUES (%s, %s)",
            ("INFOSYS.NSE", question_text)
        )
     
    # Re-enable foreign key checks
    cursor.execute("SET FOREIGN_KEY_CHECKS=1;")

    conn.commit()
    conn.close()

    questions = generate_questions()
    save_to_db(questions)
