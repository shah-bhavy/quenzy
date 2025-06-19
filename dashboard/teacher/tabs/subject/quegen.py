import os
import sys
import google.generativeai as genai
from PyPDF2 import PdfReader
import mysql.connector
import datetime
import re # Import regex module for more robust parsing

# Replace with your actual API key
os.environ["GOOGLE_API_KEY"] = "AIzaSyCMqnIUe8vHMU2TShOeduh9aFJpSJ1cr14" # Ensure this is securely handled in production

genai.configure(api_key=os.environ["GOOGLE_API_KEY"])
model = genai.GenerativeModel("gemini-1.5-flash")

def extract_text_from_pdf(pdf_path):
    """Extracts text from a PDF file."""
    try:
        with open(pdf_path, 'rb') as f:
            pdf_reader = PdfReader(f)
            text = ""
            for page_num in range(len(pdf_reader.pages)):
                page = pdf_reader.pages[page_num]
                text += page.extract_text()
        return text
    except Exception as e:
        print(f"Error extracting text from PDF: {e}")
        return None

def generate_questions(text):
    """Generates questions based on the given text and parses their marks."""
    questions_with_marks = [] # This will store tuples of (question_text, marks)
    prompt = (f"Generate a 25 one-markers, 10 two-markers, 10 five markers questions based on the following text to test my studies"
              f" that can be asked in examination. No need to provide answers. No need to add any additional information or note or"
              f" any advice. Also while generating, just generate questions, no need to write headings for 2 markers 3 markers or 5 markers."
              f" Just add '1.' before onemarker questions, '2.' before two marker questions and '5.' before five marker questions."
              f" **Here's the content**: {text}")
    
    try:
        response = model.generate_content(prompt)
        # It's good practice to check if text is available, especially with safety settings
        generated_output = response.text.strip().split('\n')
    except Exception as e:
        print(f"Error generating content from AI: {e}")
        return []

    # Regex to match the start of a question with its marker
    # This specifically looks for "1.", "2.", or "5." at the start of a line.
    question_pattern = re.compile(r"^(1\.|2\.|5\.)\s*(.*)")

    for line in generated_output:
        line = line.strip()
        if not line or line.startswith("WARNING") or line.startswith("E0000"):
            continue # Skip empty lines, warnings, or errors

        match = question_pattern.match(line)
        if match:
            marker_prefix = match.group(1) # e.g., "1."
            question_text = match.group(2).strip() # The rest of the line after the prefix

            marks = 1.0 # Default to 1.0
            if marker_prefix == "2.":
                marks = 2.0
            elif marker_prefix == "5.":
                marks = 5.0
            # For "1.", it remains 1.0

            if question_text: # Ensure there's actual question text
                questions_with_marks.append((question_text, marks))
        else:
            # This 'else' block catches lines that don't match the expected marker pattern.
            # Depending on AI's consistency, you might want to log these or assign a default.
            # For this script, we'll assign a default mark and treat the whole line as question text.
            print(f"Warning: Line does not start with 1., 2., or 5. prefix. Assigning 1.0 mark: '{line}'")
            marks = 1.0
            question_text = line # Keep the entire line as question text if pattern not found
            if question_text:
                questions_with_marks.append((question_text, marks))

    return questions_with_marks

def save_to_db(questions_with_marks, chapter_id):
    """Saves generated questions with their marks to the database."""
    conn = None
    try:
        conn = mysql.connector.connect(
            host="localhost",
            user="root",
            password="",
            database="quenzy"
        )
        cursor = conn.cursor()

        # Disable foreign key checks (optional, good for bulk inserts if issues arise, but usually not needed for simple inserts)
        cursor.execute("SET FOREIGN_KEY_CHECKS=0;")

        # Prepare the INSERT statement for questions table, including the 'marks' column
        # Ensure your 'questions' table has a 'marks' column (e.g., DECIMAL(3,1) or INT)
        insert_query = "INSERT INTO questions (question_text, chapter_id, marks, created_at) VALUES (%s, %s, %s, %s)"

        for question_text, marks in questions_with_marks:
            created_at = datetime.datetime.now() # Timestamp for each question
            cursor.execute(
                insert_query,
                (question_text, chapter_id, marks, created_at)
            )

        # Re-enable foreign key checks
        cursor.execute("SET FOREIGN_KEY_CHECKS=1;")

        conn.commit()
        print(f"Successfully saved {len(questions_with_marks)} questions to the database.")

    except mysql.connector.Error as err:
        print(f"Database error: {err}")
        if conn:
            conn.rollback() # Rollback changes if an error occurs
    except Exception as e:
        print(f"An unexpected error occurred: {e}")
    finally:
        if conn:
            conn.close()

if __name__ == "__main__":
    if len(sys.argv) < 3:
        print("Usage: python your_script_name.py <pdf_path> <chapter_id>")
        sys.exit(1)

    pdf_path = sys.argv[1]
    chapter_id = int(sys.argv[2])

    print(f"Extracting text from: {pdf_path}")
    text_content = extract_text_from_pdf(pdf_path)

    if text_content:
        print("Generating questions...")
        questions_data = generate_questions(text_content) # This now returns (text, marks) tuples

        if questions_data:
            print(f"Generated {len(questions_data)} questions. Saving to database...")
            save_to_db(questions_data, chapter_id)
        else:
            print("No questions were generated or parsed.")
    else:
        print("Failed to extract text from PDF. Cannot generate questions.")